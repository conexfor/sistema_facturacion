import Card from '@/Components/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import { TextField } from '@/Components/ui/Form';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated } from '@/types/models';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Ban, Download, FileCode2, Info, RefreshCw, Search, Send, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface FacturaOpt {
    id: number;
    numero: string;
    cliente_nombre: string;
    total: string;
    fecha: string;
}

interface VoidedDoc {
    id: number;
    identificador: string;
    fecha_referencia: string;
    motivo: string;
    ticket: string | null;
    estado: 'pendiente' | 'enviado' | 'aceptado' | 'observado' | 'rechazado' | 'simulado';
    mensaje: string | null;
    xml_path: string | null;
    cdr_path: string | null;
    sale?: { serie: string; correlativo: number; cliente_nombre: string } | null;
}

const estadoTone: Record<string, 'slate' | 'amber' | 'emerald' | 'rose' | 'sky' | 'brand'> = {
    pendiente: 'slate',
    enviado: 'sky',
    aceptado: 'emerald',
    observado: 'amber',
    rechazado: 'rose',
    simulado: 'brand',
};

export default function Index({
    bajas,
    sunatConfigurado,
}: {
    bajas: Paginated<VoidedDoc>;
    sunatConfigurado: boolean;
}) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<FacturaOpt[]>([]);
    const [factura, setFactura] = useState<FacturaOpt | null>(null);
    const [motivo, setMotivo] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [consultando, setConsultando] = useState<number | null>(null);
    const [reenviando, setReenviando] = useState<number | null>(null);

    useEffect(() => {
        if (factura || query.trim() === '') {
            setResults([]);
            return;
        }
        const t = setTimeout(() => {
            axios
                .get(route('bajas.buscar-facturas'), { params: { q: query } })
                .then((r) => setResults(r.data))
                .catch(() => setResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [query, factura]);

    const anular = () => {
        if (!factura || !motivo.trim() || procesando) return;
        setProcesando(true);
        router.post(
            route('bajas.anular'),
            { sale_id: factura.id, motivo },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setFactura(null);
                    setMotivo('');
                    setQuery('');
                },
                onFinish: () => setProcesando(false),
            },
        );
    };

    const consultar = (id: number) => {
        setConsultando(id);
        router.post(route('bajas.consultar', id), {}, { preserveScroll: true, onFinish: () => setConsultando(null) });
    };

    const reenviar = (id: number) => {
        setReenviando(id);
        router.post(route('bajas.reenviar', id), {}, { preserveScroll: true, onFinish: () => setReenviando(null) });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Comunicación de baja" />
            <div className="space-y-5">
                <PageHeader
                    title="Comunicación de baja"
                    description="Anula facturas emitidas por error ante SUNAT"
                    breadcrumbs={[{ label: 'Facturación SUNAT' }, { label: 'Bajas' }]}
                />

                <div className="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-300">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    <p>
                        La comunicación de baja anula <strong>facturas</strong> dentro
                        de los <strong>7 días</strong> de emitidas. Al aceptarse,
                        la venta se anula y el stock se devuelve. (Las boletas se
                        anulan por resumen.)
                        {!sunatConfigurado && (
                            <span className="mt-1 block text-amber-700 dark:text-amber-400">
                                Sin credenciales SUNAT: la baja se aplica en{' '}
                                <strong>modo simulación</strong>.
                            </span>
                        )}
                    </p>
                </div>

                <Card title="Dar de baja una factura">
                    {factura ? (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                <span>
                                    <span className="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {factura.numero} · {factura.cliente_nombre}
                                    </span>
                                    <span className="text-xs text-slate-400">
                                        {formatCurrency(Number(factura.total))} ·{' '}
                                        {new Date(factura.fecha).toLocaleDateString('es-PE')}
                                    </span>
                                </span>
                                <button
                                    onClick={() => setFactura(null)}
                                    className="rounded p-1 text-slate-400 hover:text-rose-600"
                                >
                                    <X className="size-4" />
                                </button>
                            </div>
                            <TextField
                                label="Motivo de la baja"
                                required
                                value={motivo}
                                onChange={(e) => setMotivo(e.target.value)}
                                placeholder="Ej.: Error en los datos del cliente"
                            />
                            <Button onClick={anular} disabled={!motivo.trim() || procesando} variant="danger">
                                <Ban className="size-4" /> Comunicar baja
                            </Button>
                        </div>
                    ) : (
                        <div className="relative max-w-xl">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Buscar factura por número o cliente…"
                                className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                            />
                            {results.length > 0 && (
                                <div className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                    {results.map((f) => (
                                        <button
                                            key={f.id}
                                            onClick={() => {
                                                setFactura(f);
                                                setResults([]);
                                            }}
                                            className="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                        >
                                            <span>
                                                <span className="block font-medium text-slate-800 dark:text-slate-100">
                                                    {f.numero}
                                                </span>
                                                <span className="text-xs text-slate-400">
                                                    {f.cliente_nombre}
                                                </span>
                                            </span>
                                            <span className="text-xs font-semibold text-slate-500">
                                                {formatCurrency(Number(f.total))}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </Card>

                <Card title="Comunicaciones de baja">
                    {bajas.data.length === 0 ? (
                        <EmptyState icon={Ban} title="Sin bajas" description="No has comunicado bajas todavía." />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Identificador</th>
                                        <th className="pb-3 pr-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 pr-3 font-semibold">Motivo</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {bajas.data.map((b) => (
                                        <tr key={b.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs font-semibold">
                                                {b.identificador}
                                            </td>
                                            <td className="py-3 pr-3 font-mono text-xs">
                                                {b.sale
                                                    ? `${b.sale.serie}-${String(b.sale.correlativo).padStart(6, '0')}`
                                                    : '—'}
                                            </td>
                                            <td className="max-w-xs py-3 pr-3 text-xs text-slate-400">
                                                <span className="line-clamp-2">{b.motivo}</span>
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                <Badge tone={estadoTone[b.estado]}>{b.estado}</Badge>
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    {(b.estado === 'enviado' || b.estado === 'observado') && (
                                                        <button
                                                            onClick={() => consultar(b.id)}
                                                            disabled={consultando === b.id}
                                                            title="Consultar estado"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <RefreshCw className={`size-4 ${consultando === b.id ? 'animate-spin' : ''}`} />
                                                        </button>
                                                    )}
                                                    {b.estado === 'rechazado' && (
                                                        <button
                                                            onClick={() => reenviar(b.id)}
                                                            disabled={reenviando === b.id}
                                                            title="Reenviar a SUNAT"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 disabled:opacity-50 dark:hover:bg-slate-800"
                                                        >
                                                            <Send className={`size-4 ${reenviando === b.id ? 'animate-pulse' : ''}`} />
                                                        </button>
                                                    )}
                                                    {b.xml_path && (
                                                        <a
                                                            href={route('bajas.xml', b.id)}
                                                            title="XML"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <FileCode2 className="size-4" />
                                                        </a>
                                                    )}
                                                    {b.cdr_path && (
                                                        <a
                                                            href={route('bajas.cdr', b.id)}
                                                            title="CDR"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-emerald-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Download className="size-4" />
                                                        </a>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={bajas.links} from={bajas.from} to={bajas.to} total={bajas.total} />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
