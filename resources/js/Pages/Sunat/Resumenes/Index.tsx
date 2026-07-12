import Card from '@/Components/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginated } from '@/types/models';
import { Head, router } from '@inertiajs/react';
import {
    CalendarCheck,
    Download,
    FileCode2,
    Info,
    RefreshCw,
} from 'lucide-react';
import { useState } from 'react';

interface BoletaSummary {
    id: number;
    identificador: string;
    fecha_referencia: string;
    fecha_envio: string;
    correlativo: number;
    ticket: string | null;
    estado: 'pendiente' | 'enviado' | 'aceptado' | 'observado' | 'rechazado' | 'simulado';
    total_boletas: number;
    mensaje: string | null;
    xml_path: string | null;
    cdr_path: string | null;
    user?: { id: number; name: string } | null;
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
    resumenes,
    fecha,
    pendientes,
    sunatConfigurado,
}: {
    resumenes: Paginated<BoletaSummary>;
    fecha: string;
    pendientes: number;
    sunatConfigurado: boolean;
}) {
    const [fechaSel, setFechaSel] = useState(fecha);
    const [procesando, setProcesando] = useState(false);
    const [consultando, setConsultando] = useState<number | null>(null);

    const cambiarFecha = (value: string) => {
        setFechaSel(value);
        router.get(
            route('resumenes.index'),
            { fecha: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const generar = () => {
        if (pendientes === 0 || procesando) return;
        setProcesando(true);
        router.post(
            route('resumenes.generar'),
            { fecha: fechaSel },
            { preserveScroll: true, onFinish: () => setProcesando(false) },
        );
    };

    const consultar = (id: number) => {
        setConsultando(id);
        router.post(
            route('resumenes.consultar', id),
            {},
            { preserveScroll: true, onFinish: () => setConsultando(null) },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Resumen diario de boletas" />
            <div className="space-y-5">
                <PageHeader
                    title="Resumen diario de boletas"
                    description="Comunicación de boletas a SUNAT (RC)"
                    breadcrumbs={[{ label: 'Facturación SUNAT' }, { label: 'Resúmenes' }]}
                />

                <div className="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-300">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    <p>
                        En producción, SUNAT no acepta boletas enviadas
                        individualmente: deben comunicarse en un{' '}
                        <strong>resumen diario</strong>. Genera el resumen del día,
                        envíalo y luego <strong>consulta el estado</strong> para
                        obtener el CDR.
                        {!sunatConfigurado && (
                            <span className="mt-1 block text-amber-700 dark:text-amber-400">
                                Sin credenciales SUNAT configuradas: los envíos se
                                harán en <strong>modo simulación</strong>.
                            </span>
                        )}
                    </p>
                </div>

                <Card title="Generar resumen">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Fecha de las boletas
                            </label>
                            <input
                                type="date"
                                value={fechaSel}
                                max={new Date().toISOString().slice(0, 10)}
                                onChange={(e) => cambiarFecha(e.target.value)}
                                className="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Boletas pendientes en esa fecha:{' '}
                                <span className="font-bold text-slate-800 dark:text-slate-100">
                                    {pendientes}
                                </span>
                            </p>
                        </div>
                        <Button onClick={generar} disabled={pendientes === 0 || procesando}>
                            <CalendarCheck className="size-4" /> Generar y enviar
                        </Button>
                    </div>
                </Card>

                <Card title="Resúmenes enviados">
                    {resumenes.data.length === 0 ? (
                        <EmptyState
                            icon={CalendarCheck}
                            title="Sin resúmenes"
                            description="Genera el primer resumen diario de boletas."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Identificador</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha boletas</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Boletas</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 pr-3 font-semibold">Mensaje</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {resumenes.data.map((r) => (
                                        <tr key={r.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs font-semibold">
                                                {r.identificador}
                                            </td>
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(r.fecha_referencia).toLocaleDateString('es-PE')}
                                            </td>
                                            <td className="py-3 pr-3 text-center">{r.total_boletas}</td>
                                            <td className="py-3 pr-3 text-center">
                                                <Badge tone={estadoTone[r.estado]}>{r.estado}</Badge>
                                            </td>
                                            <td className="max-w-xs py-3 pr-3 text-xs text-slate-400">
                                                <span className="line-clamp-2">{r.mensaje ?? '—'}</span>
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    {(r.estado === 'enviado' || r.estado === 'observado') && (
                                                        <button
                                                            onClick={() => consultar(r.id)}
                                                            disabled={consultando === r.id}
                                                            title="Consultar estado"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <RefreshCw
                                                                className={`size-4 ${consultando === r.id ? 'animate-spin' : ''}`}
                                                            />
                                                        </button>
                                                    )}
                                                    {r.xml_path && (
                                                        <a
                                                            href={route('resumenes.xml', r.id)}
                                                            title="Descargar XML"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <FileCode2 className="size-4" />
                                                        </a>
                                                    )}
                                                    {r.cdr_path && (
                                                        <a
                                                            href={route('resumenes.cdr', r.id)}
                                                            title="Descargar CDR"
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
                    <Pagination
                        links={resumenes.links}
                        from={resumenes.from}
                        to={resumenes.to}
                        total={resumenes.total}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
