<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category_id', 'estado']);

        $products = Product::query()
            ->with(['category:id,nombre', 'unit:id,abreviatura'])
            ->withSum('stocks as stock_total', 'cantidad')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('codigo_barras', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when(isset($filters['estado']) && $filters['estado'] !== '', function ($q) use ($filters) {
                $q->where('is_active', $filters['estado'] === 'activo');
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => Category::orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Products/Create', $this->formData());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $stockInicial = $data['stock_inicial'] ?? [];
        unset($data['stock_inicial']);

        DB::transaction(function () use ($data, $stockInicial) {
            $product = Product::create($data);

            if ($product->controla_stock) {
                foreach ($stockInicial as $row) {
                    $cantidad = (float) ($row['cantidad'] ?? 0);
                    if ($cantidad <= 0) {
                        continue;
                    }
                    $warehouse = Warehouse::findOrFail($row['warehouse_id']);
                    $costo = (float) ($row['costo'] ?? $product->precio_compra);
                    $this->inventory->entrada(
                        $product, $warehouse, $cantidad, $costo,
                        'Stock inicial', 'Alta de producto',
                    );
                }
            }
        });

        return redirect()->route('inventario.productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): Response
    {
        $product->load(['stocks.warehouse:id,nombre']);

        return Inertia::render('Inventory/Products/Edit', [
            ...$this->formData(),
            'product' => $product,
            'stockTotal' => (float) $product->stocks()->sum('cantidad'),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        unset($data['stock_inicial']);
        $product->update($data);

        return redirect()->route('inventario.productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->movements()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar: el producto tiene movimientos de inventario. Desactívalo en su lugar.',
            );
        }

        $product->delete();

        return back()->with('success', 'Producto eliminado.');
    }

    private function formData(): array
    {
        return [
            'categories' => Category::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
            'units' => Unit::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre', 'abreviatura']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
