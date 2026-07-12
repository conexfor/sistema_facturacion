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
import { Paginated, Warehouse } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Warehouse as WarehouseIcon } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Branch {
    id: number;
    nombre: string;
}

export default function Index({
    warehouses,
    branches,
    filters,
}: {
    warehouses: Paginated<Warehouse>;
    branches: Branch[];
    filters: Record<string, string | undefined>;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Warehouse | null>(null);
    const [toDelete, setToDelete] = useState<Warehouse | null>(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        codigo: '',
        nombre: '',
        direccion: '',
        branch_id: '' as string,
        is_main: false,
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (w: Warehouse) => {
        setEditing(w);
        form.setData({
            codigo: w.codigo,
            nombre: w.nombre,
            direccion: w.direccion ?? '',
            branch_id: w.branch_id ? String(w.branch_id) : '',
            is_main: w.is_main,
            is_active: w.is_active,
        });
        form.clearErrors();
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('inventario.almacenes.update', editing.id), opts);
        } else {
            form.post(route('inventario.almacenes.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        form.delete(route('inventario.almacenes.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Almacenes" />
            <div className="space-y-5">
                <PageHeader
                    title="Almacenes"
                    description="Ubicaciones donde se controla el stock"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Almacenes' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo almacén
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-sm">
                        <SearchInput
                            routeName="inventario.almacenes.index"
                            filters={filters}
                            placeholder="Buscar almacén…"
                        />
                    </div>

                    {warehouses.data.length === 0 ? (
                        <EmptyState
                            icon={WarehouseIcon}
                            title="Sin almacenes"
                            description="Crea un almacén para registrar stock."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Código</th>
                                        <th className="pb-3 pr-3 font-semibold">Nombre</th>
                                        <th className="pb-3 pr-3 font-semibold">Sucursal</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {warehouses.data.map((w) => (
                                        <tr key={w.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs">{w.codigo}</td>
                                            <td className="py-3 pr-3">
                                                <span className="font-medium text-slate-800 dark:text-slate-100">
                                                    {w.nombre}
                                                </span>
                                                {w.is_main && (
                                                    <Badge tone="brand">
                                                        <span className="ml-1">Principal</span>
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="py-3 pr-3 text-slate-400">
                                                {w.branch?.nombre ?? '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {w.is_active ? (
                                                    <Badge tone="emerald">Activo</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactivo</Badge>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEdit(w)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setToDelete(w)}
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
                        links={warehouses.links}
                        from={warehouses.from}
                        to={warehouses.to}
                        total={warehouses.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar almacén' : 'Nuevo almacén'}
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Código"
                                required
                                value={form.data.codigo}
                                onChange={(e) => form.setData('codigo', e.target.value)}
                                error={form.errors.codigo}
                            />
                            <TextField
                                label="Nombre"
                                required
                                value={form.data.nombre}
                                onChange={(e) => form.setData('nombre', e.target.value)}
                                error={form.errors.nombre}
                            />
                        </div>
                        <TextField
                            label="Dirección"
                            value={form.data.direccion}
                            onChange={(e) => form.setData('direccion', e.target.value)}
                            error={form.errors.direccion}
                        />
                        <SelectField
                            label="Sucursal"
                            placeholder="— Sin sucursal —"
                            value={form.data.branch_id}
                            onChange={(e) => form.setData('branch_id', e.target.value)}
                            error={form.errors.branch_id}
                            options={branches.map((b) => ({ value: b.id, label: b.nombre }))}
                        />
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Switch
                                label="Principal"
                                checked={form.data.is_main}
                                onChange={(v) => form.setData('is_main', v)}
                            />
                            <Switch
                                label="Activo"
                                checked={form.data.is_active}
                                onChange={(v) => form.setData('is_active', v)}
                            />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setShowModal(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Guardar cambios' : 'Crear almacén'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar almacén"
                message={`¿Eliminar el almacén "${toDelete?.nombre}"?`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
