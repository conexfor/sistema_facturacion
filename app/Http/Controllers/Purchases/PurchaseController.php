<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\CashService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchases,
        private readonly CashService $cash,
    ) {
    }

    public function index(Request $request): Response
    {
        $purchases = Purchase::query()
            ->with(['supplier:id,razon_social', 'user:id,name'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('numero', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('razon_social', 'like', "%{$s}%"));
            }))
            ->when($request->input('estado_pago'), fn ($q, $e) => $q->where('estado_pago', $e))
            ->when($request->input('desde'), fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($request->input('hasta'), fn ($q, $h) => $q->whereDate('fecha', '<=', $h))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search', 'estado_pago', 'desde', 'hasta']),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function create(): Response
    {
        $warehouses = Warehouse::where('is_active', true)->orderByDesc('is_main')->orderBy('nombre')
            ->get(['id', 'nombre']);

        return Inertia::render('Purchases/Create', [
            'warehouses' => $warehouses,
            'defaultWarehouseId' => $warehouses->first()?->id,
            'igv' => (float) (request()->user()->company->igv_porcentaje ?? 18),
            'moneda' => request()->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        $products = Product::query()
            ->where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%")
                    ->orWhere('codigo_barras', 'like', "%{$q}%");
            }))
            ->with('unit:id,abreviatura')
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'codigo', 'nombre', 'precio_compra', 'afecto_igv', 'controla_stock', 'unit_id']);

        return response()->json($products);
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        try {
            $purchase = $this->purchases->registrar($data);
            if (($data['condicion_pago'] ?? null) === 'contado') {
                $this->cash->registrarEgresoPagoCompra(
                    $purchase, (float) $purchase->total, $data['metodo_pago'] ?? 'efectivo',
                );
            }
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.show', $purchase->id)
            ->with('success', 'Compra registrada y stock actualizado.');
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load([
            'items.product:id,codigo',
            'supplier',
            'user:id,name',
            'warehouse:id,nombre',
            'payments.user:id,name',
        ]);

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase,
            'saldo' => $purchase->saldo,
        ]);
    }

    public function pdf(Request $request, Purchase $purchase, \App\Services\DocumentPdfService $pdf): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->purchase($purchase, $request->query('formato') === 'ticket' ? 'ticket' : 'a4');
    }

    public function payables(Request $request): Response
    {
        $purchases = Purchase::query()
            ->whereIn('estado_pago', ['pendiente', 'parcial'])
            ->with('supplier:id,razon_social')
            ->when($request->input('search'), fn ($q, $s) => $q->whereHas('supplier', fn ($q) => $q->where('razon_social', 'like', "%{$s}%")))
            ->orderBy('fecha_vencimiento')
            ->orderBy('fecha')
            ->paginate(15)
            ->withQueryString();

        $totalDeuda = Purchase::whereIn('estado_pago', ['pendiente', 'parcial'])
            ->selectRaw('COALESCE(SUM(total + percepcion_monto - monto_pagado), 0) as deuda')
            ->value('deuda');

        return Inertia::render('Purchases/Payables', [
            'purchases' => $purchases,
            'filters' => $request->only(['search']),
            'totalDeuda' => round((float) $totalDeuda, 2),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function pagar(Request $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'transferencia', 'deposito', 'cheque', 'tarjeta', 'yape', 'plin'])],
            'fecha' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->purchases->registrarPago(
                $purchase,
                (float) $data['monto'],
                $data['metodo_pago'],
                isset($data['fecha']) ? \Illuminate\Support\Carbon::parse($data['fecha']) : null,
                $data['observacion'] ?? null,
            );
            $this->cash->registrarEgresoPagoCompra($purchase, (float) $data['monto'], $data['metodo_pago']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago registrado correctamente.');
    }
}
