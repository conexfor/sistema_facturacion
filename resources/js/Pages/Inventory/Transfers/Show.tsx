import Card from '@/Components/Card';
import { buttonClasses } from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber } from '@/lib/format';
import { Transfer } from '@/types/models';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';

export default function Show({ transfer }: { transfer: Transfer }) {
    return (
        <AuthenticatedLayout>
            <Head title={`Transferencia #${transfer.id}`} />
            <div className="space-y-5">
                <PageHeader
                    title={`Transferencia #${transfer.id}`}
                    description={new Date(transfer.fecha).toLocaleString('es-PE')}
                    breadcrumbs={[
                        { label: 'Inventario' },
                        { label: 'Transferencias', href: route('inventario.transferencias.index') },
                        { label: `#${transfer.id}` },
                    ]}
                    actions={
                        <Link href={route('inventario.transferencias.index')} className={buttonClasses('secondary')}>
                            <ArrowLeft className="size-4" /> Volver
                        </Link>
                    }
                />

                <Card>
                    <div className="flex flex-wrap items-center gap-3 text-sm">
                        <span className="rounded-lg bg-slate-100 px-3 py-2 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {transfer.origin?.nombre}
                        </span>
                        <ArrowRight className="size-4 text-brand-500" />
                        <span className="rounded-lg bg-brand-50 px-3 py-2 font-medium text-brand-700 dark:bg-brand-900/20 dark:text-brand-300">
                            {transfer.dest?.nombre}
                        </span>
                        <span className="ml-auto text-xs text-slate-400">
                            Responsable: {transfer.user?.name ?? '—'}
                        </span>
                    </div>
                    {transfer.observacion && (
                        <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">
                            {transfer.observacion}
                        </p>
                    )}
                </Card>

                <Card title="Productos transferidos">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                    <th className="pb-3 pr-3 font-semibold">Producto</th>
                                    <th className="pb-3 text-right font-semibold">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {transfer.items?.map((it) => (
                                    <tr key={it.id} className="text-slate-700 dark:text-slate-300">
                                        <td className="py-3 pr-3">
                                            <div className="font-medium text-slate-800 dark:text-slate-100">
                                                {it.descripcion}
                                            </div>
                                            {it.product && (
                                                <div className="font-mono text-xs text-slate-400">
                                                    {it.product.codigo}
                                                </div>
                                            )}
                                        </td>
                                        <td className="py-3 text-right font-semibold">
                                            {formatNumber(Number(it.cantidad))}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
