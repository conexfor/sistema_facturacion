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
import { Driver, Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { IdCard, Loader2, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

function hoy(): string {
    return new Date().toISOString().slice(0, 10);
}

function fecha(iso: string): string {
    return iso.slice(0, 10).split('-').reverse().join('/');
}

/** Estado de la licencia: vencida, por vencer (30 días) o vigente. */
function estadoLicencia(vence: string | null): { label: string; tone: 'rose' | 'amber' | 'emerald' | 'slate' } {
    if (!vence) return { label: 'Sin fecha', tone: 'slate' };
    const v = vence.slice(0, 10);
    if (v < hoy()) return { label: `Vencida ${fecha(v)}`, tone: 'rose' };
    const limite = new Date();
    limite.setDate(limite.getDate() + 30);
    if (v <= limite.toISOString().slice(0, 10)) return { label: `Vence ${fecha(v)}`, tone: 'amber' };
    return { label: `Vigente ${fecha(v)}`, tone: 'emerald' };
}

export default function Index({
    drivers,
    tiposDoc,
    filters,
    lookupEnabled,
}: {
    drivers: Paginated<Driver>;
    tiposDoc: Record<string, string>;
    filters: Record<string, string | undefined>;
    lookupEnabled: boolean;
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Driver | null>(null);
    const [toDelete, setToDelete] = useState<Driver | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [lookupLoading, setLookupLoading] = useState(false);
    const [lookupError, setLookupError] = useState<string | null>(null);

    const form = useForm({
        tipo_doc: '1',
        num_doc: '',
        nombres: '',
        apellidos: '',
        licencia: '',
        licencia_vence: '',
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

    const openEdit = (d: Driver) => {
        setEditing(d);
        form.setData({
            tipo_doc: d.tipo_doc,
            num_doc: d.num_doc,
            nombres: d.nombres,
            apellidos: d.apellidos,
            licencia: d.licencia,
            licencia_vence: d.licencia_vence?.slice(0, 10) ?? '',
            telefono: d.telefono ?? '',
            is_active: d.is_active,
        });
        form.clearErrors();
        setLookupError(null);
        setShowModal(true);
    };

    const consultar = () => {
        setLookupError(null);
        setLookupLoading(true);
        axios
            .get(route('transporte.conductores.consultar'), { params: { numero: form.data.num_doc } })
            .then((r) => {
                const d = r.data.data;
                form.setData((prev) => ({
                    ...prev,
                    nombres: d.nombres || prev.nombres,
                    apellidos: d.apellidos || prev.apellidos,
                }));
            })
            .catch((e) => setLookupError(e.response?.data?.message ?? 'No se pudo consultar el DNI.'))
            .finally(() => setLookupLoading(false));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => setShowModal(false) };
        if (editing) {
            form.put(route('transporte.conductores.update', editing.id), opts);
        } else {
            form.post(route('transporte.conductores.store'), opts);
        }
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setDeleting(true);
        router.delete(route('transporte.conductores.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    };

    const canLookup = form.data.tipo_doc === '1' && form.data.num_doc.length === 8;

    return (
        <AuthenticatedLayout>
            <Head title="Conductores" />
            <div className="space-y-5">
                <PageHeader
                    title="Conductores"
                    description="Choferes habilitados y vigencia de sus licencias"
                    breadcrumbs={[{ label: 'Transporte' }, { label: 'Conductores' }]}
                    actions={
                        <Button onClick={openCreate}>
                            <Plus className="size-4" /> Nuevo conductor
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="transporte.conductores.index"
                            filters={filters}
                            placeholder="Buscar por nombre, documento o licencia…"
                        />
                    </div>

                    {drivers.data.length === 0 ? (
                        <EmptyState
                            icon={IdCard}
                            title="Sin conductores"
                            description="Registra a tus conductores para emitir guías sin volver a escribir sus datos."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Conductor</th>
                                        <th className="pb-3 pr-3 font-semibold">Documento</th>
                                        <th className="pb-3 pr-3 font-semibold">Licencia</th>
                                        <th className="pb-3 pr-3 font-semibold">Teléfono</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {drivers.data.map((d) => {
                                        const lic = estadoLicencia(d.licencia_vence);
                                        return (
                                            <tr key={d.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3 font-medium text-slate-800 dark:text-slate-100">
                                                    {d.apellidos}, {d.nombres}
                                                </td>
                                                <td className="py-3 pr-3">
                                                    <Badge tone="slate">{tiposDoc[d.tipo_doc] ?? d.tipo_doc}</Badge>
                                                    <span className="ml-2 font-mono text-xs">{d.num_doc}</span>
                                                </td>
                                                <td className="py-3 pr-3">
                                                    <span className="font-mono text-xs">{d.licencia}</span>
                                                    <div className="mt-1">
                                                        <Badge tone={lic.tone}>{lic.label}</Badge>
                                                    </div>
                                                </td>
                                                <td className="py-3 pr-3 text-xs text-slate-400">{d.telefono ?? '—'}</td>
                                                <td className="py-3 pr-3 text-center">
                                                    {d.is_active ? (
                                                        <Badge tone="emerald">Activo</Badge>
                                                    ) : (
                                                        <Badge tone="slate">Inactivo</Badge>
                                                    )}
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <button
                                                            onClick={() => openEdit(d)}
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => setToDelete(d)}
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={drivers.links} from={drivers.from} to={drivers.to} total={drivers.total} />
                </div>
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        {editing ? 'Editar conductor' : 'Nuevo conductor'}
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <SelectField
                                label="Tipo doc."
                                value={form.data.tipo_doc}
                                onChange={(e) => form.setData('tipo_doc', e.target.value)}
                                error={form.errors.tipo_doc}
                                options={Object.entries(tiposDoc).map(([value, label]) => ({ value, label }))}
                            />
                            <div className="sm:col-span-2">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Número de documento<span className="ml-0.5 text-rose-500">*</span>
                                </label>
                                <div className="flex gap-2">
                                    <input
                                        value={form.data.num_doc}
                                        onChange={(e) => form.setData('num_doc', e.target.value)}
                                        className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {canLookup && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={consultar}
                                            disabled={lookupLoading}
                                            title={lookupEnabled ? 'Consultar en RENIEC' : 'Requiere configurar PERU_API_TOKEN'}
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
                                {form.errors.num_doc && (
                                    <p className="mt-1 text-xs text-rose-600">{form.errors.num_doc}</p>
                                )}
                                {lookupError && <p className="mt-1 text-xs text-amber-600">{lookupError}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Nombres"
                                required
                                value={form.data.nombres}
                                onChange={(e) => form.setData('nombres', e.target.value)}
                                error={form.errors.nombres}
                            />
                            <TextField
                                label="Apellidos"
                                required
                                value={form.data.apellidos}
                                onChange={(e) => form.setData('apellidos', e.target.value)}
                                error={form.errors.apellidos}
                            />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <TextField
                                label="N° de licencia"
                                required
                                value={form.data.licencia}
                                onChange={(e) => form.setData('licencia', e.target.value.toUpperCase())}
                                error={form.errors.licencia}
                                hint="Ej. Q12345678"
                            />
                            <TextField
                                label="Vence"
                                type="date"
                                value={form.data.licencia_vence}
                                onChange={(e) => form.setData('licencia_vence', e.target.value)}
                                error={form.errors.licencia_vence}
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
                            {editing ? 'Guardar cambios' : 'Registrar conductor'}
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar conductor"
                message={`¿Eliminar a "${toDelete?.nombres} ${toDelete?.apellidos}"?`}
                processing={deleting}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
