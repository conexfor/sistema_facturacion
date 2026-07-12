import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import EmptyState from '@/Components/ui/EmptyState';
import { Switch, TextareaField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, Paginated } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Tags, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Index({
    categories,
    filters,
}: {
    categories: Paginated<Category>;
    filters: Record<string, string | undefined>;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [toDelete, setToDelete] = useState<Category | null>(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        nombre: '',
        descripcion: '',
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (c: Category) => {
        setEditing(c);
        form.setData({
            nombre: c.nombre,
            descripcion: c.descripcion ?? '',
            is_active: c.is_active,
        });
        form.clearErrors();
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = {
            preserveScroll: true,
            onSuccess: () => setShowModal(false),
        };
        if (editing) {
            form.put(route('inventario.categorias.update', editing.id), opts);
        } else {
            form.post(route('inventario.categorias.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        form.delete(route('inventario.categorias.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Categorías" />
            <div className="space-y-5">
                <PageHeader
                    title="Categorías"
                    description="Organiza tus productos por categoría"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Categorías' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nueva categoría
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-sm">
                        <SearchInput
                            routeName="inventario.categorias.index"
                            filters={filters}
                            placeholder="Buscar categoría…"
                        />
                    </div>

                    {categories.data.length === 0 ? (
                        <EmptyState
                            icon={Tags}
                            title="Sin categorías"
                            description="Crea categorías para clasificar tus productos."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Nombre</th>
                                        <th className="pb-3 pr-3 font-semibold">Descripción</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Productos</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {categories.data.map((c) => (
                                        <tr key={c.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-medium text-slate-800 dark:text-slate-100">
                                                {c.nombre}
                                            </td>
                                            <td className="py-3 pr-3 text-slate-400">
                                                {c.descripcion ?? '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {c.products_count ?? 0}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {c.is_active ? (
                                                    <Badge tone="emerald">Activa</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactiva</Badge>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEdit(c)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setToDelete(c)}
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
                        links={categories.links}
                        from={categories.from}
                        to={categories.to}
                        total={categories.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar categoría' : 'Nueva categoría'}
                    </h3>
                    <div className="space-y-4">
                        <TextField
                            label="Nombre"
                            required
                            value={form.data.nombre}
                            onChange={(e) => form.setData('nombre', e.target.value)}
                            error={form.errors.nombre}
                        />
                        <TextareaField
                            label="Descripción"
                            rows={2}
                            value={form.data.descripcion}
                            onChange={(e) => form.setData('descripcion', e.target.value)}
                            error={form.errors.descripcion}
                        />
                        <Switch
                            label="Activa"
                            checked={form.data.is_active}
                            onChange={(v) => form.setData('is_active', v)}
                        />
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
                            {editing ? 'Guardar cambios' : 'Crear categoría'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar categoría"
                message={`¿Eliminar la categoría "${toDelete?.nombre}"? Los productos quedarán sin categoría.`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
