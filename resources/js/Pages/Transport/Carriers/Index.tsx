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
import { Carrier, Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Building2, Loader2, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Index({
    carriers,
    filters,
    lookupEnabled,
}: {
    carriers: Paginated<Carrier>;
    filters: Record<string, string | undefined>;
    lookupEnabled: boolean;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Carrier | null>(null);
    const [toDelete, setToDelete] = useState<Carrier | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [lookupLoading, setLookupLoading] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);

    const form = useForm({
        ruc: '',
        razon_social: '',
        nro_mtc: '',
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

    const openEdit = (c: Carrier) => {
        setEditing(c);
        form.setData({
            ruc: c.ruc,
            razon_social: c.razon_social,
            nro_mtc: c.nro_mtc ?? '',
            telefono: c.telefono ?? '',
            is_active: c.is_active,
        });
        form.clearErrors();
        setLookupError(null);
        setShowModal(true);
    };

    const consultar = () => {
        setLookupError(null);
        setLookupLoading(true);
        axios
            .get(route('transporte.transportistas.consultar'), { params: { numero: form.data.ruc } })
            .then((r) => {
                const d = r.data.data;
                form.setData((prev) => ({ ...prev, razon_social: d.razon_social || prev.razon_social }));
            })
            .catch((e) => setLookupError(e.response?.data?.message ?? 'No se pudo consultar el RUC.'))
            .finally(() => setLookupLoading(false));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('transporte.transportistas.update', editing.id), opts);
        } else {
            form.post(route('transporte.transportistas.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('transporte.transportistas.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Transportistas" />
            <div className="space-y-5">
                <PageHeader
                    title="Transportistas"
                    description="Empresas de transporte para guías de transporte público"
                    breadcrumbs={[{ label: 'Transporte' }, { label: 'Transportistas' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo transportista
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="transporte.transportistas.index"
                            filters={filters}
                            placeholder="Buscar por razón social o RUC…"
                        />
                    </div>

                    {carriers.data.length === 0 ? (
                        <EmptyState
                            icon={Building2}
                            title="Sin transportistas"
                            description="Registra las empresas de transporte que contratas."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">RUC</th>
                                        <th className="pb-3 pr-3 font-semibold">Razón social</th>
                                        <th className="pb-3 pr-3 font-semibold">Registro MTC</th>
                                        <th className="pb-3 pr-3 font-semibold">Teléfono</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Guías</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {carriers.data.map((c) => (
                                        <tr key={c.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs">{c.ruc}</td>
                                            <td className="py-3 pr-3 font-medium text-slate-800 dark:text-slate-100">
                                                {c.razon_social}
                                            </td>
                                            <td className="py-3 pr-3 text-xs">{c.nro_mtc ?? '—'}</td>
                                            <td className="py-3 pr-3 text-xs text-slate-400">{c.telefono ?? '—'}</td>
                                            <td className="py-3 pr-3 text-right text-xs text-slate-500">
                                                {c.despatches_count ?? 0}
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

                    <Pagination links={carriers.links} from={carriers.from} to={carriers.to} total={carriers.total} />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar transportista' : 'Nuevo transportista'}
                    </h3>
                    <div className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                RUC<span className="ml-0.5 text-rose-500">*</span>
                            </label>
                            <div className="flex gap-2">
                                <input
                                    value={form.data.ruc}
                                    onChange={(e) => form.setData('ruc', e.target.value)}
                                    maxLength={11}
                                    className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                />
                                {form.data.ruc.length === 11 && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={consultar}
                                        disabled={lookupLoading}
                                        title={lookupEnabled ? 'Consultar en SUNAT' : 'Requiere configurar PERU_API_TOKEN'}
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
                            {form.errors.ruc && <p className="mt-1 text-xs text-rose-600">{form.errors.ruc}</p>}
                            {lookupError && <p className="mt-1 text-xs text-amber-600">{lookupError}</p>}
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
                                label="N° registro MTC"
                                value={form.data.nro_mtc}
                                onChange={(e) => form.setData('nro_mtc', e.target.value)}
                                error={form.errors.nro_mtc}
                            />
                            <TextField
                                label="Teléfono"
                                value={form.data.telefono}
                                onChange={(e) => form.setData('telefono', e.target.value)}
                                error={form.errors.telefono}
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
                            {editing ? 'Guardar cambios' : 'Registrar transportista'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar transportista"
                message={`¿Eliminar a "${toDelete?.razon_social}"?`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
