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
import { Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserCog } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface UserRow {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    position: string | null;
    branch_id: number | null;
    is_active: boolean;
    branch?: { id: number; nombre: string } | null;
    roles: { id: number; name: string }[];
}

interface Branch {
    id: number;
    nombre: string;
}

export default function Index({
    users,
    roles,
    branches,
    filters,
}: {
    users: Paginated<UserRow>;
    roles: string[];
    branches: Branch[];
    filters: Record<string, string | undefined>;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [toDelete, setToDelete] = useState<UserRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        position: '',
        branch_id: '',
        role: roles[0] ?? '',
        is_active: true,
        password: '',
        password_confirmation: '',
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.setData('role', roles[0] ?? '');
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (u: UserRow) => {
        setEditing(u);
        form.setData({
            name: u.name,
            email: u.email,
            phone: u.phone ?? '',
            position: u.position ?? '',
            branch_id: u.branch_id ? String(u.branch_id) : '',
            role: u.roles[0]?.name ?? roles[0] ?? '',
            is_active: u.is_active,
            password: '',
            password_confirmation: '',
        });
        form.clearErrors();
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('settings.usuarios.update', editing.id), opts);
        } else {
            form.post(route('settings.usuarios.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('settings.usuarios.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Usuarios" />
            <div className="space-y-5">
                <PageHeader
                    title="Usuarios"
                    description="Gestiona el personal y sus accesos"
                    breadcrumbs={[{ label: 'Configuración' }, { label: 'Usuarios' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo usuario
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="settings.usuarios.index"
                            filters={filters}
                            placeholder="Buscar por nombre o correo…"
                        />
                    </div>

                    {users.data.length === 0 ? (
                        <EmptyState icon={UserCog} title="Sin usuarios" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Usuario</th>
                                        <th className="pb-3 pr-3 font-semibold">Rol</th>
                                        <th className="pb-3 pr-3 font-semibold">Sucursal</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {users.data.map((u) => (
                                        <tr key={u.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3">
                                                <div className="font-medium text-slate-800 dark:text-slate-100">
                                                    {u.name}
                                                </div>
                                                <div className="text-xs text-slate-400">{u.email}</div>
                                            </td>
                                            <td className="py-3 pr-3">
                                                {u.roles[0] ? (
                                                    <Badge tone="brand">{u.roles[0].name}</Badge>
                                                ) : (
                                                    <Badge tone="slate">sin rol</Badge>
                                                )}
                                            </td>
                                            <td className="py-3 pr-3 text-slate-500">
                                                {u.branch?.nombre ?? '—'}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {u.is_active ? (
                                                    <Badge tone="emerald">Activo</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactivo</Badge>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEdit(u)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setToDelete(u)}
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
                        links={users.links}
                        from={users.from}
                        to={users.to}
                        total={users.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar usuario' : 'Nuevo usuario'}
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Nombre"
                                required
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                            />
                            <TextField
                                label="Correo"
                                type="email"
                                required
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                error={form.errors.email}
                            />
                            <TextField
                                label="Teléfono"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                            />
                            <TextField
                                label="Cargo"
                                value={form.data.position}
                                onChange={(e) => form.setData('position', e.target.value)}
                            />
                            <SelectField
                                label="Rol"
                                required
                                value={form.data.role}
                                onChange={(e) => form.setData('role', e.target.value)}
                                error={form.errors.role}
                                options={roles.map((r) => ({ value: r, label: r }))}
                            />
                            <SelectField
                                label="Sucursal"
                                placeholder="— Sin sucursal —"
                                value={form.data.branch_id}
                                onChange={(e) => form.setData('branch_id', e.target.value)}
                                error={form.errors.branch_id}
                                options={branches.map((b) => ({ value: b.id, label: b.nombre }))}
                            />
                            <TextField
                                label={editing ? 'Nueva contraseña (opcional)' : 'Contraseña'}
                                type="password"
                                required={!editing}
                                value={form.data.password}
                                onChange={(e) => form.setData('password', e.target.value)}
                                error={form.errors.password}
                            />
                            <TextField
                                label="Confirmar contraseña"
                                type="password"
                                required={!editing}
                                value={form.data.password_confirmation}
                                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                            />
                        </div>
                        <Switch
                            label="Activo"
                            checked={form.data.is_active}
                            onChange={(v) => form.setData('is_active', v)}
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Guardar cambios' : 'Crear usuario'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar usuario"
                message={`¿Eliminar a "${toDelete?.name}"?`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
