import Badge from '@/Components/ui/Badge';
import { buttonClasses } from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Despatch, Paginated } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { Download, FileCheck, Plus, Printer, Receipt, RefreshCw, Send, Truck } from 'lucide-react';

const estadoMeta: Record<string, { label: string; tone: 'emerald' | 'amber' | 'rose' | 'slate' | 'sky' }> = {
    no_enviado: { label: 'No enviado', tone: 'slate' },
    simulado: { label: 'Simulado', tone: 'sky' },
    enviado: { label: 'Enviado (en proceso)', tone: 'amber' },
    aceptado: { label: 'Aceptado', tone: 'emerald' },
    observado: { label: 'Observado', tone: 'amber' },
    rechazado: { label: 'Rechazado', tone: 'rose' },
};

function numero(s: string, c: number): string {
    return `${s}-${String(c).padStart(6, '0')}`;
}

export default function Index({
    guias,
    filters,
}: {
    guias: Paginated<Despatch>;
    filters: Record<string, string | undefined>;
}) {
    return (
        <AuthenticatedLayout>
            <Head title="Guías de remisión" />
            <div className="space-y-5">
                <PageHeader
                    title="Guías de remisión"
                    description="Guía de remisión electrónica (remitente)"
                    breadcrumbs={[{ label: 'Facturación SUNAT' }, { label: 'Guías de remisión' }]}
                    actions={
                        <Link href={route('guias.create')} className={buttonClasses('primary')}>
                            <Plus className="size-4" /> Nueva guía
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput routeName="guias.index" filters={filters} placeholder="Buscar por número o destinatario…" />
                    </div>

                    {guias.data.length === 0 ? (
                        <EmptyState
                            icon={Truck}
                            title="Sin guías"
                            description="Genera guías de remisión para el traslado de tus mercaderías."
                            action={
                                <Link href={route('guias.create')} className={buttonClasses('primary', 'sm')}>
                                    <Plus className="size-4" /> Nueva guía
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Guía</th>
                                        <th className="pb-3 pr-3 font-semibold">Destinatario</th>
                                        <th className="pb-3 pr-3 font-semibold">Traslado</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {guias.data.map((g) => {
                                        const meta = estadoMeta[g.sunat_estado] ?? estadoMeta.no_enviado;
                                        return (
                                            <tr key={g.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3 font-mono text-xs font-semibold">
                                                    {numero(g.serie, g.correlativo)}
                                                </td>
                                                <td className="py-3 pr-3">{g.dest_nombre}</td>
                                                <td className="py-3 pr-3 text-slate-500">{g.des_traslado}</td>
                                                <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                    {new Date(g.fecha_traslado).toLocaleDateString('es-PE')}
                                                </td>
                                                <td className="py-3 pr-3 text-center">
                                                    <Badge tone={meta.tone}>{meta.label}</Badge>
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <a
                                                            href={route('guias.pdf', g.id)}
                                                            target="_blank"
                                                            rel="noopener"
                                                            title="PDF A4"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Printer className="size-4" />
                                                        </a>
                                                        <a
                                                            href={route('guias.pdf', { despatch: g.id, formato: 'ticket' })}
                                                            target="_blank"
                                                            rel="noopener"
                                                            title="Ticket 80mm"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Receipt className="size-4" />
                                                        </a>
                                                        {g.sunat_estado === 'enviado' && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    router.post(route('guias.consultar', g.id), {}, { preserveScroll: true })
                                                                }
                                                                title="Consultar estado en SUNAT (obtener CDR)"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-amber-600 dark:hover:bg-slate-800"
                                                            >
                                                                <RefreshCw className="size-4" />
                                                            </button>
                                                        )}
                                                        {(g.sunat_estado === 'rechazado' ||
                                                            g.sunat_estado === 'no_enviado') && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    router.post(route('guias.emitir', g.id), {}, { preserveScroll: true })
                                                                }
                                                                title="Enviar / reenviar a SUNAT"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                            >
                                                                <Send className="size-4" />
                                                            </button>
                                                        )}
                                                        {g.sunat_xml_path && (
                                                            <a
                                                                href={route('guias.xml', g.id)}
                                                                title="Descargar XML"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                            >
                                                                <Download className="size-4" />
                                                            </a>
                                                        )}
                                                        {g.sunat_cdr_path && (
                                                            <a
                                                                href={route('guias.cdr', g.id)}
                                                                title="Descargar CDR"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-emerald-600 dark:hover:bg-slate-800"
                                                            >
                                                                <FileCheck className="size-4" />
                                                            </a>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={guias.links} from={guias.from} to={guias.to} total={guias.total} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
