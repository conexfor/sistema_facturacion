import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency, formatNumber } from '@/lib/format';
import { Quote } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, Receipt, Trash2, X } from 'lucide-react';
import { FormEvent, useState } from 'react';

const estadoTone: Record<string, 'slate' | 'sky' | 'emerald' | 'rose'> = {
    pendiente: 'slate',
    aprobada: 'sky',
    convertida: 'emerald',
    anulada: 'rose',
};

export default function Show({
    quote,
    warehouses,
    moneda,
}: {
    quote: Quote;
    warehouses: { id: number; nombre: string }[];
    moneda: string;
}) {
    const numero = `${quote.serie}-${String(quote.correlativo).padStart(6, '0')}`;
    const esCot = quote.tipo === 'cotizacion';
    const editable = quote.estado !== 'convertida' && quote.estado !== 'anulada';
    const [showConv, setShowConv] = useState(false);
    const [showDel, setShowDel] = useState(false);

    const conv = useForm({
        tipo_comprobante: 'boleta',
        warehouse_id: warehouses[0] ? String(warehouses[0].id) : '',
        metodo_pago: 'efectivo',
        monto_recibido: '',
    });

    const convertir = (e: FormEvent) => {
        e.preventDefault();
        conv.post(route('documentos.convertir', quote.id), { onSuccess: () => setShowConv(false) });
    };

    const cambiarEstado = (estado: string) =>
        router.patch(route('documentos.estado', quote.id), { estado }, { preserveScroll: true });

    return (
        <AuthenticatedLayout>
            <Head title={numero} />
            <div className="space-y-5">
                <PageHeader
                    title={`${esCot ? 'Cotización' : 'Pedido'} ${numero}`}
                    description={quote.cliente_nombre}
                    breadcrumbs={[
                        { label: 'Ventas' },
                        {
                            label: esCot ? 'Cotizaciones' : 'Pedidos',
                            href: route(esCot ? 'cotizaciones.index' : 'pedidos.index'),
                        },
                        { label: numero },
                    ]}
                    actions={
                        <>
                            <Link
                                href={route(esCot ? 'cotizaciones.index' : 'pedidos.index')}
                                className={buttonClasses('secondary')}
                            >
                                <ArrowLeft className="size-4" /> Volver
                            </Link>
                            {editable && (
                                <Button onClick={() => setShowConv(true)}>
                                    <Receipt className="size-4" /> Convertir en venta
                                </Button>
                            )}
                        </>
                    }
                />

                {quote.estado === 'convertida' && quote.sale && (
                    <div className="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-900/50 dark:bg-emerald-900/20">
                        <span className="text-emerald-700 dark:text-emerald-300">
                            Convertida en la venta{' '}
                            <span className="font-mono font-semibold">
                                {quote.sale.serie}-{String(quote.sale.correlativo).padStart(6, '0')}
                            </span>
                        </span>
                        <Link href={route('ventas.show', quote.sale.id)} className="font-semibold text-emerald-700 underline dark:text-emerald-300">
                            Ver venta <ArrowRight className="inline size-3.5" />
                        </Link>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card title="Detalle">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="pb-3 pr-3 font-semibold">Producto</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">Cant.</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">P. unit.</th>
                                            <th className="pb-3 text-right font-semibold">Importe</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {quote.items?.map((it) => (
                                            <tr key={it.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3">
                                                    <div className="font-medium text-slate-800 dark:text-slate-100">{it.descripcion}</div>
                                                    {it.product && <div className="font-mono text-xs text-slate-400">{it.product.codigo}</div>}
                                                </td>
                                                <td className="py-3 pr-3 text-right">{formatNumber(Number(it.cantidad))}</td>
                                                <td className="py-3 pr-3 text-right">{formatCurrency(Number(it.precio_unitario), moneda)}</td>
                                                <td className="py-3 text-right font-semibold">{formatCurrency(Number(it.total), moneda)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <dl className="mt-4 ml-auto max-w-xs space-y-2 text-sm">
                                <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                    <dt>Subtotal</dt>
                                    <dd>{formatCurrency(Number(quote.subtotal), moneda)}</dd>
                                </div>
                                <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                    <dt>IGV</dt>
                                    <dd>{formatCurrency(Number(quote.igv), moneda)}</dd>
                                </div>
                                <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                    <dt>Total</dt>
                                    <dd>{formatCurrency(Number(quote.total), moneda)}</dd>
                                </div>
                            </dl>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Información">
                            <dl className="space-y-3 text-sm">
                                <div className="flex items-center justify-between">
                                    <dt className="text-slate-500 dark:text-slate-400">Estado</dt>
                                    <dd><Badge tone={estadoTone[quote.estado]}>{quote.estado}</Badge></dd>
                                </div>
                                <div className="flex items-center justify-between">
                                    <dt className="text-slate-500 dark:text-slate-400">Fecha</dt>
                                    <dd>{new Date(quote.fecha).toLocaleDateString('es-PE')}</dd>
                                </div>
                                {quote.fecha_validez && (
                                    <div className="flex items-center justify-between">
                                        <dt className="text-slate-500 dark:text-slate-400">Válido hasta</dt>
                                        <dd>{new Date(quote.fecha_validez).toLocaleDateString('es-PE')}</dd>
                                    </div>
                                )}
                                <div className="flex items-center justify-between">
                                    <dt className="text-slate-500 dark:text-slate-400">Registró</dt>
                                    <dd>{quote.user?.name ?? '—'}</dd>
                                </div>
                            </dl>
                            {editable && (
                                <div className="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                                    {quote.estado !== 'aprobada' && (
                                        <Button size="sm" variant="secondary" onClick={() => cambiarEstado('aprobada')}>
                                            <Check className="size-4" /> Aprobar
                                        </Button>
                                    )}
                                    <Button size="sm" variant="secondary" onClick={() => cambiarEstado('anulada')}>
                                        <X className="size-4" /> Anular
                                    </Button>
                                    <Button size="sm" variant="danger" onClick={() => setShowDel(true)}>
                                        <Trash2 className="size-4" /> Eliminar
                                    </Button>
                                </div>
                            )}
                        </Card>
                    </div>
                </div>
            </div>

            <Modal show={showConv} onClose={() => setShowConv(false)} maxWidth="md">
                <form onSubmit={convertir} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">Convertir en venta</h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                            <SelectField
                                label="Comprobante"
                                value={conv.data.tipo_comprobante}
                                onChange={(e) => conv.setData('tipo_comprobante', e.target.value)}
                                options={[
                                    { value: 'boleta', label: 'Boleta' },
                                    { value: 'factura', label: 'Factura' },
                                    { value: 'nota_venta', label: 'Nota de venta' },
                                ]}
                            />
                            <SelectField
                                label="Método de pago"
                                value={conv.data.metodo_pago}
                                onChange={(e) => conv.setData('metodo_pago', e.target.value)}
                                options={[
                                    { value: 'efectivo', label: 'Efectivo' },
                                    { value: 'tarjeta', label: 'Tarjeta' },
                                    { value: 'transferencia', label: 'Transferencia' },
                                    { value: 'yape', label: 'Yape' },
                                    { value: 'credito', label: 'Crédito' },
                                ]}
                            />
                        </div>
                        <SelectField
                            label="Almacén"
                            value={conv.data.warehouse_id}
                            onChange={(e) => conv.setData('warehouse_id', e.target.value)}
                            error={conv.errors.warehouse_id}
                            options={warehouses.map((w) => ({ value: w.id, label: w.nombre }))}
                        />
                        {conv.data.metodo_pago === 'efectivo' && (
                            <TextField
                                label="Monto recibido"
                                type="number"
                                step="0.01"
                                value={conv.data.monto_recibido}
                                onChange={(e) => conv.setData('monto_recibido', e.target.value)}
                            />
                        )}
                        <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            Se generará una venta real y se descontará el stock del almacén.
                        </p>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowConv(false)}>Cancelar</Button>
                        <Button type="submit" disabled={conv.processing}>Generar venta</Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={showDel}
                title="Eliminar documento"
                message={`¿Eliminar ${quote.tipo} ${numero}?`}
                onConfirm={() => router.delete(route('documentos.destroy', quote.id), { onFinish: () => setShowDel(false) })}
                onClose={() => setShowDel(false)}
            />
        </AuthenticatedLayout>
    );
}
