<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** Catálogo de reportes disponibles. */
    public const REPORTES = [
        'ventas' => [
            'title' => 'Ventas por período',
            'description' => 'Detalle de comprobantes emitidos en un rango de fechas.',
            'icon' => 'receipt',
            'filtros' => ['fechas'],
        ],
        'productos' => [
            'title' => 'Productos más vendidos',
            'description' => 'Ranking de productos por importe vendido.',
            'icon' => 'package',
            'filtros' => ['fechas'],
        ],
        'rentabilidad' => [
            'title' => 'Rentabilidad',
            'description' => 'Utilidad y margen por producto (venta vs. costo).',
            'icon' => 'trending-up',
            'filtros' => ['fechas'],
        ],
        'compras' => [
            'title' => 'Compras por período',
            'description' => 'Detalle de compras registradas en un rango de fechas.',
            'icon' => 'shopping-bag',
            'filtros' => ['fechas'],
        ],
        'stock-valorizado' => [
            'title' => 'Stock valorizado',
            'description' => 'Valor del inventario al costo promedio actual.',
            'icon' => 'boxes',
            'filtros' => [],
        ],
    ];

    public function exists(string $key): bool
    {
        return array_key_exists($key, self::REPORTES);
    }

    public function build(string $key, array $filters, int $companyId): array
    {
        [$desde, $hasta] = $this->rango($filters);

        $meta = self::REPORTES[$key];
        $base = [
            'key' => $key,
            'title' => $meta['title'],
            'description' => $meta['description'],
            'filtros' => $meta['filtros'],
            'periodo' => in_array('fechas', $meta['filtros'], true)
                ? ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]
                : null,
        ];

        return match ($key) {
            'ventas' => $base + $this->ventas($companyId, $desde, $hasta),
            'productos' => $base + $this->productos($companyId, $desde, $hasta),
            'rentabilidad' => $base + $this->rentabilidad($companyId, $desde, $hasta),
            'compras' => $base + $this->compras($companyId, $desde, $hasta),
            'stock-valorizado' => $base + $this->stockValorizado($companyId),
        };
    }

    private function rango(array $filters): array
    {
        $desde = ! empty($filters['desde'])
            ? Carbon::parse($filters['desde'])->startOfDay()
            : Carbon::now()->startOfMonth();
        $hasta = ! empty($filters['hasta'])
            ? Carbon::parse($filters['hasta'])->endOfDay()
            : Carbon::now()->endOfDay();

        return [$desde, $hasta];
    }

    private function ventas(int $cid, Carbon $desde, Carbon $hasta): array
    {
        $sales = Sale::where('estado', '!=', 'anulada')
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->get();

        $rows = $sales->map(fn (Sale $s) => [
            Carbon::parse($s->fecha)->format('d/m/Y'),
            $s->numero,
            $s->cliente_nombre,
            ucfirst($s->tipo_comprobante),
            (float) $s->subtotal,
            (float) $s->igv,
            (float) $s->total,
        ])->all();

        return [
            'columns' => [
                ['label' => 'Fecha', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Comprobante', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Cliente', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Tipo', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Subtotal', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'IGV', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'Total', 'align' => 'right', 'format' => 'currency'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Comprobantes', 'value' => $sales->count(), 'format' => 'int'],
                ['label' => 'Subtotal', 'value' => round((float) $sales->sum('subtotal'), 2), 'format' => 'currency'],
                ['label' => 'IGV', 'value' => round((float) $sales->sum('igv'), 2), 'format' => 'currency'],
                ['label' => 'Total', 'value' => round((float) $sales->sum('total'), 2), 'format' => 'currency'],
            ],
        ];
    }

    private function productos(int $cid, Carbon $desde, Carbon $hasta): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $cid)
            ->where('sales.estado', '!=', 'anulada')
            ->whereBetween('sales.fecha', [$desde, $hasta])
            ->groupBy('sale_items.descripcion')
            ->selectRaw('sale_items.descripcion as name, SUM(sale_items.cantidad) as qty, SUM(sale_items.total) as amount')
            ->orderByDesc('amount')
            ->get();

        return [
            'columns' => [
                ['label' => 'Producto', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Cantidad', 'align' => 'right', 'format' => 'number'],
                ['label' => 'Importe', 'align' => 'right', 'format' => 'currency'],
            ],
            'rows' => $rows->map(fn ($r) => [$r->name, (float) $r->qty, round((float) $r->amount, 2)])->all(),
            'summary' => [
                ['label' => 'Productos', 'value' => $rows->count(), 'format' => 'int'],
                ['label' => 'Importe total', 'value' => round((float) $rows->sum('amount'), 2), 'format' => 'currency'],
            ],
        ];
    }

    private function rentabilidad(int $cid, Carbon $desde, Carbon $hasta): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $cid)
            ->where('sales.estado', '!=', 'anulada')
            ->whereBetween('sales.fecha', [$desde, $hasta])
            ->groupBy('sale_items.descripcion')
            ->selectRaw('sale_items.descripcion as name, SUM(sale_items.base) as venta, SUM(sale_items.costo_unitario * sale_items.cantidad) as costo')
            ->orderByDesc('venta')
            ->get();

        $data = $rows->map(function ($r) {
            $venta = round((float) $r->venta, 2);
            $costo = round((float) $r->costo, 2);
            $utilidad = round($venta - $costo, 2);
            $margen = $venta > 0 ? round($utilidad / $venta * 100, 1) : 0;

            return [$r->name, $venta, $costo, $utilidad, $margen];
        });

        $totalVenta = round((float) $data->sum(fn ($r) => $r[1]), 2);
        $totalCosto = round((float) $data->sum(fn ($r) => $r[2]), 2);
        $totalUtil = round($totalVenta - $totalCosto, 2);

        return [
            'columns' => [
                ['label' => 'Producto', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Venta (s/IGV)', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'Costo', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'Utilidad', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'Margen %', 'align' => 'right', 'format' => 'number'],
            ],
            'rows' => $data->all(),
            'summary' => [
                ['label' => 'Venta total', 'value' => $totalVenta, 'format' => 'currency'],
                ['label' => 'Costo total', 'value' => $totalCosto, 'format' => 'currency'],
                ['label' => 'Utilidad', 'value' => $totalUtil, 'format' => 'currency'],
                ['label' => 'Margen', 'value' => $totalVenta > 0 ? round($totalUtil / $totalVenta * 100, 1) : 0, 'format' => 'number'],
            ],
        ];
    }

    private function compras(int $cid, Carbon $desde, Carbon $hasta): array
    {
        $compras = Purchase::with('supplier:id,razon_social')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')
            ->get();

        $rows = $compras->map(fn (Purchase $p) => [
            Carbon::parse($p->fecha)->format('d/m/Y'),
            $p->numero_completo ?: '—',
            $p->supplier?->razon_social ?? 'Sin proveedor',
            (float) $p->total,
            ucfirst($p->estado_pago),
        ])->all();

        return [
            'columns' => [
                ['label' => 'Fecha', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Comprobante', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Proveedor', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Total', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'Estado', 'align' => 'center', 'format' => 'text'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Compras', 'value' => $compras->count(), 'format' => 'int'],
                ['label' => 'Total', 'value' => round((float) $compras->sum('total'), 2), 'format' => 'currency'],
                ['label' => 'Pagado', 'value' => round((float) $compras->sum('monto_pagado'), 2), 'format' => 'currency'],
                ['label' => 'Saldo', 'value' => round((float) $compras->sum(fn ($p) => $p->saldo), 2), 'format' => 'currency'],
            ],
        ];
    }

    private function stockValorizado(int $cid): array
    {
        $products = Product::where('controla_stock', true)
            ->with('category:id,nombre')
            ->withSum('stocks as stock_total', 'cantidad')
            ->orderBy('nombre')
            ->get();

        $rows = $products->map(function (Product $p) {
            $stock = (float) ($p->stock_total ?? 0);
            $costo = (float) $p->costo_promedio;

            return [$p->codigo, $p->nombre, $p->category?->nombre ?? 'Sin categoría', $stock, $costo, round($stock * $costo, 2)];
        });

        return [
            'columns' => [
                ['label' => 'Código', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Producto', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Categoría', 'align' => 'left', 'format' => 'text'],
                ['label' => 'Stock', 'align' => 'right', 'format' => 'number'],
                ['label' => 'Costo prom.', 'align' => 'right', 'format' => 'currency'],
                ['label' => 'Valor', 'align' => 'right', 'format' => 'currency'],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => 'Productos', 'value' => $products->count(), 'format' => 'int'],
                ['label' => 'Valor del inventario', 'value' => round((float) $rows->sum(fn ($r) => $r[5]), 2), 'format' => 'currency'],
            ],
        ];
    }
}
