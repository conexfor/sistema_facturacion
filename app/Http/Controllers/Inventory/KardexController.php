<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KardexController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->where('controla_stock', true)
            ->with('unit:id,abreviatura')
            ->withSum('stocks as stock_total', 'cantidad')
            ->when($request->input('search'), fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Inventory/Kardex/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Product $product, Request $request): Response
    {
        $movements = $product->movements()
            ->with(['warehouse:id,nombre', 'user:id,name'])
            ->when($request->input('warehouse_id'), fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->input('desde'), fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($request->input('hasta'), fn ($q, $h) => $q->whereDate('fecha', '<=', $h))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $product->loadSum('stocks as stock_total', 'cantidad');
        $stockPorAlmacen = $product->stocks()
            ->with('warehouse:id,nombre')
            ->get()
            ->map(fn ($s) => [
                'almacen' => $s->warehouse?->nombre,
                'cantidad' => (float) $s->cantidad,
            ]);

        return Inertia::render('Inventory/Kardex/Show', [
            'product' => $product->only(['id', 'codigo', 'nombre', 'costo_promedio', 'stock_total']),
            'movements' => $movements,
            'stockPorAlmacen' => $stockPorAlmacen,
            'warehouses' => Warehouse::orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['warehouse_id', 'desde', 'hasta']),
        ]);
    }
}
