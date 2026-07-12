<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function index(Request $request): Response
    {
        $company = $request->user()->company;
        $cid = $company?->id;
        $moneda = $company?->moneda ?? 'PEN';

        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $inicioMesAnterior = (clone $inicioMes)->subMonth();

        $ventasHoy = (float) Sale::where('estado', '!=', 'anulada')
            ->whereDate('fecha', $hoy)->sum('total');
        $ventasMes = (float) Sale::where('estado', '!=', 'anulada')
            ->where('fecha', '>=', $inicioMes)->sum('total');
        $ventasMesAnterior = (float) Sale::where('estado', '!=', 'anulada')
            ->whereBetween('fecha', [$inicioMesAnterior, $inicioMes])->sum('total');
        $ventasAyer = (float) Sale::where('estado', '!=', 'anulada')
            ->whereDate('fecha', (clone $hoy)->subDay())->sum('total');

        $porCobrar = (float) Sale::where('estado', 'pendiente')
            ->sum(DB::raw('total - monto_cobrado'));
        $porPagar = (float) Purchase::whereIn('estado_pago', ['pendiente', 'parcial'])
            ->sum(DB::raw('total - monto_pagado'));

        $stockBajo = Product::where('controla_stock', true)
            ->where('is_active', true)
            ->withSum('stocks as stock_total', 'cantidad')
            ->get(['id', 'stock_minimo'])
            ->filter(fn ($p) => (float) ($p->stock_total ?? 0) <= (float) $p->stock_minimo)
            ->count();

        $stats = [
            [
                'key' => 'ventas_hoy', 'label' => 'Ventas de hoy', 'value' => round($ventasHoy, 2),
                'format' => 'currency', 'delta' => $this->delta($ventasHoy, $ventasAyer),
                'icon' => 'shopping-cart', 'color' => 'brand',
            ],
            [
                'key' => 'ventas_mes', 'label' => 'Ventas del mes', 'value' => round($ventasMes, 2),
                'format' => 'currency', 'delta' => $this->delta($ventasMes, $ventasMesAnterior),
                'icon' => 'trending-up', 'color' => 'emerald',
            ],
            [
                'key' => 'por_cobrar', 'label' => 'Por cobrar', 'value' => round($porCobrar, 2),
                'format' => 'currency', 'delta' => 0, 'icon' => 'hand-coins', 'color' => 'amber',
            ],
            [
                'key' => 'por_pagar', 'label' => 'Por pagar', 'value' => round($porPagar, 2),
                'format' => 'currency', 'delta' => 0, 'icon' => 'landmark', 'color' => 'rose',
            ],
        ];

        // Caja abierta del usuario
        $sesion = CashSession::where('user_id', $request->user()->id)
            ->where('estado', 'abierta')->latest('id')->first();
        $caja = $sesion ? [
            'abierta' => true,
            'nombre' => $sesion->cashRegister?->nombre,
            'saldo' => $sesion->saldoEsperado(),
        ] : ['abierta' => false];

        return Inertia::render('Dashboard', [
            'moneda' => $moneda,
            'stats' => $stats,
            'stockBajo' => $stockBajo,
            'caja' => $caja,
            'salesByMonth' => $this->seriesMensual($cid),
            'salesByCategory' => $this->ventasPorCategoria($cid, $inicioMes),
            'topProducts' => $this->topProductos($cid, $inicioMes),
            'recentSales' => $this->ultimasVentas(),
        ]);
    }

    private function delta(float $actual, float $anterior): float
    {
        if ($anterior <= 0) {
            return 0;
        }

        return round(($actual - $anterior) / $anterior * 100, 1);
    }

    /** Ventas vs compras de los últimos 6 meses (agregado en PHP, portable). */
    private function seriesMensual(?int $cid): array
    {
        $desde = Carbon::now()->startOfMonth()->subMonths(5);

        $ventas = Sale::where('estado', '!=', 'anulada')
            ->where('fecha', '>=', $desde)
            ->get(['fecha', 'total']);
        $compras = Purchase::where('fecha', '>=', $desde->toDateString())
            ->get(['fecha', 'total']);

        $categories = [];
        $ventasSerie = [];
        $comprasSerie = [];

        for ($i = 0; $i < 6; $i++) {
            $mes = (clone $desde)->addMonths($i);
            $key = $mes->format('Y-m');
            $categories[] = self::MESES[(int) $mes->format('n')];
            $ventasSerie[] = round((float) $ventas
                ->filter(fn ($v) => Carbon::parse($v->fecha)->format('Y-m') === $key)
                ->sum('total'), 2);
            $comprasSerie[] = round((float) $compras
                ->filter(fn ($c) => Carbon::parse($c->fecha)->format('Y-m') === $key)
                ->sum('total'), 2);
        }

        return ['categories' => $categories, 'ventas' => $ventasSerie, 'compras' => $comprasSerie];
    }

    private function ventasPorCategoria(?int $cid, Carbon $desde): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.company_id', $cid)
            ->where('sales.estado', '!=', 'anulada')
            ->where('sales.fecha', '>=', $desde)
            ->groupBy('categories.nombre')
            ->selectRaw('COALESCE(categories.nombre, ?) as categoria, SUM(sale_items.total) as monto', ['Sin categoría'])
            ->orderByDesc('monto')
            ->get();

        return [
            'labels' => $rows->pluck('categoria')->all(),
            'series' => $rows->map(fn ($r) => round((float) $r->monto, 2))->all(),
        ];
    }

    private function topProductos(?int $cid, Carbon $desde): array
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $cid)
            ->where('sales.estado', '!=', 'anulada')
            ->where('sales.fecha', '>=', $desde)
            ->groupBy('sale_items.descripcion')
            ->selectRaw('sale_items.descripcion as name, SUM(sale_items.cantidad) as qty, SUM(sale_items.total) as amount')
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'qty' => (float) $r->qty,
                'amount' => round((float) $r->amount, 2),
            ])->all();
    }

    private function ultimasVentas(): array
    {
        return Sale::orderByDesc('id')->limit(6)->get()
            ->map(fn (Sale $s) => [
                'doc' => $s->numero,
                'cliente' => $s->cliente_nombre,
                'total' => round((float) $s->total, 2),
                'estado' => ucfirst($s->estado),
            ])->all();
    }
}
