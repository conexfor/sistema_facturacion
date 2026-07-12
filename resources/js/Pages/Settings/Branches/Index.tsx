import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import EmptyState from '@/Components/ui/EmptyState';
import { Switch, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Branch {
    id: number;
    codigo: string;
    nombre: string;
    direccion: string | null;
    ubigeo: string | null;
    telefono: string | null;
    is_main: boolean;
    is_active: boolean;
    users_count?: number;
    warehouses_count?: number;
}

export default function Index({
    branches,
    filters,
}: {
    branches: Paginated<Branch>;
    filters: Record<string, string | undefined>;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Branch | null>(null);
    const [toDelete, setToDelete] = useState<Branch | null>(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        codigo: '',
        nombre: '',
        direccion: '',
        ubigeo: '',
        telefono: '',
        is_main: false,
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (b: Branch) => {
        setEditing(b);
        form.setData({
            codigo: b.codigo,
            nombre: b.nombre,
            direccion: b.direccion ?? '',
            ubigeo: b.ubigeo ?? '',
            telefono: b.telefono ?? '',
            is_main: b.is_main,
            is_active: b.is_active,
        });
        form.clearErrors();
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('settings.sucursales.update', editing.id), opts);
        } else {
            form.post(route('settings.sucursales.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('settings.sucursales.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Sucursales" />
            <div className="space-y-5">
                <PageHeader
                    title="Sucursales"
                    description="Establecimientos anexos de la empresa"
                    breadcrumbs={[{ label: 'Configuración' }, { label: 'Sucursales' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nueva sucursal
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-sm">
                        <SearchInput
                            routeName="settings.sucursales.index"
                            filters={filters}
                            placeholder="Buscar sucursal…"
                        />
                    </div>

                    {branches.data.length === 0 ? (
                        <EmptyState
                            icon={Building2}
                            title="Sin sucursales"
                            description="Registra los establecimientos de tu empresa."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Código</th>
                                        <th className="pb-3 pr-3 font-semibold">Nombre</th>
                                        <th className="pb-3 pr-3 font-semibold">Dirección</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Usuarios</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Almacenes</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {branches.data.map((b) => (
                                        <tr key={b.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs">{b.codigo}</td>
                                            <td className="py-3 pr-3">
                                                <span className="font-medium text-slate-800 dark:text-slate-100">
                                                    {b.nombre}
                                                </span>
                                                {b.is_main && (
                                                    <Badge tone="brand">
                                                        <span className="ml-1">Principal</span>
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="py-3 pr-3 text-slate-400">
                                                {b.direccion ?? '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-center">{b.users_count ?? 0}</td>
                                            <td className="py-3 pr-3 text-center">{b.warehouses_count ?? 0}</td>
                                            <td className="py-3 pr-3 text-center">
                                                {b.is_active ? (
                                                    <Badge tone="emerald">Activa</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactiva</Badge>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEdit(b)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>
                                                    {!b.is_main && (
                                                        <button
                                                            onClick={() => setToDelete(b)}
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

                    <Pagination
                        links={branches.links}
                        from={branches.from}
                        to={branches.to}
                        total={branches.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar sucursal' : 'Nueva sucursal'}
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <TextField
                                label="Código"
                                required
                                value={form.data.codigo}
                                onChange={(e) => form.setData('codigo', e.target.value)}
                                error={form.errors.codigo}
                                hint="Anexo SUNAT (0000)"
                            />
                            <div className="sm:col-span-2">
                                <TextField
                                    label="Nombre"
                                    required
                                    value={form.data.nombre}
                                    onChange={(e) => form.setData('nombre', e.target.value)}
                                    error={form.errors.nombre}
                                />
                            </div>
                        </div>
                        <TextField
                            label="Dirección"
                            value={form.data.direccion}
                            onChange={(e) => form.setData('direccion', e.target.value)}
                            error={form.errors.direccion}
                        />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Ubigeo"
                                value={form.data.ubigeo}
                                onChange={(e) => form.setData('ubigeo', e.target.value)}
                                error={form.errors.ubigeo}
                                hint="6 dígitos"
                            />
                            <TextField
                                label="Teléfono"
                                value={form.data.telefono}
                                onChange={(e) => form.setData('telefono', e.target.value)}
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Switch
                                label="Principal"
                                checked={form.data.is_main}
                                onChange={(v) => form.setData('is_main', v)}
                            />
                            <Switch
                                label="Activa"
                                checked={form.data.is_active}
                                onChange={(v) => form.setData('is_active', v)}
                            />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Guardar cambios' : 'Crear sucursal'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar sucursal"
                message={`¿Eliminar la sucursal "${toDelete?.nombre}"?`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
