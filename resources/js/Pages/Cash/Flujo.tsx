import Card from '@/Components/Card';
import { TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import { useTheme } from '@/hooks/useTheme';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import { type ApexOptions } from 'apexcharts';
import { ArrowDownCircle, ArrowUpCircle, Scale } from 'lucide-react';
import ReactApexChart from 'react-apexcharts';

interface CatRow {
    categoria: string;
    ingresos: number;
    egresos: number;
}

const categoriaLabel: Record<string, string> = {
    venta: 'Ventas',
    cobro: 'Cobros',
    pago_compra: 'Pagos a compras',
    gasto: 'Gastos',
    retiro: 'Retiros',
    deposito: 'Depósitos',
    ajuste: 'Ajustes',
    otro: 'Otros',
};

export default function Flujo({
    filters,
    ingresos,
    egresos,
    neto,
    porCategoria,
    moneda,
}: {
    filters: { desde: string; hasta: string };
    ingresos: number;
    egresos: number;
    neto: number;
    porCategoria: CatRow[];
    moneda: string;
}) {
    const { isDark } = useTheme();

    const apply = (key: string, value: string) =>
        router.get(
            route('caja.flujo'),
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const options: ApexOptions = {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', foreColor: isDark ? '#94a3b8' : '#64748b' },
        colors: ['#10b981', '#f43f5e'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: isDark ? '#1e293b' : '#e2e8f0', strokeDashArray: 4 },
        xaxis: {
            categories: porCategoria.map((c) => categoriaLabel[c.categoria] ?? c.categoria),
        },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: (v) => formatCurrency(v, moneda) } },
    };

    const series = [
        { name: 'Ingresos', data: porCategoria.map((c) => c.ingresos) },
        { name: 'Egresos', data: porCategoria.map((c) => c.egresos) },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Flujo de caja" />
            <div className="space-y-5">
                <PageHeader
                    title="Flujo de caja"
                    description="Ingresos y egresos de efectivo por periodo"
                    breadcrumbs={[{ label: 'Caja y Finanzas' }, { label: 'Flujo de caja' }]}
                    actions={
                        <div className="flex items-end gap-2">
                            <TextField
                                label="Desde"
                                type="date"
                                value={filters.desde}
                                onChange={(e) => apply('desde', e.target.value)}
                            />
                            <TextField
                                label="Hasta"
                                type="date"
                                value={filters.hasta}
                                onChange={(e) => apply('hasta', e.target.value)}
                            />
                        </div>
                    }
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card>
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30">
                                <ArrowUpCircle className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Ingresos</p>
                                <p className="text-xl font-bold text-slate-800 dark:text-slate-100">
                                    {formatCurrency(ingresos, moneda)}
                                </p>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-900/30">
                                <ArrowDownCircle className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Egresos</p>
                                <p className="text-xl font-bold text-slate-800 dark:text-slate-100">
                                    {formatCurrency(egresos, moneda)}
                                </p>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30">
                                <Scale className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Flujo neto</p>
                                <p className={`text-xl font-bold ${neto >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                    {formatCurrency(neto, moneda)}
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                <Card title="Por categoría">
                    {porCategoria.length === 0 ? (
                        <p className="py-8 text-center text-sm text-slate-400">
                            No hay movimientos en el periodo seleccionado.
                        </p>
                    ) : (
                        <ReactApexChart options={options} series={series} type="bar" height={320} />
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
