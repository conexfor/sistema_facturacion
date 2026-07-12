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
import { Paginated, Supplier } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, Pencil, Plus, Search, Trash2, Truck } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Index({
    suppliers,
    tipos,
    filters,
    lookupEnabled,
}: {
    suppliers: Paginated<Supplier>;
    tipos: Record<string, string>;
    filters: Record<string, string | undefined>;
    lookupEnabled: boolean;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Supplier | null>(null);
    const [toDelete, setToDelete] = useState<Supplier | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [lookupLoading, setLookupLoading] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);

    const form = useForm({
        tipo_documento: '6',
        numero_documento: '',
        razon_social: '',
        nombre_comercial: '',
        direccion: '',
        contacto: '',
        email: '',
        telefono: '',
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setLookupError(null);
        setShowModal(true);
    };

    const openEdit = (s: Supplier) => {
        setEditing(s);
        form.setData({
            tipo_documento: s.tipo_documento,
            numero_documento: s.numero_documento ?? '',
            razon_social: s.razon_social,
            nombre_comercial: s.nombre_comercial ?? '',
            direccion: s.direccion ?? '',
            contacto: s.contacto ?? '',
            email: s.email ?? '',
            telefono: s.telefono ?? '',
            is_active: s.is_active,
        });
        form.clearErrors();
        setLookupError(null);
        setShowModal(true);
    };

    const consultar = () => {
        setLookupError(null);
        setLookupLoading(true);
        axios
            .get(route('compras.proveedores.consultar'), {
                params: { tipo: form.data.tipo_documento, numero: form.data.numero_documento },
            })
            .then((r) => {
                const d = r.data.data;
                form.setData((prev) => ({
                    ...prev,
                    razon_social: d.razon_social || prev.razon_social,
                    nombre_comercial: d.nombre_comercial ?? prev.nombre_comercial,
                    direccion: d.direccion ?? prev.direccion,
                }));
            })
            .catch((e) =>
                setLookupError(e.response?.data?.message ?? 'No se pudo consultar el documento.'),
            )
            .finally(() => setLookupLoading(false));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('compras.proveedores.update', editing.id), opts);
        } else {
            form.post(route('compras.proveedores.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('compras.proveedores.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    const canLookup =
        ['1', '6'].includes(form.data.tipo_documento) &&
        form.data.numero_documento.length >= 8;

    return (
        <AuthenticatedLayout>
            <Head title="Proveedores" />
            <div className="space-y-5">
                <PageHeader
                    title="Proveedores"
                    description="Directorio de proveedores y consulta SUNAT"
                    breadcrumbs={[{ label: 'Compras' }, { label: 'Proveedores' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo proveedor
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="compras.proveedores.index"
                            filters={filters}
                            placeholder="Buscar por nombre o RUC…"
                        />
                    </div>

                    {suppliers.data.length === 0 ? (
                        <EmptyState
                            icon={Truck}
                            title="Sin proveedores"
                            description="Registra tu primer proveedor o consúltalo por RUC."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Documento</th>
                                        <th className="pb-3 pr-3 font-semibold">Razón social</th>
                                        <th className="pb-3 pr-3 font-semibold">Contacto</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {suppliers.data.map((s) => (
                                        <tr key={s.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3">
                                                <Badge tone="slate">{tipos[s.tipo_documento]}</Badge>
                                                <span className="ml-2 font-mono text-xs">
                                                    {s.numero_documento ?? '—'}
                                                </span>
                                            </td>
                                            <td className="py-3 pr-3 font-medium text-slate-800 dark:text-slate-100">
                                                {s.razon_social}
                                            </td>
                                            <td className="py-3 pr-3 text-xs text-slate-400">
                                                {s.contacto ?? ''}
                                                {s.contacto && s.telefono ? ' · ' : ''}
                                                {s.telefono ?? ''}
                                                {!s.contacto && !s.telefono ? '—' : ''}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {s.is_active ? (
                                                    <Badge tone="emerald">Activo</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactivo</Badge>
                                                )}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openEdit(s)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setToDelete(s)}
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
                        links={suppliers.links}
                        from={suppliers.from}
                        to={suppliers.to}
                        total={suppliers.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar proveedor' : 'Nuevo proveedor'}
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <SelectField
                                label="Tipo doc."
                                value={form.data.tipo_documento}
                                onChange={(e) => form.setData('tipo_documento', e.target.value)}
                                error={form.errors.tipo_documento}
                                options={Object.entries(tipos).map(([value, label]) => ({
                                    value,
                                    label,
                                }))}
                            />
                            <div className="sm:col-span-2">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Número de documento
                                </label>
                                <div className="flex gap-2">
                                    <input
                                        value={form.data.numero_documento}
                                        onChange={(e) =>
                                            form.setData('numero_documento', e.target.value)
                                        }
                                        className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {canLookup && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={consultar}
                                            disabled={lookupLoading}
                                            title={
                                                lookupEnabled
                                                    ? 'Consultar en SUNAT'
                                                    : 'Requiere configurar PERU_API_TOKEN'
                                            }
                                        >
                                            {lookupLoading ? (
                                                <Loader2 className="size-4 animate-spin" />
                                            ) : (
                                                <Search className="size-4" />
                                            )}
                                            Consultar
                                        </Button>
                                    )}
                                </div>
                                {form.errors.numero_documento && (
                                    <p className="mt-1 text-xs text-rose-600">
                                        {form.errors.numero_documento}
                                    </p>
                                )}
                                {lookupError && (
                                    <p className="mt-1 text-xs text-amber-600">{lookupError}</p>
                                )}
                            </div>
                        </div>
                        <TextField
                            label="Razón social"
                            required
                            value={form.data.razon_social}
                            onChange={(e) => form.setData('razon_social', e.target.value)}
                            error={form.errors.razon_social}
                        />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Nombre comercial"
                                value={form.data.nombre_comercial}
                                onChange={(e) => form.setData('nombre_comercial', e.target.value)}
                            />
                            <TextField
                                label="Persona de contacto"
                                value={form.data.contacto}
                                onChange={(e) => form.setData('contacto', e.target.value)}
                            />
                        </div>
                        <TextField
                            label="Dirección"
                            value={form.data.direccion}
                            onChange={(e) => form.setData('direccion', e.target.value)}
                        />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <TextField
                                label="Teléfono"
                                value={form.data.telefono}
                                onChange={(e) => form.setData('telefono', e.target.value)}
                            />
                            <TextField
                                label="Email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                error={form.errors.email}
                            />
                            <Switch
                                label="Activo"
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
                            {editing ? 'Guardar cambios' : 'Registrar proveedor'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar proveedor"
                message={`¿Eliminar a "${toDelete?.razon_social}"? Las compras registradas se conservan.`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
