import Badge from '@/Components/ui/Badge';
import { buttonClasses } from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated, Purchase } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Plus, ShoppingBag } from 'lucide-react';

const estadoTone: Record<string, 'emerald' | 'amber' | 'rose'> = {
    pagada: 'emerald',
    parcial: 'amber',
    pendiente: 'rose',
};

export default function Index({
    purchases,
    filters,
    moneda,
}: {
    purchases: Paginated<Purchase>;
    filters: Record<string, string | undefined>;
    moneda: string;
}) {
    const applyFilter = (key: string, value: string) => {
        router.get(
            route('compras.index'),
            { ...filters, [key]: value || undefined, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Compras" />
            <div className="space-y-5">
                <PageHeader
                    title="Compras"
                    description="Mercadería ingresada al inventario"
                    breadcrumbs={[{ label: 'Compras' }, { label: 'Historial' }]}
                    actions={
                        <Link href={route('compras.create')} className={buttonClasses('primary')}>
                            <Plus className="size-4" /> Nueva compra
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="lg:col-span-2">
                            <SearchInput
                                routeName="compras.index"
                                filters={filters}
                                placeholder="Buscar por número o proveedor…"
                            />
                        </div>
                        <SelectField
                            placeholder="Todos los estados"
                            value={filters.estado_pago ?? ''}
                            onChange={(e) => applyFilter('estado_pago', e.target.value)}
                            options={[
                                { value: 'pagada', label: 'Pagadas' },
                                { value: 'parcial', label: 'Parciales' },
                                { value: 'pendiente', label: 'Pendientes' },
                            ]}
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

                    {purchases.data.length === 0 ? (
                        <EmptyState
                            icon={ShoppingBag}
                            title="Sin compras"
                            description="Registra tu primera compra para ingresar stock."
                            action={
                                <Link href={route('compras.create')} className={buttonClasses('primary', 'sm')}>
                                    <Plus className="size-4" /> Nueva compra
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 pr-3 font-semibold">Proveedor</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Ver</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {purchases.data.map((p) => (
                                        <tr key={p.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3">
                                                <span className="font-mono text-xs font-semibold">
                                                    {[p.serie, p.numero].filter(Boolean).join('-') || '—'}
                                                </span>
                                                <span className="ml-2 text-xs capitalize text-slate-400">
                                                    {p.tipo_comprobante}
                                                </span>
                                            </td>
                                            <td className="py-3 pr-3">
                                                {p.supplier?.razon_social ?? 'Sin proveedor'}
                                            </td>
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(p.fecha).toLocaleDateString('es-PE')}
                                            </td>
                                            <td className="py-3 pr-3 text-right font-semibold">
                                                {formatCurrency(Number(p.total), moneda)}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                <Badge tone={estadoTone[p.estado_pago]}>
                                                    {p.estado_pago}
                                                </Badge>
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={route('compras.show', p.id)}
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

                    <Pagination
                        links={purchases.links}
                        from={purchases.from}
                        to={purchases.to}
                        total={purchases.total}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
