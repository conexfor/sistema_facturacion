<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\CashService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PosController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly CashService $cash,
    ) {
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $warehouses = Warehouse::where('is_active', true)->orderByDesc('is_main')->orderBy('nombre')
            ->get(['id', 'nombre']);

        return Inertia::render('Sales/Pos', [
            'warehouses' => $warehouses,
            'defaultWarehouseId' => $warehouses->first()?->id,
            'igv' => (float) ($user->company->igv_porcentaje ?? 18),
            'moneda' => $user->company->moneda ?? 'PEN',
            'agentePercepcion' => (bool) $user->company->agente_percepcion,
            'percepcionTasa' => (float) ($user->company->percepcion_tasa ?? 2),
        ]);
    }

    /** Búsqueda de productos para el POS (JSON). */
    public function buscarProductos(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');
        $warehouseId = $request->input('warehouse_id');

        $products = Product::query()
            ->where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%")
                    ->orWhere('codigo_barras', 'like', "%{$q}%");
            }))
            ->with('unit:id,abreviatura')
            ->withSum(
                ['stocks as stock' => fn ($q) => $q->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))],
                'cantidad',
            )
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'codigo', 'nombre', 'precio_venta', 'afecto_igv', 'controla_stock', 'unit_id']);

        return response()->json($products);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        try {
            $sale = $this->sales->registrar($request->validated());
            $cajaOk = $this->cash->registrarIngresoVenta($sale);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = "Venta {$sale->numero} registrada correctamente.";
        if (! $cajaOk) {
            $msg .= ' Aviso: no hay una caja abierta a tu nombre, así que el ingreso no quedó registrado en caja.';
        }

        return redirect()->route('ventas.show', $sale->id)
            ->with($cajaOk ? 'success' : 'warning', $msg);
    }

    /** Búsqueda rápida de clientes (reutilizada en el POS). */
    public function buscarClientes(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        return response()->json(
            Customer::where('is_active', true)
                ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                    $query->where('razon_social', 'like', "%{$q}%")
                        ->orWhere('numero_documento', 'like', "%{$q}%");
                }))
                ->orderBy('razon_social')
                ->limit(10)
                ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social']),
        );
    }
}
