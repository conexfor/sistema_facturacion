import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import { SelectField, TextareaField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency, formatNumber } from '@/lib/format';
import { estadoTone, fechaCorta } from '@/lib/trips';
import { Trip, TripEstado } from '@/types/models';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { AlertTriangle, ArrowLeft, CheckCircle2, Play, Printer, Search, Trash2, XCircle } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface VehiculoOpt {
    id: number;
    placa: string;
    marca: string | null;
    modelo: string | null;
    capacidad_kg: string | null;
}
interface ConductorOpt {
    id: number;
    nombres: string;
    apellidos: string;
}
interface GuiaOpt {
    id: number;
    numero: string;
    fecha_traslado: string;
    dest_nombre: string;
    llegada_direccion: string;
    peso_total: number;
    vehiculo_placa: string | null;
}

const accionMeta: Record<string, { label: string; variant: 'primary' | 'secondary' | 'danger'; icon: typeof Play }> = {
    en_ruta: { label: 'Iniciar ruta', variant: 'primary', icon: Play },
    entregado: { label: 'Marcar entregado', variant: 'primary', icon: CheckCircle2 },
    incidencia: { label: 'Reportar incidencia', variant: 'secondary', icon: AlertTriangle },
    cancelado: { label: 'Cancelar viaje', variant: 'danger', icon: XCircle },
};

function numeroGuia(serie: string, correlativo: number): string {
    return `${serie}-${String(correlativo).padStart(6, '0')}`;
}

function Dato({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <dt className="text-xs uppercase tracking-wide text-slate-400">{label}</dt>
            <dd className="mt-0.5 text-sm text-slate-800 dark:text-slate-100">{children}</dd>
        </div>
    );
}

export default function Show({
    trip,
    estados,
    transiciones,
    editable,
    vehiculos,
    conductores,
}: {
    trip: Trip;
    estados: Record<TripEstado, string>;
    transiciones: TripEstado[];
    editable: boolean;
    vehiculos: VehiculoOpt[];
    conductores: ConductorOpt[];
}) {
    const { company, permissions } = usePage().props.auth;
    const moneda = company?.moneda ?? 'PEN';
    const verGuias = permissions.includes('ventas.gestionar');
    const cerrado = trip.estado === 'entregado' || trip.estado === 'cancelado';
    const guias = trip.despatches ?? [];
    const pesoTotal = guias.reduce((a, g) => a + Number(g.peso_total ?? 0), 0);
    const capacidad = trip.vehicle?.capacidad_kg ? Number(trip.vehicle.capacidad_kg) : null;

    // ---- Datos y costos ----
    const form = useForm({
        fecha: trip.fecha.slice(0, 10),
        vehicle_id: trip.vehicle_id ? String(trip.vehicle_id) : '',
        driver_id: trip.driver_id ? String(trip.driver_id) : '',
        origen: trip.origen ?? '',
        destino: trip.destino ?? '',
        km_inicial: trip.km_inicial ?? '',
        km_final: trip.km_final ?? '',
        costo_combustible: trip.costo_combustible ?? '0',
        costo_peajes: trip.costo_peajes ?? '0',
        costo_otros: trip.costo_otros ?? '0',
        observacion: trip.observacion ?? '',
    });
    const guardar = (e: FormEvent) => {
        e.preventDefault();
        form.put(route('transporte.viajes.update', trip.id), { preserveScroll: true });
    };

    // Opciones: incluye el vehículo/conductor actual aunque esté inactivo.
    const vehiculoOpts = [
        ...(trip.vehicle && !vehiculos.some((v) => v.id === trip.vehicle!.id) ? [trip.vehicle] : []),
        ...vehiculos,
    ].map((v) => ({ value: String(v.id), label: [v.placa, v.marca, v.modelo].filter(Boolean).join(' · ') }));
    const conductorOpts = [
        ...(trip.driver && !conductores.some((c) => c.id === trip.driver!.id) ? [trip.driver] : []),
        ...conductores,
    ].map((c) => ({ value: String(c.id), label: `${c.apellidos}, ${c.nombres}` }));

    // ---- Cambio de estado ----
    const [accion, setAccion] = useState<TripEstado | null>(null);
    const estadoForm = useForm({ estado: '', km_inicial: '', km_final: '', incidencia: '' });
    const abrirAccion = (estado: TripEstado) => {
        estadoForm.setData({
            estado,
            km_inicial: trip.km_inicial ?? '',
            km_final: trip.km_final ?? '',
            incidencia: '',
        });
        estadoForm.clearErrors();
        setAccion(estado);
    };
    const confirmarAccion = (e?: FormEvent) => {
        e?.preventDefault();
        estadoForm.patch(route('transporte.viajes.estado', trip.id), {
            preserveScroll: true,
            onSuccess: () => setAccion(null),
        });
    };

    // ---- Guías ----
    const [gQuery, setGQuery] = useState('');
    const [gResults, setGResults] = useState<GuiaOpt[]>([]);
    const [showG, setShowG] = useState(false);
    const [toRemove, setToRemove] = useState<number | null>(null);

    useEffect(() => {
        if (!showG) return;
        const t = setTimeout(() => {
            axios
                .get(route('transporte.viajes.buscar-guias'), { params: { q: gQuery } })
                .then((r) => setGResults(r.data))
                .catch(() => setGResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [gQuery, showG]);

    const asignar = (g: GuiaOpt) => {
        setShowG(false);
        setGQuery('');
        router.post(
            route('transporte.viajes.guias.store', trip.id),
            { despatch_ids: [g.id] },
            { preserveScroll: true },
        );
    };
    const quitar = () => {
        if (toRemove === null) return;
        router.delete(route('transporte.viajes.guias.destroy', [trip.id, toRemove]), {
            preserveScroll: true,
            onFinish: () => setToRemove(null),
        });
    };

    const costoTotal =
        Number(form.data.costo_combustible || 0) + Number(form.data.costo_peajes || 0) + Number(form.data.costo_otros || 0);

    return (
        <AuthenticatedLayout>
            <Head title={`Viaje ${trip.numero}`} />
            <div className="space-y-5">
                <PageHeader
                    title={`Viaje ${trip.numero}`}
                    description={`${fechaCorta(trip.fecha)}${trip.destino ? ` · ${trip.origen ?? '—'} → ${trip.destino}` : ''}`}
                    breadcrumbs={[
                        { label: 'Transporte' },
                        { label: 'Viajes', href: route('transporte.viajes.index') },
                        { label: trip.numero },
                    ]}
                    actions={
                        <>
                            <Link href={route('transporte.viajes.index')} className={buttonClasses('secondary')}>
                                <ArrowLeft className="size-4" /> Volver
                            </Link>
                            {transiciones.map((t) => {
                                const meta = accionMeta[t];
                                const label = t === 'en_ruta' && trip.estado === 'incidencia' ? 'Retomar ruta' : meta.label;
                                const Icon = meta.icon;
                                return (
                                    <Button key={t} variant={meta.variant} onClick={() => abrirAccion(t)}>
                                        <Icon className="size-4" /> {label}
                                    </Button>
                                );
                            })}
                        </>
                    }
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card
                            title="Guías transportadas"
                            subtitle={`${guias.length} guía(s) · ${formatNumber(pesoTotal)} kg${
                                capacidad ? ` de ${formatNumber(capacidad)} kg` : ''
                            }`}
                        >
                            {capacidad !== null && pesoTotal > capacidad && (
                                <p className="mb-3 flex items-center gap-1.5 text-xs text-amber-600">
                                    <AlertTriangle className="size-3.5" /> La carga supera la capacidad del vehículo.
                                </p>
                            )}
                            {editable && (
                                <div className="relative mb-4">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        value={gQuery}
                                        onFocus={() => setShowG(true)}
                                        onBlur={() => setTimeout(() => setShowG(false), 150)}
                                        onChange={(e) => setGQuery(e.target.value)}
                                        placeholder="Agregar guía emitida (transporte privado, sin viaje)…"
                                        className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                    />
                                    {showG && (
                                        <div className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                            {gResults.length === 0 ? (
                                                <p className="px-3 py-2 text-sm text-slate-400">Sin guías disponibles.</p>
                                            ) : (
                                                gResults.map((g) => (
                                                    <button
                                                        key={g.id}
                                                        type="button"
                                                        onMouseDown={(e) => e.preventDefault()}
                                                        onClick={() => asignar(g)}
                                                        className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span className="font-mono font-medium text-slate-800 dark:text-slate-100">
                                                            {g.numero}
                                                        </span>
                                                        <span className="ml-2 text-xs text-slate-400">
                                                            {g.dest_nombre} · {fechaCorta(g.fecha_traslado)} ·{' '}
                                                            {formatNumber(g.peso_total)} kg
                                                            {g.vehiculo_placa && ` · ${g.vehiculo_placa}`}
                                                        </span>
                                                    </button>
                                                ))
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            {guias.length === 0 ? (
                                <p className="py-4 text-center text-sm text-slate-400">
                                    Aún no hay guías asignadas a este viaje.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                                <th className="pb-3 pr-3 font-semibold">Guía</th>
                                                <th className="pb-3 pr-3 font-semibold">Destinatario / llegada</th>
                                                <th className="pb-3 pr-3 text-right font-semibold">Peso</th>
                                                <th className="pb-3" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {guias.map((g) => (
                                                <tr key={g.id} className="text-slate-700 dark:text-slate-300">
                                                    <td className="py-3 pr-3 font-mono text-xs font-semibold">
                                                        {numeroGuia(g.serie, g.correlativo)}
                                                        {g.vehiculo_placa && trip.vehicle && g.vehiculo_placa !== trip.vehicle.placa && (
                                                            <div className="mt-1 font-sans font-normal text-amber-600">
                                                                Placa en guía: {g.vehiculo_placa}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-3">
                                                        {g.dest_nombre}
                                                        <div className="text-xs text-slate-400">{g.llegada_direccion}</div>
                                                    </td>
                                                    <td className="py-3 pr-3 text-right">
                                                        {formatNumber(Number(g.peso_total ?? 0))} kg
                                                    </td>
                                                    <td className="py-3">
                                                        <div className="flex items-center justify-end gap-1">
                                                            {verGuias && (
                                                            <a
                                                                href={route('guias.pdf', g.id)}
                                                                target="_blank"
                                                                rel="noopener"
                                                                title="PDF de la guía"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                            >
                                                                <Printer className="size-4" />
                                                            </a>
                                                            )}
                                                            {editable && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setToRemove(g.id)}
                                                                    title="Quitar del viaje"
                                                                    className="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                                                >
                                                                    <Trash2 className="size-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Card>

                        <form onSubmit={guardar}>
                            <Card
                                title="Datos, kilometraje y costos"
                                action={
                                    <Button type="submit" size="sm" disabled={form.processing}>
                                        Guardar
                                    </Button>
                                }
                            >
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <TextField
                                        label="Fecha"
                                        type="date"
                                        value={form.data.fecha}
                                        onChange={(e) => form.setData('fecha', e.target.value)}
                                        error={form.errors.fecha}
                                        disabled={cerrado}
                                    />
                                    <div />
                                    <SelectField
                                        label="Vehículo"
                                        value={form.data.vehicle_id}
                                        onChange={(e) => form.setData('vehicle_id', e.target.value)}
                                        error={form.errors.vehicle_id}
                                        options={vehiculoOpts}
                                        disabled={trip.estado !== 'programado'}
                                    />
                                    <SelectField
                                        label="Conductor"
                                        value={form.data.driver_id}
                                        onChange={(e) => form.setData('driver_id', e.target.value)}
                                        error={form.errors.driver_id}
                                        options={conductorOpts}
                                        disabled={trip.estado !== 'programado'}
                                    />
                                    <TextField
                                        label="Origen"
                                        value={form.data.origen}
                                        onChange={(e) => form.setData('origen', e.target.value)}
                                        disabled={cerrado}
                                    />
                                    <TextField
                                        label="Destino"
                                        value={form.data.destino}
                                        onChange={(e) => form.setData('destino', e.target.value)}
                                        disabled={cerrado}
                                    />
                                    <TextField
                                        label="Km inicial"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        value={form.data.km_inicial}
                                        onChange={(e) => form.setData('km_inicial', e.target.value)}
                                        error={form.errors.km_inicial}
                                        disabled={cerrado}
                                    />
                                    <TextField
                                        label="Km final"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        value={form.data.km_final}
                                        onChange={(e) => form.setData('km_final', e.target.value)}
                                        error={form.errors.km_final}
                                    />
                                    <TextField
                                        label="Combustible"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.data.costo_combustible}
                                        onChange={(e) => form.setData('costo_combustible', e.target.value)}
                                        error={form.errors.costo_combustible}
                                    />
                                    <TextField
                                        label="Peajes"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.data.costo_peajes}
                                        onChange={(e) => form.setData('costo_peajes', e.target.value)}
                                        error={form.errors.costo_peajes}
                                    />
                                    <TextField
                                        label="Otros costos"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.data.costo_otros}
                                        onChange={(e) => form.setData('costo_otros', e.target.value)}
                                        error={form.errors.costo_otros}
                                        hint="Viáticos, estiba, etc."
                                    />
                                    <div className="flex items-end">
                                        <p className="text-sm text-slate-500">
                                            Costo total:{' '}
                                            <span className="font-semibold text-slate-800 dark:text-slate-100">
                                                {formatCurrency(costoTotal, moneda)}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <TextField
                                        label="Observación"
                                        value={form.data.observacion}
                                        onChange={(e) => form.setData('observacion', e.target.value)}
                                        error={form.errors.observacion}
                                    />
                                </div>
                            </Card>
                        </form>
                    </div>

                    <div className="space-y-4">
                        <Card title="Resumen">
                            <dl className="space-y-3">
                                <Dato label="Estado">
                                    <Badge tone={estadoTone[trip.estado]}>{estados[trip.estado]}</Badge>
                                </Dato>
                                <Dato label="Vehículo">
                                    {trip.vehicle ? (
                                        <>
                                            <span className="font-mono">{trip.vehicle.placa}</span>
                                            {capacidad !== null && (
                                                <span className="ml-1 text-xs text-slate-400">
                                                    ({formatNumber(capacidad)} kg)
                                                </span>
                                            )}
                                        </>
                                    ) : (
                                        '—'
                                    )}
                                </Dato>
                                <Dato label="Conductor">
                                    {trip.driver ? `${trip.driver.nombres} ${trip.driver.apellidos}` : '—'}
                                    {trip.driver && (
                                        <div className="text-xs text-slate-400">
                                            Licencia {trip.driver.licencia}
                                            {trip.driver.licencia_vence && ` · vence ${fechaCorta(trip.driver.licencia_vence)}`}
                                        </div>
                                    )}
                                </Dato>
                                <Dato label="Salida">
                                    {trip.salida_at ? new Date(trip.salida_at).toLocaleString('es-PE') : '—'}
                                </Dato>
                                <Dato label="Llegada">
                                    {trip.llegada_at ? new Date(trip.llegada_at).toLocaleString('es-PE') : '—'}
                                </Dato>
                                <Dato label="Km recorridos">
                                    {trip.km_recorridos !== null ? formatNumber(trip.km_recorridos) : '—'}
                                </Dato>
                                <Dato label="Costo total">{formatCurrency(trip.costo_total, moneda)}</Dato>
                                <Dato label="Registrado por">{trip.user?.name ?? '—'}</Dato>
                            </dl>
                        </Card>

                        {trip.incidencia && (
                            <Card title="Incidencias">
                                <p className="whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">
                                    {trip.incidencia}
                                </p>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            <Modal show={accion !== null && accion !== 'cancelado'} onClose={() => setAccion(null)} maxWidth="md">
                <form onSubmit={confirmarAccion} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {accion === 'en_ruta' && trip.estado === 'incidencia'
                            ? 'Retomar ruta'
                            : accion
                              ? accionMeta[accion]?.label
                              : ''}
                    </h3>
                    <div className="space-y-4">
                        {accion === 'en_ruta' && trip.estado === 'programado' && (
                            <TextField
                                label="Km inicial (odómetro)"
                                type="number"
                                step="0.1"
                                min="0"
                                value={estadoForm.data.km_inicial}
                                onChange={(e) => estadoForm.setData('km_inicial', e.target.value)}
                                error={estadoForm.errors.km_inicial}
                            />
                        )}
                        {accion === 'en_ruta' && trip.estado === 'incidencia' && (
                            <p className="text-sm text-slate-500">El viaje volverá a estado "En ruta".</p>
                        )}
                        {accion === 'entregado' && (
                            <TextField
                                label="Km final (odómetro)"
                                type="number"
                                step="0.1"
                                min="0"
                                value={estadoForm.data.km_final}
                                onChange={(e) => estadoForm.setData('km_final', e.target.value)}
                                error={estadoForm.errors.km_final}
                            />
                        )}
                        {accion === 'incidencia' && (
                            <TextareaField
                                label="¿Qué ocurrió?"
                                required
                                rows={3}
                                value={estadoForm.data.incidencia}
                                onChange={(e) => estadoForm.setData('incidencia', e.target.value)}
                                error={estadoForm.errors.incidencia}
                            />
                        )}
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setAccion(null)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={estadoForm.processing}>
                            Confirmar
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={accion === 'cancelado'}
                title="Cancelar viaje"
                message={`¿Cancelar el viaje ${trip.numero}? Ya no se podrá modificar.`}
                processing={estadoForm.processing}
                onConfirm={() => confirmarAccion()}
                onClose={() => setAccion(null)}
            />

            <ConfirmDialog
                show={toRemove !== null}
                title="Quitar guía"
                message="¿Quitar esta guía del viaje? Quedará disponible para otro viaje."
                onConfirm={quitar}
                onClose={() => setToRemove(null)}
            />
        </AuthenticatedLayout>
    );
}
