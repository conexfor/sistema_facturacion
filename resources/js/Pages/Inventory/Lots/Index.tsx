import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber } from '@/lib/format';
import { Paginated, ProductLot } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { AlertTriangle, CalendarClock, PackagePlus, Search } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface ProductOpt {
    id: number;
    codigo: string;
    nombre: string;
    precio_compra: string;
}

const estadoMeta: Record<string, { label: string; tone: 'emerald' | 'amber' | 'rose' | 'slate' }> = {
    vigente: { label: 'Vigente', tone: 'emerald' },
    por_vencer: { label: 'Por vencer', tone: 'amber' },
    vencido: { label: 'Vencido', tone: 'rose' },
    sin_fecha: { label: 'Sin fecha', tone: 'slate' },
};

export default function Index({
    lots,
    filters,
    alertas,
    warehouses,
}: {
    lots: Paginated<ProductLot>;
    filters: Record<string, string | undefined>;
    alertas: { por_vencer: number; vencidos: number };
    warehouses: { id: number; nombre: string }[];
}) {
    const [show, setShow] = useState(false);
    const [merma, setMerma] = useState<ProductLot | null>(null);
    const [producto, setProducto] = useState<ProductOpt | null>(null);
    const [pQuery, setPQuery] = useState('');
    const [pResults, setPResults] = useState<ProductOpt[]>([]);

    const form = useForm({
        product_id: 0,
        warehouse_id: warehouses[0] ? String(warehouses[0].id) : '',
        lote: '',
        fecha_vencimiento: '',
        cantidad: '',
        costo: '',
    });
    const mermaForm = useForm({ cantidad: '', motivo: '' });

    useEffect(() => {
        if (!show) return;
        const t = setTimeout(() => {
            axios
                .get(route('inventario.lotes.buscar-productos'), { params: { q: pQuery } })
                .then((r) => setPResults(r.data))
                .catch(() => setPResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [pQuery, show]);

    const open = () => {
        setProducto(null);
        setPQuery('');
        form.reset();
        form.clearErrors();
        setShow(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('inventario.lotes.store'), { preserveScroll: true, onSuccess: () => setShow(false) });
    };

    const submitMerma = (e: FormEvent) => {
        e.preventDefault();
        if (!merma) return;
        mermaForm.post(route('inventario.lotes.merma', merma.id), {
            preserveScroll: true,
            onSuccess: () => setMerma(null),
        });
    };

    const applyEstado = (estado: string) =>
        router.get(route('inventario.lotes.index'), { ...filters, estado: estado || undefined, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true });

    return (
        <AuthenticatedLayout>
            <Head title="Lotes y vencimiento" />
            <div className="space-y-5">
                <PageHeader
                    title="Lotes y vencimiento"
                    description="Trazabilidad de lotes y control de fechas de vencimiento"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Lotes' }]}
                    actions={
                        <Button onClick={open}>
                            <PackagePlus className="size-4" /> Ingresar lote
                        </Button>
                    }
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Card>
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30">
                                <CalendarClock className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Por vencer (≤30 días)</p>
                                <p className="text-2xl font-bold text-amber-600">{alertas.por_vencer}</p>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-900/30">
                                <AlertTriangle className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Vencidos</p>
                                <p className="text-2xl font-bold text-rose-600">{alertas.vencidos}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <SearchInput routeName="inventario.lotes.index" filters={filters} placeholder="Buscar por lote o producto…" />
                        </div>
                        <SelectField
                            placeholder="Todos los estados"
                            value={filters.estado ?? ''}
                            onChange={(e) => applyEstado(e.target.value)}
                            options={[
                                { value: 'vigente', label: 'Vigentes' },
                                { value: 'por_vencer', label: 'Por vencer' },
                                { value: 'vencido', label: 'Vencidos' },
                            ]}
                        />
                    </div>

                    {lots.data.length === 0 ? (
                        <EmptyState
                            icon={CalendarClock}
                            title="Sin lotes"
                            description="Ingresa lotes con su fecha de vencimiento para hacer seguimiento."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Producto</th>
                                        <th className="pb-3 pr-3 font-semibold">Almacén</th>
                                        <th className="pb-3 pr-3 font-semibold">Lote</th>
                                        <th className="pb-3 pr-3 font-semibold">Vencimiento</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Cantidad</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {lots.data.map((l) => {
                                        const meta = estadoMeta[l.estado_calc ?? 'sin_fecha'] ?? estadoMeta.sin_fecha;
                                        return (
                                            <tr key={l.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3">
                                                    <div className="font-medium text-slate-800 dark:text-slate-100">{l.product?.nombre}</div>
                                                    <div className="font-mono text-xs text-slate-400">{l.product?.codigo}</div>
                                                </td>
                                                <td className="py-3 pr-3 text-slate-500">{l.warehouse?.nombre}</td>
                                                <td className="py-3 pr-3 font-mono text-xs">{l.lote}</td>
                                                <td className="py-3 pr-3">
                                                    <span className="flex items-center gap-2">
                                                        {l.fecha_vencimiento
                                                            ? new Date(l.fecha_vencimiento).toLocaleDateString('es-PE')
                                                            : '—'}
                                                        <Badge tone={meta.tone}>{meta.label}</Badge>
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-3 text-right font-semibold">{formatNumber(Number(l.cantidad))}</td>
                                                <td className="py-3 text-right">
                                                    <button
                                                        onClick={() => {
                                                            setMerma(l);
                                                            mermaForm.reset();
                                                            mermaForm.clearErrors();
                                                        }}
                                                        className="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-rose-600 dark:hover:bg-slate-800"
                                                    >
                                                        Merma
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={lots.links} from={lots.from} to={lots.to} total={lots.total} />
                </div>
            </div>

            {/* Modal ingresar lote */}
            <Modal show={show} onClose={() => setShow(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">Ingresar lote</h3>
                    <div className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Producto</label>
                            {producto ? (
                                <div className="flex items-center justify-between rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                    <span className="text-sm font-medium text-slate-800 dark:text-slate-100">{producto.nombre}</span>
                                    <button type="button" onClick={() => { setProducto(null); form.setData('product_id', 0); }} className="text-xs text-slate-400 hover:text-rose-600">cambiar</button>
                                </div>
                            ) : (
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        value={pQuery}
                                        onChange={(e) => setPQuery(e.target.value)}
                                        placeholder="Buscar producto…"
                                        className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                    />
                                    {pResults.length > 0 && (
                                        <div className="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                            {pResults.map((p) => (
                                                <button
                                                    key={p.id}
                                                    type="button"
                                                    onClick={() => {
                                                        setProducto(p);
                                                        form.setData('product_id', p.id);
                                                        form.setData('costo', p.precio_compra);
                                                        setPResults([]);
                                                    }}
                                                    className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                                >
                                                    {p.nombre} <span className="font-mono text-xs text-slate-400">{p.codigo}</span>
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                    {form.errors.product_id && <p className="mt-1 text-xs text-rose-600">{form.errors.product_id}</p>}
                                </div>
                            )}
                        </div>
                        <SelectField
                            label="Almacén"
                            value={form.data.warehouse_id}
                            onChange={(e) => form.setData('warehouse_id', e.target.value)}
                            error={form.errors.warehouse_id}
                            options={warehouses.map((w) => ({ value: w.id, label: w.nombre }))}
                        />
                        <div className="grid grid-cols-2 gap-3">
                            <TextField label="Lote" value={form.data.lote} onChange={(e) => form.setData('lote', e.target.value)} error={form.errors.lote} />
                            <TextField label="Vencimiento" type="date" value={form.data.fecha_vencimiento} onChange={(e) => form.setData('fecha_vencimiento', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <TextField label="Cantidad" type="number" step="0.0001" min="0" value={form.data.cantidad} onChange={(e) => form.setData('cantidad', e.target.value)} error={form.errors.cantidad} />
                            <TextField label="Costo unit. (S/)" type="number" step="0.01" min="0" value={form.data.costo} onChange={(e) => form.setData('costo', e.target.value)} />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShow(false)}>Cancelar</Button>
                        <Button type="submit" disabled={form.processing || !form.data.product_id}>Ingresar</Button>
                    </div>
                </form>
            </Modal>

            {/* Modal merma */}
            <Modal show={!!merma} onClose={() => setMerma(null)} maxWidth="md">
                <form onSubmit={submitMerma} className="p-6">
                    <h3 className="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">Registrar merma</h3>
                    <p className="mb-4 text-sm text-slate-400">
                        Lote {merma?.lote} · Saldo: {merma && formatNumber(Number(merma.cantidad))}
                    </p>
                    <div className="space-y-4">
                        <TextField label="Cantidad a dar de baja" type="number" step="0.0001" min="0" value={mermaForm.data.cantidad} onChange={(e) => mermaForm.setData('cantidad', e.target.value)} error={mermaForm.errors.cantidad} />
                        <TextField label="Motivo" value={mermaForm.data.motivo} onChange={(e) => mermaForm.setData('motivo', e.target.value)} placeholder="Ej.: Vencido, dañado…" />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setMerma(null)}>Cancelar</Button>
                        <Button type="submit" variant="danger" disabled={mermaForm.processing}>Registrar merma</Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
