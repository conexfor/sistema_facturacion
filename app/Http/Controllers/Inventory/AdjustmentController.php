<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdjustmentController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(): Response
    {
        $recientes = InventoryMovement::query()
            ->whereIn('tipo', ['ajuste_entrada', 'ajuste_salida'])
            ->with(['product:id,codigo,nombre', 'warehouse:id,nombre', 'user:id,name'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return Inertia::render('Inventory/Adjustments/Index', [
            'products' => Product::where('controla_stock', true)
                ->where('is_active', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
            'recientes' => $recientes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'cantidad_objetivo' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $warehouse = Warehouse::findOrFail($data['warehouse_id']);

        $movimiento = $this->inventory->ajustar(
            $product, $warehouse, (float) $data['cantidad_objetivo'], $data['motivo'],
        );

        if (! $movimiento) {
            throw ValidationException::withMessages([
                'cantidad_objetivo' => 'La cantidad objetivo es igual al stock actual; no hay nada que ajustar.',
            ]);
        }

        return back()->with('success', 'Ajuste de inventario registrado correctamente.');
    }

    /** Endpoint auxiliar: stock actual de un producto en un almacén. */
    public function stockActual(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'warehouse_id' => ['required', 'integer'],
        ]);

        // El scope de empresa garantiza el aislamiento al resolver el producto.
        Product::findOrFail($data['product_id']);

        $cantidad = (float) (ProductStock::where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->value('cantidad') ?? 0);

        return response()->json(['cantidad' => $cantidad]);
    }
}
