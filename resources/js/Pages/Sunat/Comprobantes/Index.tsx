import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated, Sale } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import {
    Download,
    Eye,
    FileCheck,
    Info,
    Send,
    ScrollText,
} from 'lucide-react';
import { useState } from 'react';

const estadoMeta: Record<string, { label: string; tone: 'emerald' | 'amber' | 'rose' | 'slate' | 'sky' }> = {
    no_enviado: { label: 'No enviado', tone: 'slate' },
    simulado: { label: 'Simulado', tone: 'sky' },
    aceptado: { label: 'Aceptado', tone: 'emerald' },
    observado: { label: 'Observado', tone: 'amber' },
    rechazado: { label: 'Rechazado', tone: 'rose' },
    anulado: { label: 'Anulado', tone: 'slate' },
};

function numero(s: Sale): string {
    return `${s.serie}-${String(s.correlativo).padStart(6, '0')}`;
}

export default function Index({
    comprobantes,
    filters,
    sunatConfigurado,
    moneda,
}: {
    comprobantes: Paginated<Sale>;
    filters: Record<string, string | undefined>;
    sunatConfigurado: boolean;
    moneda: string;
}) {
    const [emitiendo, setEmitiendo] = useState<number | null>(null);

    const emitir = (s: Sale) => {
        setEmitiendo(s.id);
        router.post(route('comprobantes.emitir', s.id), {}, {
            preserveScroll: true,
            onFinish: () => setEmitiendo(null),
        });
    };

    const applyFilter = (key: string, value: string) =>
        router.get(
            route('comprobantes.index'),
            { ...filters, [key]: value || undefined, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    return (
        <AuthenticatedLayout>
            <Head title="Comprobantes electrónicos" />
            <div className="space-y-5">
                <PageHeader
                    title="Comprobantes electrónicos"
                    description="Emisión de boletas y facturas a SUNAT"
                    breadcrumbs={[{ label: 'Facturación SUNAT' }, { label: 'Comprobantes' }]}
                />

                {!sunatConfigurado && (
                    <div className="flex items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm dark:border-sky-900/50 dark:bg-sky-900/20">
                        <Info className="size-5 shrink-0 text-sky-500" />
                        <span className="text-sky-700 dark:text-sky-300">
                            Estás en <strong>modo simulación</strong>: los comprobantes se generan y firman pero no se envían a SUNAT.{' '}
                            <Link href={route('settings.sunat.edit')} className="font-semibold underline">
                                Configura tu certificado y clave SOL
                            </Link>{' '}
                            para emitir realmente.
                        </span>
                    </div>
                )}

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <SearchInput
                                routeName="comprobantes.index"
                                filters={filters}
                                placeholder="Buscar por número o cliente…"
                            />
                        </div>
                        <SelectField
                            placeholder="Todos los estados"
                            value={filters.sunat_estado ?? ''}
                            onChange={(e) => applyFilter('sunat_estado', e.target.value)}
                            options={Object.entries(estadoMeta).map(([value, m]) => ({
                                value,
                                label: m.label,
                            }))}
                        />
                    </div>

                    {comprobantes.data.length === 0 ? (
                        <EmptyState
                            icon={ScrollText}
                            title="Sin comprobantes"
                            description="Las boletas y facturas que registres aparecerán aquí para su emisión."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 pr-3 font-semibold">Cliente</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado SUNAT</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {comprobantes.data.map((s) => {
                                        const meta = estadoMeta[s.sunat_estado ?? 'no_enviado'] ?? estadoMeta.no_enviado;
                                        const emitido = ['aceptado', 'simulado', 'observado'].includes(
                                            s.sunat_estado ?? '',
                                        );
                                        return (
                                            <tr key={s.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3">
                                                    <span className="font-mono text-xs font-semibold">
                                                        {numero(s)}
                                                    </span>
                                                    <span className="ml-2 text-xs capitalize text-slate-400">
                                                        {s.tipo_comprobante}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-3">{s.cliente_nombre}</td>
                                                <td className="py-3 pr-3 text-right font-semibold">
                                                    {formatCurrency(Number(s.total), moneda)}
                                                </td>
                                                <td className="py-3 pr-3 text-center">
                                                    <Badge tone={meta.tone}>{meta.label}</Badge>
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {s.sunat_estado !== 'aceptado' && (
                                                            <Button
                                                                size="sm"
                                                                onClick={() => emitir(s)}
                                                                disabled={emitiendo === s.id}
                                                            >
                                                                <Send className="size-3.5" />
                                                                {emitido ? 'Reenviar' : 'Emitir'}
                                                            </Button>
                                                        )}
                                                        {s.sunat_xml_path && (
                                                            <a
                                                                href={route('comprobantes.xml', s.id)}
                                                                title="Descargar XML"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                            >
                                                                <Download className="size-4" />
                                                            </a>
                                                        )}
                                                        {s.sunat_cdr_path && (
                                                            <a
                                                                href={route('comprobantes.cdr', s.id)}
                                                                title="Descargar CDR"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-emerald-600 dark:hover:bg-slate-800"
                                                            >
                                                                <FileCheck className="size-4" />
                                                            </a>
                                                        )}
                                                        <Link
                                                            href={route('ventas.show', s.id)}
                                                            title="Ver venta"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination
                        links={comprobantes.links}
                        from={comprobantes.from}
                        to={comprobantes.to}
                        total={comprobantes.total}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
