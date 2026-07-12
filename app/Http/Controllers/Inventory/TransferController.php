<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TransferController extends Controller
{
    public function __construct(private readonly TransferService $transfers)
    {
    }

    public function index(Request $request): Response
    {
        $transfers = Transfer::query()
            ->with(['origin:id,nombre', 'dest:id,nombre', 'user:id,name'])
            ->withCount('items')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);

        return Inertia::render('Inventory/Transfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Transfers/Create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');
        $warehouseId = $request->input('warehouse_id');

        $products = Product::query()
            ->where('is_active', true)
            ->where('controla_stock', true)
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
            ->get(['id', 'codigo', 'nombre', 'unit_id']);

        return response()->json($products);
    }

    public function store(TransferRequest $request): RedirectResponse
    {
        try {
            $transfer = $this->transfers->registrar($request->validated());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventario.transferencias.show', $transfer->id)
            ->with('success', 'Transferencia registrada y stock actualizado.');
    }

    public function show(Transfer $transfer): Response
    {
        $transfer->load(['items.product:id,codigo', 'origin:id,nombre', 'dest:id,nombre', 'user:id,name']);

        return Inertia::render('Inventory/Transfers/Show', [
            'transfer' => $transfer,
        ]);
    }
}
