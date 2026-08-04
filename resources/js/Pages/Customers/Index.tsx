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
import { Customer, Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, Pencil, Plus, Search, Trash2, Users } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Index({
    customers,
    tipos,
    filters,
    lookupEnabled,
}: {
    customers: Paginated<Customer>;
    tipos: Record<string, string>;
    filters: Record<string, string | undefined>;
    lookupEnabled: boolean;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Customer | null>(null);
    const [toDelete, setToDelete] = useState<Customer | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [lookupLoading, setLookupLoading] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);
    const [lookupInfo, setLookupInfo] = useState<{ estado?: string; condicion?: string; habilitado?: boolean | null } | null>(null);

    const form = useForm({
        tipo_documento: '1',
        numero_documento: '',
        razon_social: '',
        nombre_comercial: '',
        direccion: '',
        ubigeo: '',
        estado_contribuyente: '',
        condicion_domicilio: '',
        email: '',
        telefono: '',
        is_active: true,
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setLookupError(null);
        setLookupInfo(null);
        setShowModal(true);
    };

    const openEdit = (c: Customer) => {
        setEditing(c);
        form.setData({
            tipo_documento: c.tipo_documento,
            numero_documento: c.numero_documento ?? '',
            razon_social: c.razon_social,
            nombre_comercial: c.nombre_comercial ?? '',
            direccion: c.direccion ?? '',
            ubigeo: c.ubigeo ?? '',
            estado_contribuyente: c.estado_contribuyente ?? '',
            condicion_domicilio: c.condicion_domicilio ?? '',
            email: c.email ?? '',
            telefono: c.telefono ?? '',
            is_active: c.is_active,
        });
        form.clearErrors();
        setLookupError(null);
        setLookupInfo(null);
        setShowModal(true);
    };

    const consultar = () => {
        setLookupError(null);
        setLookupInfo(null);
        setLookupLoading(true);
        axios
            .get(route('clientes.consultar'), {
                params: {
                    tipo: form.data.tipo_documento,
                    numero: form.data.numero_documento,
                },
            })
            .then((r) => {
                const d = r.data.data;
                form.setData((prev) => ({
                    ...prev,
                    razon_social: d.razon_social || prev.razon_social,
                    nombre_comercial: d.nombre_comercial ?? prev.nombre_comercial,
                    direccion: d.direccion ?? prev.direccion,
                    ubigeo: d.ubigeo ?? prev.ubigeo,
                    estado_contribuyente: d.estado ?? prev.estado_contribuyente,
                    condicion_domicilio: d.condicion ?? prev.condicion_domicilio,
                }));
                if (d.estado || d.condicion) {
                    setLookupInfo({ estado: d.estado, condicion: d.condicion, habilitado: d.habilitado });
                }
            })
            .catch((e) =>
                setLookupError(
                    e.response?.data?.message ?? 'No se pudo consultar el documento.',
                ),
            )
            .finally(() => setLookupLoading(false));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('clientes.update', editing.id), opts);
        } else {
            form.post(route('clientes.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('clientes.destroy', toDelete.id), {
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
            <Head title="Clientes" />
            <div className="space-y-5">
                <PageHeader
                    title="Clientes"
                    description="Directorio de clientes y consulta SUNAT / RENIEC"
                    breadcrumbs={[{ label: 'Clientes' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo cliente
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <SearchInput
                                routeName="clientes.index"
                                filters={filters}
                                placeholder="Buscar por nombre o documento…"
                            />
                        </div>
                        <SelectField
                            placeholder="Todos los tipos"
                            value={filters.tipo_documento ?? ''}
                            onChange={(e) =>
                                router.get(
                                    route('clientes.index'),
                                    { ...filters, tipo_documento: e.target.value || undefined, page: undefined },
                                    { preserveState: true, preserveScroll: true, replace: true },
                                )
                            }
                            options={Object.entries(tipos).map(([value, label]) => ({
                                value,
                                label,
                            }))}
                        />
                    </div>

                    {customers.data.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="Sin clientes"
                            description="Registra tu primer cliente o consúltalo por RUC/DNI."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Documento</th>
                                        <th className="pb-3 pr-3 font-semibold">Nombre / Razón social</th>
                                        <th className="pb-3 pr-3 font-semibold">Contacto</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {customers.data.map((c) => (
                                        <tr key={c.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3">
                                                <Badge tone="slate">{tipos[c.tipo_documento]}</Badge>
                                                <span className="ml-2 font-mono text-xs">
                                                    {c.numero_documento ?? '—'}
                                                </span>
                                            </td>
                                            <td className="py-3 pr-3 font-medium text-slate-800 dark:text-slate-100">
                                                <span className="inline-flex items-center gap-2">
                                                    {c.razon_social}
                                                    {(c.estado_contribuyente &&
                                                        c.estado_contribuyente.toUpperCase() !== 'ACTIVO') ||
                                                    (c.condicion_domicilio &&
                                                        c.condicion_domicilio.toUpperCase() !== 'HABIDO') ? (
                                                        <Badge tone="rose">
                                                            {c.estado_contribuyente &&
                                                            c.estado_contribuyente.toUpperCase() !== 'ACTIVO'
                                                                ? c.estado_contribuyente
                                                                : 'No habido'}
                                                        </Badge>
                                                    ) : null}
                                                </span>
                                            </td>
                                            <td className="py-3 pr-3 text-xs text-slate-400">
                                                {c.telefono ?? ''}
                                                {c.telefono && c.email ? ' · ' : ''}
                                                {c.email ?? ''}
                                                {!c.telefono && !c.email ? '—' : ''}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                {c.is_active ? (
                                                    <Badge tone="emerald">Activo</Badge>
                                                ) : (
                                                    <Badge tone="slate">Inactivo</Badge>
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
                        links={customers.links}
                        from={customers.from}
                        to={customers.to}
                        total={customers.total}
                    />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar cliente' : 'Nuevo cliente'}
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
                                                    ? 'Consultar en SUNAT/RENIEC'
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
                                {lookupInfo && (
                                    <p
                                        className={`mt-1 text-xs ${
                                            lookupInfo.habilitado === false ? 'text-rose-600' : 'text-emerald-600'
                                        }`}
                                    >
                                        SUNAT: {lookupInfo.estado ?? '—'}
                                        {lookupInfo.condicion ? ` · ${lookupInfo.condicion}` : ''}
                                        {lookupInfo.habilitado === false
                                            ? ' — Contribuyente no habilitado para emitir comprobantes.'
                                            : ''}
                                    </p>
                                )}
                            </div>
                        </div>
                        <TextField
                            label="Nombre / Razón social"
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
                                label="Teléfono"
                                value={form.data.telefono}
                                onChange={(e) => form.setData('telefono', e.target.value)}
                            />
                        </div>
                        <TextField
                            label="Dirección"
                            value={form.data.direccion}
                            onChange={(e) => form.setData('direccion', e.target.value)}
                        />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                            {editing ? 'Guardar cambios' : 'Registrar cliente'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Desactivar cliente"
                message={`¿Desactivar a "${toDelete?.razon_social}"? No podrás venderle hasta reactivarlo; su historial de ventas y comprobantes se conserva.`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
