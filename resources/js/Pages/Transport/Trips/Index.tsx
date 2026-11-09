import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated, Trip, TripEstado } from '@/types/models';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChevronRight, Plus, Route } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { estadoTone, fechaCorta } from '@/lib/trips';

interface VehiculoOpt {
    id: number;
    placa: string;
    marca: string | null;
    modelo: string | null;
}
interface ConductorOpt {
    id: number;
    nombres: string;
    apellidos: string;
}

export default function Index({
    trips,
    estados,
    conteo,
    filters,
    vehiculos,
    conductores,
}: {
    trips: Paginated<Trip>;
    estados: Record<TripEstado, string>;
    conteo: Partial<Record<TripEstado, number>>;
    filters: Record<string, string | undefined>;
    vehiculos: VehiculoOpt[];
    conductores: ConductorOpt[];
}) {
    const moneda = usePage().props.auth.company?.moneda ?? 'PEN';
    const [showModal, setShowModal] = useState(false);

    const form = useForm({
        fecha: new Date().toISOString().slice(0, 10),
        vehicle_id: '',
        driver_id: '',
        origen: '',
        destino: '',
        km_inicial: '',
        observacion: '',
    });

    const openCreate = () => {
        form.reset();
        form.clearErrors();
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('transporte.viajes.store'));
    };

    const total = Object.values(conteo).reduce((a, b) => a + Number(b ?? 0), 0);
    const tabs: { key: string; label: string; count: number }[] = [
        { key: '', label: 'Todos', count: total },
        ...(Object.keys(estados) as TripEstado[]).map((k) => ({
            key: k,
            label: estados[k],
            count: Number(conteo[k] ?? 0),
        })),
    ];
    const activo = filters.estado ?? '';

    return (
        <AuthenticatedLayout>
            <Head title="Viajes" />
            <div className="space-y-5">
                <PageHeader
                    title="Viajes"
                    description="Hoja de ruta: vehículo, conductor, guías transportadas y costos"
                    breadcrumbs={[{ label: 'Transporte' }, { label: 'Viajes' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Programar viaje
                        </Button>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    {tabs.map((t) => (
                        <Link
                            key={t.key || 'todos'}
                            href={route('transporte.viajes.index', {
                                ...(filters.search ? { search: filters.search } : {}),
                                ...(t.key ? { estado: t.key } : {}),
                            })}
                            preserveScroll
                            className={`inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition ${
                                activo === t.key
                                    ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'
                            }`}
                        >
                            {t.label}
                            <span className="rounded-full bg-slate-100 px-1.5 text-xs text-slate-500 dark:bg-slate-800">
                                {t.count}
                            </span>
                        </Link>
                    ))}
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="transporte.viajes.index"
                            filters={filters}
                            placeholder="Buscar por destino, placa o conductor…"
                        />
                    </div>

                    {trips.data.length === 0 ? (
                        <EmptyState
                            icon={Route}
                            title="Sin viajes"
                            description="Programa un viaje y asígnale las guías de remisión que transportará."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Viaje</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 font-semibold">Vehículo / conductor</th>
                                        <th className="pb-3 pr-3 font-semibold">Ruta</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Guías</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Costo</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {trips.data.map((t) => (
                                        <tr key={t.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs font-semibold">{t.numero}</td>
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {fechaCorta(t.fecha)}
                                            </td>
                                            <td className="py-3 pr-3">
                                                <span className="font-mono text-xs">{t.vehicle?.placa ?? '—'}</span>
                                                <div className="text-xs text-slate-400">
                                                    {t.driver ? `${t.driver.nombres} ${t.driver.apellidos}` : '—'}
                                                </div>
                                            </td>
                                            <td className="py-3 pr-3 text-xs">
                                                {t.origen || t.destino
                                                    ? `${t.origen ?? '—'} → ${t.destino ?? '—'}`
                                                    : '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-right">{t.despatches_count ?? 0}</td>
                                            <td className="py-3 pr-3 text-right">
                                                {formatCurrency(t.costo_total, moneda)}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                <Badge tone={estadoTone[t.estado]}>{estados[t.estado]}</Badge>
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={route('transporte.viajes.show', t.id)}
                                                    className="inline-flex rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                >
                                                    <ChevronRight className="size-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={trips.links} from={trips.from} to={trips.to} total={trips.total} />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">Programar viaje</h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Fecha"
                                type="date"
                                required
                                value={form.data.fecha}
                                onChange={(e) => form.setData('fecha', e.target.value)}
                                error={form.errors.fecha}
                            />
                            <TextField
                                label="Km inicial"
                                type="number"
                                step="0.1"
                                min="0"
                                value={form.data.km_inicial}
                                onChange={(e) => form.setData('km_inicial', e.target.value)}
                                error={form.errors.km_inicial}
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <SelectField
                                label="Vehículo"
                                required
                                placeholder="— Selecciona —"
                                value={form.data.vehicle_id}
                                onChange={(e) => form.setData('vehicle_id', e.target.value)}
                                error={form.errors.vehicle_id}
                                options={vehiculos.map((v) => ({
                                    value: v.id,
                                    label: [v.placa, v.marca, v.modelo].filter(Boolean).join(' · '),
                                }))}
                            />
                            <SelectField
                                label="Conductor"
                                required
                                placeholder="— Selecciona —"
                                value={form.data.driver_id}
                                onChange={(e) => form.setData('driver_id', e.target.value)}
                                error={form.errors.driver_id}
                                options={conductores.map((c) => ({
                                    value: c.id,
                                    label: `${c.apellidos}, ${c.nombres}`,
                                }))}
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Origen"
                                value={form.data.origen}
                                onChange={(e) => form.setData('origen', e.target.value)}
                                error={form.errors.origen}
                            />
                            <TextField
                                label="Destino"
                                value={form.data.destino}
                                onChange={(e) => form.setData('destino', e.target.value)}
                                error={form.errors.destino}
                            />
                        </div>
                        <TextField
                            label="Observación"
                            value={form.data.observacion}
                            onChange={(e) => form.setData('observacion', e.target.value)}
                            error={form.errors.observacion}
                        />
                        {(vehiculos.length === 0 || conductores.length === 0) && (
                            <p className="text-xs text-amber-600">
                                Primero registra al menos un vehículo y un conductor activos.
                            </p>
                        )}
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Programar
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
