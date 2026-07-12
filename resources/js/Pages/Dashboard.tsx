import Card from '@/Components/Card';
import StatCard, { type Stat } from '@/Components/dashboard/StatCard';
import Badge from '@/Components/ui/Badge';
import { useTheme } from '@/hooks/useTheme';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';
import { type ApexOptions } from 'apexcharts';
import { AlertTriangle, Wallet } from 'lucide-react';
import ReactApexChart from 'react-apexcharts';

interface DashboardProps {
    moneda: string;
    stats: Stat[];
    stockBajo: number;
    caja: { abierta: boolean; nombre?: string | null; saldo?: number };
    salesByMonth: {
        categories: string[];
        ventas: number[];
        compras: number[];
    };
    salesByCategory: { labels: string[]; series: number[] };
    topProducts: { name: string; qty: number; amount: number }[];
    recentSales: {
        doc: string;
        cliente: string;
        total: number;
        estado: string;
    }[];
}

const BRAND = '#4f46e5';
const EMERALD = '#10b981';

export default function Dashboard({
    moneda,
    stats,
    stockBajo,
    caja,
    salesByMonth,
    salesByCategory,
    topProducts,
    recentSales,
}: DashboardProps) {
    const { isDark } = useTheme();
    const gridColor = isDark ? '#1e293b' : '#e2e8f0';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    const areaOptions: ApexOptions = {
        chart: {
            type: 'area',
            toolbar: { show: false },
            fontFamily: 'inherit',
            foreColor: textColor,
        },
        colors: [BRAND, EMERALD],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 },
        },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        xaxis: {
            categories: salesByMonth.categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                formatter: (v) =>
                    v >= 1000 ? `${(v / 1000).toFixed(0)}k` : `${v}`,
            },
        },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            y: { formatter: (v) => formatCurrency(v, moneda) },
        },
    };

    const areaSeries = [
        { name: 'Ventas', data: salesByMonth.ventas },
        { name: 'Compras', data: salesByMonth.compras },
    ];

    const donutOptions: ApexOptions = {
        chart: { type: 'donut', fontFamily: 'inherit', foreColor: textColor },
        labels: salesByCategory.labels,
        colors: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: (v) => `${Number(v).toFixed(0)}%`,
        },
        stroke: { width: 2, colors: [isDark ? '#0f172a' : '#fff'] },
        plotOptions: { pie: { donut: { size: '68%' } } },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            y: { formatter: (v) => formatCurrency(v, moneda) },
        },
    };

    const maxAmount = Math.max(...topProducts.map((p) => p.amount), 1);
    const hayCategorias = salesByCategory.series.some((v) => v > 0);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-bold text-slate-800 dark:text-slate-100">
                        Dashboard ejecutivo
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Resumen general del negocio en tiempo real
                    </p>
                </div>
            }
        >
            <Head title="Dashboard" />

            {/* Alertas */}
            {(stockBajo > 0 || caja.abierta) && (
                <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    {stockBajo > 0 && (
                        <Link
                            href={route('inventario.productos.index', { estado: 'activo' })}
                            className="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm transition hover:bg-amber-100 dark:border-amber-900/50 dark:bg-amber-900/20"
                        >
                            <AlertTriangle className="size-5 shrink-0 text-amber-500" />
                            <span className="text-amber-700 dark:text-amber-300">
                                <span className="font-semibold">{stockBajo}</span>{' '}
                                producto(s) con stock bajo o agotado
                            </span>
                        </Link>
                    )}
                    {caja.abierta && (
                        <Link
                            href={route('caja.index')}
                            className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-900/20"
                        >
                            <Wallet className="size-5 shrink-0 text-emerald-500" />
                            <span className="text-emerald-700 dark:text-emerald-300">
                                {caja.nombre} abierta · saldo{' '}
                                <span className="font-semibold">
                                    {formatCurrency(caja.saldo ?? 0, moneda)}
                                </span>
                            </span>
                        </Link>
                    )}
                </div>
            )}

            {/* Tarjetas de métricas */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {stats.map((stat) => (
                    <StatCard key={stat.key} stat={stat} moneda={moneda} />
                ))}
            </div>

            {/* Gráficos */}
            <div className="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card
                    title="Ventas vs. Compras"
                    subtitle="Últimos 6 meses"
                    className="lg:col-span-2"
                >
                    <ReactApexChart
                        options={areaOptions}
                        series={areaSeries}
                        type="area"
                        height={320}
                    />
                </Card>

                <Card title="Ventas por categoría" subtitle="Mes actual">
                    {hayCategorias ? (
                        <ReactApexChart
                            options={donutOptions}
                            series={salesByCategory.series}
                            type="donut"
                            height={320}
                        />
                    ) : (
                        <div className="flex h-[320px] items-center justify-center text-center text-sm text-slate-400">
                            Aún no hay ventas este mes.
                        </div>
                    )}
                </Card>
            </div>

            {/* Tablas */}
            <div className="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card
                    title="Productos más vendidos"
                    subtitle="Por importe del mes"
                >
                    {topProducts.length === 0 ? (
                        <p className="py-8 text-center text-sm text-slate-400">
                            Sin ventas registradas este mes.
                        </p>
                    ) : (
                        <ul className="space-y-4">
                            {topProducts.map((p, i) => (
                                <li key={i}>
                                    <div className="mb-1 flex items-center justify-between text-sm">
                                        <span className="font-medium text-slate-700 dark:text-slate-200">
                                            {i + 1}. {p.name}
                                        </span>
                                        <span className="font-semibold text-slate-800 dark:text-slate-100">
                                            {formatCurrency(p.amount, moneda)}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div
                                                className="h-full rounded-full bg-brand-500"
                                                style={{
                                                    width: `${(p.amount / maxAmount) * 100}%`,
                                                }}
                                            />
                                        </div>
                                        <span className="w-16 text-right text-xs text-slate-400">
                                            {p.qty} und
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card title="Últimas ventas" subtitle="Comprobantes recientes">
                    {recentSales.length === 0 ? (
                        <p className="py-8 text-center text-sm text-slate-400">
                            Aún no se han registrado ventas.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase tracking-wide text-slate-400">
                                        <th className="pb-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 font-semibold">Cliente</th>
                                        <th className="pb-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 text-right font-semibold">Estado</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {recentSales.map((s, i) => (
                                        <tr
                                            key={i}
                                            className="text-slate-700 dark:text-slate-300"
                                        >
                                            <td className="py-3 font-mono text-xs">{s.doc}</td>
                                            <td className="py-3">{s.cliente}</td>
                                            <td className="py-3 text-right font-semibold">
                                                {formatCurrency(s.total, moneda)}
                                            </td>
                                            <td className="py-3 text-right">
                                                <Badge
                                                    tone={
                                                        s.estado === 'Pagada'
                                                            ? 'emerald'
                                                            : s.estado === 'Anulada'
                                                              ? 'rose'
                                                              : 'amber'
                                                    }
                                                >
                                                    {s.estado}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
