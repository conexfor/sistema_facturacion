import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import { TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, ShieldCheck, Trash2, Users } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Role {
    id: number;
    name: string;
    permissions: string[];
    users_count: number;
}

export default function Index({
    roles,
    permisos,
}: {
    roles: Role[];
    permisos: Record<string, string>;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Role | null>(null);
    const [toDelete, setToDelete] = useState<Role | null>(null);

    const form = useForm<{ name: string; permissions: string[] }>({
        name: '',
        permissions: [],
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (r: Role) => {
        setEditing(r);
        form.setData({ name: r.name, permissions: [...r.permissions] });
        form.clearErrors();
        setShowModal(true);
    };

    const toggle = (perm: string) => {
        form.setData(
            'permissions',
            form.data.permissions.includes(perm)
                ? form.data.permissions.filter((p) => p !== perm)
                : [...form.data.permissions, perm],
        );
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('settings.roles.update', editing.id), opts);
        } else {
            form.post(route('settings.roles.store'), opts);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Roles y permisos" />
            <div className="space-y-5">
                <PageHeader
                    title="Roles y permisos"
                    description="Define qué puede hacer cada tipo de usuario"
                    breadcrumbs={[{ label: 'Configuración' }, { label: 'Roles' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo rol
                        </Button>
                    }
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {roles.map((r) => (
                        <div
                            key={r.id}
                            className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-2">
                                    <span className="flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30">
                                        <ShieldCheck className="size-5" />
                                    </span>
                                    <div>
                                        <p className="font-semibold text-slate-800 dark:text-slate-100">
                                            {r.name}
                                        </p>
                                        <p className="flex items-center gap-1 text-xs text-slate-400">
                                            <Users className="size-3" /> {r.users_count} usuario(s)
                                        </p>
                                    </div>
                                </div>
                                <div className="flex gap-1">
                                    <button
                                        onClick={() => openEdit(r)}
                                        className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                    >
                                        <Pencil className="size-4" />
                                    </button>
                                    {r.name !== 'Administrador' && (
                                        <button
                                            onClick={() => setToDelete(r)}
                                            className="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    )}
                                </div>
                            </div>
                            <div className="mt-4 flex flex-wrap gap-1.5">
                                {r.permissions.length === 0 ? (
                                    <span className="text-xs text-slate-400">Sin permisos</span>
                                ) : (
                                    r.permissions.map((p) => (
                                        <Badge key={p} tone="slate">
                                            {permisos[p] ?? p}
                                        </Badge>
                                    ))
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar rol' : 'Nuevo rol'}
                    </h3>
                    <div className="space-y-4">
                        <TextField
                            label="Nombre del rol"
                            required
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            error={form.errors.name}
                            disabled={editing?.name === 'Administrador'}
                        />
                        <div>
                            <p className="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                                Permisos
                            </p>
                            <div className="space-y-2">
                                {Object.entries(permisos).map(([name, label]) => (
                                    <label
                                        key={name}
                                        className="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={form.data.permissions.includes(name)}
                                            onChange={() => toggle(name)}
                                            className="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                        />
                                        <span className="text-slate-700 dark:text-slate-200">
                                            {label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Guardar cambios' : 'Crear rol'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar rol"
                message={`¿Eliminar el rol "${toDelete?.name}"?`}
                onConfirm={() => {
                    if (!toDelete) return;
                    form.delete(route('settings.roles.destroy', toDelete.id), {
                        preserveScroll: true,
                        onFinish: () => setToDelete(null),
                    });
                }}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
