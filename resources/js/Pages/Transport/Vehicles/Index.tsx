import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, Switch, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber } from '@/lib/format';
import { Paginated, Vehicle } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Truck } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Index({
    vehicles,
    tipos,
    filters,
}: {
    vehicles: Paginated<Vehicle>;
    tipos: string[];
    filters: Record<string, string | undefined>;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Vehicle | null>(null);
    const [toDelete, setToDelete] = useState<Vehicle | null>(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        placa: '',
        marca: '',
        modelo: '',
        tipo: '',
        cert_habilitacion: '',
        capacidad_kg: '',
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (v: Vehicle) => {
        setEditing(v);
        form.setData({
            placa: v.placa,
            marca: v.marca ?? '',
            modelo: v.modelo ?? '',
            tipo: v.tipo ?? '',
            cert_habilitacion: v.cert_habilitacion ?? '',
            capacidad_kg: v.capacidad_kg ?? '',
            is_active: v.is_active,
        });
        form.clearErrors();
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('transporte.vehiculos.update', editing.id), opts);
        } else {
            form.post(route('transporte.vehiculos.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('transporte.vehiculos.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Vehículos" />
            <div className="space-y-5">
                <PageHeader
                    title="Vehículos"
                    description="Flota propia para guías de transporte privado y viajes"
                    breadcrumbs={[{ label: 'Transporte' }, { label: 'Vehículos' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo vehículo
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="transporte.vehiculos.index"
                            filters={filters}
                            placeholder="Buscar por placa, marca o modelo…"
                        />
                    </div>

                    {vehicles.data.length === 0 ? (
                        <EmptyState
                            icon={Truck}
                            title="Sin vehículos"
                            description="Registra los vehículos de tu flota para usarlos en guías y viajes."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Placa</th>
                                        <th className="pb-3 pr-3 font-semibold">Vehículo</th>
                                        <th className="pb-3 pr-3 font-semibold">Cert. habilitación</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Capacidad</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Guías / viajes</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {vehicles.data.map((v) => (
                                        <tr key={v.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs font-semibold text-slate-800 dark:text-slate-100">
                                                {v.placa}
                                            </td>
                                            <td className="py-3 pr-3">
                                                {[v.marca, v.modelo].filter(Boolean).join(' ') || '—'}
                                                {v.tipo && <div className="text-xs text-slate-400">{v.tipo}</div>}
                                            </td>
                                            <td className="py-3 pr-3 text-xs text-slate-500">
                                                {v.cert_habilitacion ?? '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-right">
                                                {v.capacidad_kg ? `${formatNumber(Number(v.capacidad_kg))} kg` : '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-right text-xs text-slate-500">
                                                {v.despatches_count ?? 0} / {v.trips_count ?? 0}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {v.is_active ? (
                                                    <Badge tone="emerald">Activo</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactivo</Badge>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEdit(v)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setToDelete(v)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination
                        links={vehicles.links}
                        from={vehicles.from}
                        to={vehicles.to}
                        total={vehicles.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar vehículo' : 'Nuevo vehículo'}
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Placa"
                                required
                                value={form.data.placa}
                                onChange={(e) => form.setData('placa', e.target.value.toUpperCase())}
                                error={form.errors.placa}
                                hint="Sin guiones, ej. ABC123"
                            />
                            <SelectField
                                label="Tipo"
                                placeholder="— Sin especificar —"
                                value={form.data.tipo}
                                onChange={(e) => form.setData('tipo', e.target.value)}
                                options={tipos.map((t) => ({ value: t, label: t }))}
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Marca"
                                value={form.data.marca}
                                onChange={(e) => form.setData('marca', e.target.value)}
                                error={form.errors.marca}
                            />
                            <TextField
                                label="Modelo"
                                value={form.data.modelo}
                                onChange={(e) => form.setData('modelo', e.target.value)}
                                error={form.errors.modelo}
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Cert. de habilitación (MTC)"
                                value={form.data.cert_habilitacion}
                                onChange={(e) => form.setData('cert_habilitacion', e.target.value)}
                                error={form.errors.cert_habilitacion}
                            />
                            <TextField
                                label="Capacidad de carga (kg)"
                                type="number"
                                step="0.001"
                                min="0"
                                value={form.data.capacidad_kg}
                                onChange={(e) => form.setData('capacidad_kg', e.target.value)}
                                error={form.errors.capacidad_kg}
                                hint="Se valida contra el peso de guías y viajes"
                            />
                        </div>
                        <Switch
                            label="Activo"
                            description="Los inactivos no aparecen al emitir guías ni al programar viajes"
                            checked={form.data.is_active}
                            onChange={(v) => form.setData('is_active', v)}
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Guardar cambios' : 'Registrar vehículo'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar vehículo"
                message={`¿Eliminar el vehículo ${toDelete?.placa}?`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
