import Badge from '@/Components/ui/Badge';
import { buttonClasses } from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated, Sale } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Plus, Receipt } from 'lucide-react';

const tipoLabel: Record<string, string> = {
    boleta: 'Boleta',
    factura: 'Factura',
    nota_venta: 'Nota venta',
};

const estadoTone: Record<string, 'emerald' | 'amber' | 'rose'> = {
    pagada: 'emerald',
    pendiente: 'amber',
    anulada: 'rose',
};

function numero(s: Sale): string {
    return `${s.serie}-${String(s.correlativo).padStart(6, '0')}`;
}

export default function Index({
    sales,
    filters,
    totalPagina,
    moneda,
}: {
    sales: Paginated<Sale>;
    filters: Record<string, string | undefined>;
    totalPagina: number;
    moneda: string;
}) {
    const applyFilter = (key: string, value: string) => {
        router.get(
            route('ventas.index'),
            { ...filters, [key]: value || undefined, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Ventas" />
            <div className="space-y-5">
                <PageHeader
                    title="Historial de ventas"
                    description="Comprobantes emitidos"
                    breadcrumbs={[{ label: 'Ventas' }, { label: 'Historial' }]}
                    actions={
                        <Link href={route('ventas.pos')} className={buttonClasses('primary')}>
                            <Plus className="size-4" /> Nueva venta (POS)
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="lg:col-span-2">
                            <SearchInput
                                routeName="ventas.index"
                                filters={filters}
                                placeholder="Buscar por número o cliente…"
                            />
                        </div>
                        <SelectField
                            placeholder="Todos los comprobantes"
                            value={filters.tipo_comprobante ?? ''}
                            onChange={(e) => applyFilter('tipo_comprobante', e.target.value)}
                            options={Object.entries(tipoLabel).map(([value, label]) => ({
                                value,
                                label,
                            }))}
                        />
                        <TextField
                            type="date"
                            value={filters.desde ?? ''}
                            onChange={(e) => applyFilter('desde', e.target.value)}
                        />
                        <TextField
                            type="date"
                            value={filters.hasta ?? ''}
                            onChange={(e) => applyFilter('hasta', e.target.value)}
                        />
                    </div>

                    {sales.data.length === 0 ? (
                        <EmptyState
                            icon={Receipt}
                            title="Sin ventas"
                            description="Registra tu primera venta desde el POS."
                            action={
                                <Link href={route('ventas.pos')} className={buttonClasses('primary', 'sm')}>
                                    <Plus className="size-4" /> Ir al POS
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 font-semibold">Cliente</th>
                                        <th className="pb-3 pr-3 font-semibold">Pago</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Ver</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {sales.data.map((s) => (
                                        <tr key={s.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3">
                                                <span className="font-mono text-xs font-semibold">
                                                    {numero(s)}
                                                </span>
                                                <span className="ml-2 text-xs text-slate-400">
                                                    {tipoLabel[s.tipo_comprobante]}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(s.fecha).toLocaleString('es-PE', {
                                                    dateStyle: 'short',
                                                    timeStyle: 'short',
                                                })}
                                            </td>
                                            <td className="py-3 pr-3">{s.cliente_nombre}</td>
                                            <td className="py-3 pr-3 capitalize text-slate-500">
                                                {s.metodo_pago}
                                            </td>
                                            <td className="py-3 pr-3 text-right font-semibold">
                                                {formatCurrency(Number(s.total), moneda)}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                <Badge tone={estadoTone[s.estado]}>
                                                    {s.estado}
                                                </Badge>
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={route('ventas.show', s.id)}
                                                    className="inline-flex rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                >
                                                    <Eye className="size-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="flex items-center justify-between gap-3 pt-3 text-sm">
                        <span className="text-slate-500 dark:text-slate-400">
                            Total en esta página:{' '}
                            <span className="font-bold text-slate-800 dark:text-slate-100">
                                {formatCurrency(totalPagina, moneda)}
                            </span>
                        </span>
                    </div>

                    <Pagination
                        links={sales.links}
                        from={sales.from}
                        to={sales.to}
                        total={sales.total}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
