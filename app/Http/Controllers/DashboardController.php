<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    private const DIAS = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    /** Días tras la emisión a partir de los cuales una venta pendiente se considera vencida. */
    private const DIAS_VENCIMIENTO = 30;

    /** Margen sobre el promedio de los 3 meses previos que define la meta mensual. */
    private const META_CRECIMIENTO = 1.10;

    public function index(Request $request): Response
    {
        $company = $request->user()->company;
        $cid = $company?->id;
        $moneda = $company?->moneda ?? 'PEN';

        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $inicioMesAnterior = (clone $inicioMes)->subMonth();

        $vigentes = fn () => Sale::where('estado', '!=', 'anulada');

        $ventasHoy = (float) $vigentes()->whereDate('fecha', $hoy)->sum('total');
        $ventasAyer = (float) $vigentes()->whereDate('fecha', (clone $hoy)->subDay())->sum('total');
        $ventasMes = (float) $vigentes()->where('fecha', '>=', $inicioMes)->sum('total');
        $ventasMesAnterior = (float) $vigentes()
            ->where('fecha', '>=', $inicioMesAnterior)->where('fecha', '<', $inicioMes)->sum('total');

        $docsMes = $vigentes()->where('fecha', '>=', $inicioMes)->count();
        $docsMesAnterior = $vigentes()
            ->where('fecha', '>=', $inicioMesAnterior)->where('fecha', '<', $inicioMes)->count();
        $docsHoy = $vigentes()->whereDate('fecha', $hoy)->count();

        $clientesMes = $this->clientesActivos($inicioMes, null);
        $clientesMesAnterior = $this->clientesActivos($inicioMesAnterior, $inicioMes);
        $clientesRegistrados = Customer::where('is_active', true)->count();

        $porCobrar = (float) Sale::where('estado', 'pendiente')
            ->sum(DB::raw('total - monto_cobrado'));

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
                'icon' => 'shopping-cart', 'color' => 'cyan',
                'hint' => $docsHoy.' '.($docsHoy === 1 ? 'comprobante' : 'comprobantes').' emitidos',
            ],
            [
                'key' => 'ingresos_mes', 'label' => 'Ingresos del mes', 'value' => round($ventasMes, 2),
                'format' => 'currency', 'delta' => $this->delta($ventasMes, $ventasMesAnterior),
                'icon' => 'trending-up', 'color' => 'blue',
                'hint' => 'vs. mes anterior',
            ],
            [
                'key' => 'facturas_mes', 'label' => 'Comprobantes del mes', 'value' => $docsMes,
                'format' => 'number', 'delta' => $this->delta($docsMes, $docsMesAnterior),
                'icon' => 'file-text', 'color' => 'purple',
                'hint' => 'por cobrar',
                'hintAmount' => round($porCobrar, 2),
            ],
            [
                'key' => 'clientes_activos', 'label' => 'Clientes activos', 'value' => $clientesMes,
                'format' => 'number', 'delta' => $this->delta($clientesMes, $clientesMesAnterior),
                'icon' => 'users', 'color' => 'amber',
                'hint' => 'de '.$clientesRegistrados.' registrados',
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
            'salesByMonth' => $this->seriesMensual(),
            'salesByCategory' => $this->ventasPorCategoria($cid, $inicioMes),
            'salesByDay' => $this->ventasDiarias(),
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

    /** Clientes distintos con al menos una venta vigente en el rango. */
    private function clientesActivos(Carbon $desde, ?Carbon $hasta): int
    {
        return Sale::where('estado', '!=', 'anulada')
            ->where('fecha', '>=', $desde)
            ->when($hasta, fn ($q) => $q->where('fecha', '<', $hasta))
            ->get(['customer_id', 'cliente_documento', 'cliente_nombre'])
            ->map(fn ($s) => $s->customer_id
                ? 'c'.$s->customer_id
                : 'd'.($s->cliente_documento ?: $s->cliente_nombre))
            ->unique()
            ->count();
    }

    /**
     * Ingresos de los últimos 12 meses y la meta de cada mes
     * (promedio de los 3 meses previos + 10%). Agregado en PHP, portable.
     */
    private function seriesMensual(): array
    {
        $meses = 12;
        $desde = Carbon::now()->startOfMonth()->subMonths($meses - 1);
        // 3 meses extra para poder calcular la meta del primer mes visible
        $desdeConsulta = (clone $desde)->subMonths(3);

        $porMes = Sale::where('estado', '!=', 'anulada')
            ->where('fecha', '>=', $desdeConsulta)
            ->get(['fecha', 'total'])
            ->groupBy(fn ($v) => Carbon::parse($v->fecha)->format('Y-m'))
            ->map(fn ($g) => (float) $g->sum('total'));

        $monto = fn (Carbon $m) => round((float) ($porMes[$m->format('Y-m')] ?? 0), 2);

        $categories = [];
        $ingresos = [];
        $meta = [];

        for ($i = 0; $i < $meses; $i++) {
            $mes = (clone $desde)->addMonths($i);
            $categories[] = self::MESES[(int) $mes->format('n')];
            $ingresos[] = $monto($mes);

            $previos = collect([1, 2, 3])->map(fn ($k) => $monto((clone $mes)->subMonths($k)));
            $meta[] = round($previos->avg() * self::META_CRECIMIENTO, 2);
        }

        return ['categories' => $categories, 'ingresos' => $ingresos, 'meta' => $meta];
    }

    /** Las 3 categorías con más ventas del mes + "Otros" con el resto. */
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

        $top = $rows->take(3);
        $resto = (float) $rows->slice(3)->sum('monto');

        $labels = $top->pluck('categoria')->all();
        $series = $top->map(fn ($r) => round((float) $r->monto, 2))->all();

        if ($resto > 0) {
            $labels[] = 'Otros';
            $series[] = round($resto, 2);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /** Monto y cantidad de comprobantes por día de la semana actual (lun–dom). */
    private function ventasDiarias(): array
    {
        $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $fin = (clone $inicio)->addDays(7);

        $porDia = Sale::where('estado', '!=', 'anulada')
            ->where('fecha', '>=', $inicio)->where('fecha', '<', $fin)
            ->get(['fecha', 'total'])
            ->groupBy(fn ($v) => Carbon::parse($v->fecha)->toDateString());

        return collect(range(0, 6))->map(function ($i) use ($inicio, $porDia) {
            $dia = (clone $inicio)->addDays($i);
            $ventas = $porDia[$dia->toDateString()] ?? collect();

            return [
                'dia' => self::DIAS[$dia->dayOfWeek],
                'fecha' => $dia->toDateString(),
                'monto' => round((float) $ventas->sum('total'), 2),
                'cantidad' => $ventas->count(),
            ];
        })->all();
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
        $limiteVencimiento = Carbon::now()->subDays(self::DIAS_VENCIMIENTO);

        return Sale::orderByDesc('id')->limit(8)->get()
            ->map(fn (Sale $s) => [
                'id' => $s->id,
                'doc' => $s->numero,
                'cliente' => $s->cliente_nombre,
                'tipo' => $s->tipo_comprobante,
                'total' => round((float) $s->total, 2),
                'estado' => $s->estado === 'pendiente' && $s->fecha < $limiteVencimiento
                    ? 'vencida'
                    : $s->estado,
                'fecha' => $s->fecha?->toDateString(),
            ])->all();
    }
}
