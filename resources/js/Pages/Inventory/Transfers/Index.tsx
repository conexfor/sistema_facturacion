import { buttonClasses } from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginated, Transfer } from '@/types/models';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftRight, ArrowRight, Eye, Plus } from 'lucide-react';

export default function Index({ transfers }: { transfers: Paginated<Transfer> }) {
    return (
        <AuthenticatedLayout>
            <Head title="Transferencias" />
            <div className="space-y-5">
                <PageHeader
                    title="Transferencias"
                    description="Movimientos de stock entre almacenes"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Transferencias' }]}
                    actions={
                        <Link href={route('inventario.transferencias.create')} className={buttonClasses('primary')}>
                            <Plus className="size-4" /> Nueva transferencia
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    {transfers.data.length === 0 ? (
                        <EmptyState
                            icon={ArrowLeftRight}
                            title="Sin transferencias"
                            description="Mueve productos de un almacén a otro."
                            action={
                                <Link href={route('inventario.transferencias.create')} className={buttonClasses('primary', 'sm')}>
                                    <Plus className="size-4" /> Nueva transferencia
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 font-semibold">Movimiento</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Ítems</th>
                                        <th className="pb-3 pr-3 font-semibold">Responsable</th>
                                        <th className="pb-3 text-right font-semibold">Ver</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {transfers.data.map((t) => (
                                        <tr key={t.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(t.fecha).toLocaleString('es-PE', {
                                                    dateStyle: 'short',
                                                    timeStyle: 'short',
                                                })}
                                            </td>
                                            <td className="py-3 pr-3">
                                                <span className="inline-flex items-center gap-2">
                                                    <span className="font-medium text-slate-700 dark:text-slate-200">
                                                        {t.origin?.nombre}
                                                    </span>
                                                    <ArrowRight className="size-3.5 text-slate-400" />
                                                    <span className="font-medium text-slate-700 dark:text-slate-200">
                                                        {t.dest?.nombre}
                                                    </span>
                                                </span>
                                            </td>
                                            <td className="py-3 pr-3 text-center">{t.items_count ?? 0}</td>
                                            <td className="py-3 pr-3 text-slate-500">{t.user?.name ?? '—'}</td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={route('inventario.transferencias.show', t.id)}
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
                        links={transfers.links}
                        from={transfers.from}
                        to={transfers.to}
                        total={transfers.total}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
