import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, Switch, TextareaField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Note, Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Download, FileCheck, FileMinus, Plus, Printer, Receipt, Search, X } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface VentaOpt {
    id: number;
    numero: string;
    tipo: string;
    cliente: string;
    total: number;
}

const estadoMeta: Record<string, { label: string; tone: 'emerald' | 'amber' | 'rose' | 'slate' | 'sky' }> = {
    no_enviado: { label: 'No enviado', tone: 'slate' },
    simulado: { label: 'Simulado', tone: 'sky' },
    aceptado: { label: 'Aceptado', tone: 'emerald' },
    observado: { label: 'Observado', tone: 'amber' },
    rechazado: { label: 'Rechazado', tone: 'rose' },
};

function numero(serie: string, correlativo: number): string {
    return `${serie}-${String(correlativo).padStart(6, '0')}`;
}

export default function Index({
    notas,
    filters,
    motivosCredito,
    motivosDebito,
    moneda,
}: {
    notas: Paginated<Note>;
    filters: Record<string, string | undefined>;
    motivosCredito: Record<string, string>;
    motivosDebito: Record<string, string>;
    moneda: string;
}) {
    const [show, setShow] = useState(false);
    const [venta, setVenta] = useState<VentaOpt | null>(null);
    const [vQuery, setVQuery] = useState('');
    const [vResults, setVResults] = useState<VentaOpt[]>([]);

    const form = useForm({
        sale_id: 0,
        tipo: 'credito',
        cod_motivo: '01',
        des_motivo: motivosCredito['01'] ?? '',
        concepto: '',
        monto: '',
        afecto_igv: true,
    });

    const motivos = form.data.tipo === 'credito' ? motivosCredito : motivosDebito;

    useEffect(() => {
        if (!show) return;
        const t = setTimeout(() => {
            axios
                .get(route('notas.buscar-ventas'), { params: { q: vQuery } })
                .then((r) => setVResults(r.data))
                .catch(() => setVResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [vQuery, show]);

    const open = () => {
        setVenta(null);
        setVQuery('');
        form.reset();
        form.clearErrors();
        setShow(true);
    };

    const cambiarTipo = (tipo: string) => {
        const cat = tipo === 'credito' ? motivosCredito : motivosDebito;
        const first = Object.keys(cat)[0];
        form.setData((prev) => ({
            ...prev,
            tipo,
            cod_motivo: first,
            des_motivo: cat[first] ?? '',
        }));
    };

    const cambiarMotivo = (cod: string) => {
        form.setData((prev) => ({ ...prev, cod_motivo: cod, des_motivo: motivos[cod] ?? prev.des_motivo }));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('notas.store'), { onSuccess: () => setShow(false) });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Notas de crédito y débito" />
            <div className="space-y-5">
                <PageHeader
                    title="Notas de crédito y débito"
                    description="Anulaciones, devoluciones y cargos sobre comprobantes"
                    breadcrumbs={[{ label: 'Facturación SUNAT' }, { label: 'Notas' }]}
                    actions={
                        <Button onClick={open}>
                            <Plus className="size-4" /> Nueva nota
                        </Button>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <SearchInput routeName="notas.index" filters={filters} placeholder="Buscar por número…" />
                        </div>
                        <SelectField
                            placeholder="Todos los tipos"
                            value={filters.tipo ?? ''}
                            onChange={(e) =>
                                router.get(
                                    route('notas.index'),
                                    { ...filters, tipo: e.target.value || undefined },
                                    { preserveState: true, preserveScroll: true, replace: true },
                                )
                            }
                            options={[
                                { value: 'credito', label: 'Crédito' },
                                { value: 'debito', label: 'Débito' },
                            ]}
                        />
                    </div>

                    {notas.data.length === 0 ? (
                        <EmptyState
                            icon={FileMinus}
                            title="Sin notas"
                            description="Emite notas de crédito (anulación/devolución) o débito sobre tus comprobantes."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Nota</th>
                                        <th className="pb-3 pr-3 font-semibold">Afecta a</th>
                                        <th className="pb-3 pr-3 font-semibold">Motivo</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {notas.data.map((n) => {
                                        const meta = estadoMeta[n.sunat_estado] ?? estadoMeta.no_enviado;
                                        return (
                                            <tr key={n.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3">
                                                    <span className="font-mono text-xs font-semibold">
                                                        {numero(n.serie, n.correlativo)}
                                                    </span>
                                                    <Badge tone={n.tipo === 'credito' ? 'rose' : 'amber'}>
                                                        <span className="ml-1">
                                                            {n.tipo === 'credito' ? 'Crédito' : 'Débito'}
                                                        </span>
                                                    </Badge>
                                                </td>
                                                <td className="py-3 pr-3 font-mono text-xs">
                                                    {n.sale ? numero(n.sale.serie, n.sale.correlativo) : '—'}
                                                </td>
                                                <td className="py-3 pr-3 text-slate-500">{n.des_motivo}</td>
                                                <td className="py-3 pr-3 text-right font-semibold">
                                                    {formatCurrency(Number(n.total), moneda)}
                                                </td>
                                                <td className="py-3 pr-3 text-center">
                                                    <Badge tone={meta.tone}>{meta.label}</Badge>
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <a
                                                            href={route('notas.pdf', n.id)}
                                                            target="_blank"
                                                            rel="noopener"
                                                            title="PDF A4"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Printer className="size-4" />
                                                        </a>
                                                        <a
                                                            href={route('notas.pdf', { note: n.id, formato: 'ticket' })}
                                                            target="_blank"
                                                            rel="noopener"
                                                            title="Ticket 80mm"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Receipt className="size-4" />
                                                        </a>
                                                        {n.sunat_xml_path && (
                                                            <a
                                                                href={route('notas.xml', n.id)}
                                                                title="Descargar XML"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                            >
                                                                <Download className="size-4" />
                                                            </a>
                                                        )}
                                                        {n.sunat_cdr_path && (
                                                            <a
                                                                href={route('notas.cdr', n.id)}
                                                                title="Descargar CDR"
                                                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-emerald-600 dark:hover:bg-slate-800"
                                                            >
                                                                <FileCheck className="size-4" />
                                                            </a>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={notas.links} from={notas.from} to={notas.to} total={notas.total} />
                </div>
            </div>

            <Modal show={show} onClose={() => setShow(false)} maxWidth="lg">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Nueva nota
                    </h3>

                    {/* Selección de venta */}
                    <div className="mb-4">
                        <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Comprobante afectado
                        </label>
                        {venta ? (
                            <div className="flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                <span>
                                    <span className="font-mono text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {venta.numero}
                                    </span>
                                    <span className="ml-2 text-xs text-slate-500">
                                        {venta.cliente} · {formatCurrency(venta.total, moneda)}
                                    </span>
                                </span>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setVenta(null);
                                        form.setData('sale_id', 0);
                                    }}
                                    className="rounded p-1 text-slate-400 hover:text-rose-600"
                                >
                                    <X className="size-4" />
                                </button>
                            </div>
                        ) : (
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={vQuery}
                                    onChange={(e) => setVQuery(e.target.value)}
                                    placeholder="Buscar venta por número o cliente…"
                                    className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                />
                                {vResults.length > 0 && (
                                    <div className="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                        {vResults.map((v) => (
                                            <button
                                                key={v.id}
                                                type="button"
                                                onClick={() => {
                                                    setVenta(v);
                                                    form.setData('sale_id', v.id);
                                                    setVResults([]);
                                                }}
                                                className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                            >
                                                <span className="font-mono font-medium text-slate-800 dark:text-slate-100">
                                                    {v.numero}
                                                </span>
                                                <span className="ml-2 text-xs text-slate-400">{v.cliente}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                                {form.errors.sale_id && (
                                    <p className="mt-1 text-xs text-rose-600">{form.errors.sale_id}</p>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField
                            label="Tipo de nota"
                            value={form.data.tipo}
                            onChange={(e) => cambiarTipo(e.target.value)}
                            options={[
                                { value: 'credito', label: 'Crédito (anula/devuelve)' },
                                { value: 'debito', label: 'Débito (cargo adicional)' },
                            ]}
                        />
                        <SelectField
                            label="Motivo"
                            value={form.data.cod_motivo}
                            onChange={(e) => cambiarMotivo(e.target.value)}
                            options={Object.entries(motivos).map(([value, label]) => ({ value, label }))}
                        />
                    </div>

                    <div className="mt-4">
                        <TextareaField
                            label="Descripción del motivo"
                            rows={2}
                            value={form.data.des_motivo}
                            onChange={(e) => form.setData('des_motivo', e.target.value)}
                            error={form.errors.des_motivo}
                        />
                    </div>

                    {form.data.tipo === 'credito' ? (
                        <p className="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            La nota de crédito <strong>anula la venta</strong> y devuelve el stock al almacén.
                        </p>
                    ) : (
                        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <TextField
                                    label="Concepto"
                                    value={form.data.concepto}
                                    onChange={(e) => form.setData('concepto', e.target.value)}
                                    error={form.errors.concepto}
                                    placeholder="Ej.: Interés por mora"
                                />
                            </div>
                            <TextField
                                label="Monto (S/)"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.data.monto}
                                onChange={(e) => form.setData('monto', e.target.value)}
                                error={form.errors.monto}
                            />
                            <Switch
                                label="Afecto a IGV"
                                checked={form.data.afecto_igv}
                                onChange={(v) => form.setData('afecto_igv', v)}
                            />
                        </div>
                    )}

                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShow(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing || !form.data.sale_id}>
                            Emitir nota
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
