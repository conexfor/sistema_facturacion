import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import { SelectField, TextareaField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency, formatNumber } from '@/lib/format';
import { Sale } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Ban, Printer, Receipt } from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';

const tipoLabel: Record<string, string> = {
    boleta: 'Boleta de venta',
    factura: 'Factura',
    nota_venta: 'Nota de venta',
};

const estadoTone: Record<string, 'emerald' | 'amber' | 'rose'> = {
    pagada: 'emerald',
    pendiente: 'amber',
    anulada: 'rose',
};

export default function Show({
    sale,
    numero,
    puedeAnular = false,
    motivosCredito = {},
    devueltos = {},
}: {
    sale: Sale;
    numero: string;
    puedeAnular?: boolean;
    motivosCredito?: Record<string, string>;
    devueltos?: Record<string, number>;
}) {
    const moneda = sale.moneda;
    const [showAnular, setShowAnular] = useState(false);
    const [modo, setModo] = useState<'todo' | 'item'>('todo');
    const [cantidades, setCantidades] = useState<Record<number, string>>({});

    const motivoKeys = Object.keys(motivosCredito);
    const form = useForm({
        sale_id: sale.id,
        tipo: 'credito',
        cod_motivo: motivoKeys[0] ?? '01',
        des_motivo: motivosCredito[motivoKeys[0]] ?? 'Anulación de la operación',
    });

    // Regresa a la interfaz de la que se vino (Comprobantes, Ventas, etc.)
    // en vez de forzar siempre el historial de ventas.
    const volver = () => {
        if (typeof window !== 'undefined' && window.history.length > 1) {
            window.history.back();
        } else {
            router.visit(route('ventas.index'));
        }
    };

    const restante = (it: { id: number; cantidad: string }) =>
        Number(it.cantidad) - Number(devueltos[String(it.id)] ?? 0);

    const itemsDevolubles = (sale.items ?? []).filter((it) => restante(it) > 0.0001);

    const cambiarMotivo = (cod: string) =>
        form.setData((prev) => ({ ...prev, cod_motivo: cod, des_motivo: motivosCredito[cod] ?? prev.des_motivo }));

    const anular = (e: FormEvent) => {
        e.preventDefault();
        if (modo === 'item') {
            const items = itemsDevolubles
                .map((it) => ({ sale_item_id: it.id, cantidad: Number(cantidades[it.id] ?? 0) }))
                .filter((i) => i.cantidad > 0);
            form.transform((d) => ({ ...d, items }));
        } else {
            form.transform((d) => d);
        }
        form.post(route('notas.store'), { onSuccess: () => setShowAnular(false) });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Venta ${numero}`} />
            <div className="space-y-5">
                <PageHeader
                    title={`${tipoLabel[sale.tipo_comprobante]} ${numero}`}
                    breadcrumbs={[
                        { label: 'Ventas' },
                        { label: 'Historial', href: route('ventas.index') },
                        { label: numero },
                    ]}
                    actions={
                        <>
                            <button type="button" onClick={volver} className={buttonClasses('secondary')}>
                                <ArrowLeft className="size-4" /> Volver
                            </button>
                            <a
                                href={route('ventas.pdf', sale.id)}
                                target="_blank"
                                rel="noopener"
                                className={buttonClasses('primary')}
                            >
                                <Printer className="size-4" /> PDF A4
                            </a>
                            <a
                                href={route('ventas.pdf', { sale: sale.id, formato: 'ticket' })}
                                target="_blank"
                                rel="noopener"
                                className={buttonClasses('secondary')}
                            >
                                <Receipt className="size-4" /> Ticket
                            </a>
                            {puedeAnular && (
                                <Button variant="danger" onClick={() => setShowAnular(true)}>
                                    <Ban className="size-4" /> Anular / reembolsar
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card title="Detalle">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="pb-3 pr-3 font-semibold">Descripción</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">Cant.</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">P. unit.</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">Desc.</th>
                                            <th className="pb-3 text-right font-semibold">Importe</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {sale.items?.map((it) => (
                                            <tr key={it.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3">
                                                    <div className="font-medium text-slate-800 dark:text-slate-100">
                                                        {it.descripcion}
                                                    </div>
                                                    {it.product && (
                                                        <div className="font-mono text-xs text-slate-400">
                                                            {it.product.codigo}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-3 text-right">
                                                    {formatNumber(Number(it.cantidad))}
                                                </td>
                                                <td className="py-3 pr-3 text-right">
                                                    {formatCurrency(Number(it.precio_unitario), moneda)}
                                                </td>
                                                <td className="py-3 pr-3 text-right text-rose-500">
                                                    {Number(it.descuento) > 0
                                                        ? formatCurrency(Number(it.descuento), moneda)
                                                        : '—'}
                                                </td>
                                                <td className="py-3 text-right font-semibold">
                                                    {formatCurrency(Number(it.total), moneda)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <dl className="mt-4 ml-auto max-w-xs space-y-2 text-sm">
                                <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                    <dt>Subtotal (gravado)</dt>
                                    <dd>{formatCurrency(Number(sale.subtotal), moneda)}</dd>
                                </div>
                                <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                    <dt>IGV</dt>
                                    <dd>{formatCurrency(Number(sale.igv), moneda)}</dd>
                                </div>
                                {Number(sale.descuento_total) > 0 && (
                                    <div className="flex justify-between text-rose-500">
                                        <dt>Descuento</dt>
                                        <dd>− {formatCurrency(Number(sale.descuento_total), moneda)}</dd>
                                    </div>
                                )}
                                <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                    <dt>Total</dt>
                                    <dd>{formatCurrency(Number(sale.total), moneda)}</dd>
                                </div>
                                {Number(sale.percepcion_monto ?? 0) > 0 && (
                                    <>
                                        <div className="flex justify-between text-amber-600">
                                            <dt>Percepción ({Number(sale.percepcion_tasa)}%)</dt>
                                            <dd>{formatCurrency(Number(sale.percepcion_monto), moneda)}</dd>
                                        </div>
                                        <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                            <dt>Total a cobrar</dt>
                                            <dd>{formatCurrency(Number(sale.total) + Number(sale.percepcion_monto), moneda)}</dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Comprobante">
                            <dl className="space-y-3 text-sm">
                                <Row label="Estado">
                                    <Badge tone={estadoTone[sale.estado]}>{sale.estado}</Badge>
                                </Row>
                                <Row label="Fecha">
                                    {new Date(sale.fecha).toLocaleString('es-PE')}
                                </Row>
                                <Row label="Método de pago">
                                    <span className="capitalize">{sale.metodo_pago}</span>
                                </Row>
                                {sale.metodo_pago === 'efectivo' && (
                                    <>
                                        <Row label="Recibido">
                                            {formatCurrency(Number(sale.monto_recibido), moneda)}
                                        </Row>
                                        <Row label="Vuelto">
                                            {formatCurrency(Number(sale.vuelto), moneda)}
                                        </Row>
                                    </>
                                )}
                                <Row label="Almacén">{sale.warehouse?.nombre ?? '—'}</Row>
                                <Row label="Vendedor">{sale.user?.name ?? '—'}</Row>
                            </dl>
                        </Card>

                        <Card title="Cliente">
                            <p className="font-medium text-slate-800 dark:text-slate-100">
                                {sale.cliente_nombre}
                            </p>
                            {sale.cliente_documento && (
                                <p className="text-sm text-slate-400">
                                    Doc: {sale.cliente_documento}
                                </p>
                            )}
                            {sale.customer?.direccion && (
                                <p className="mt-1 text-sm text-slate-400">
                                    {sale.customer.direccion}
                                </p>
                            )}
                        </Card>
                    </div>
                </div>
            </div>

            <Modal show={showAnular} onClose={() => setShowAnular(false)} maxWidth="lg">
                <form onSubmit={anular} className="p-6">
                    <h3 className="text-base font-semibold text-slate-800 dark:text-slate-100">
                        Nota de crédito — {numero}
                    </h3>

                    {/* Modo */}
                    <div className="mt-4 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onClick={() => setModo('todo')}
                            className={`rounded-lg border px-3 py-2 text-sm font-medium ${
                                modo === 'todo'
                                    ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'
                            }`}
                        >
                            Anular todo
                        </button>
                        <button
                            type="button"
                            onClick={() => setModo('item')}
                            disabled={itemsDevolubles.length === 0}
                            className={`rounded-lg border px-3 py-2 text-sm font-medium disabled:opacity-40 ${
                                modo === 'item'
                                    ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'
                            }`}
                        >
                            Devolución por ítem
                        </button>
                    </div>

                    {modo === 'item' && (
                        <div className="mt-4 overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="p-2 font-semibold">Producto</th>
                                        <th className="p-2 text-right font-semibold">Disponible</th>
                                        <th className="p-2 text-center font-semibold">Devolver</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {itemsDevolubles.map((it) => {
                                        const max = restante(it);
                                        return (
                                            <tr key={it.id}>
                                                <td className="p-2">{it.descripcion}</td>
                                                <td className="p-2 text-right text-slate-500">
                                                    {formatNumber(max)}
                                                </td>
                                                <td className="p-2 text-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={max}
                                                        step="0.0001"
                                                        value={cantidades[it.id] ?? ''}
                                                        onChange={(e) =>
                                                            setCantidades((p) => ({ ...p, [it.id]: e.target.value }))
                                                        }
                                                        className="w-24 rounded-lg border-slate-300 py-1 text-center text-sm dark:border-slate-700 dark:bg-slate-800"
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {modo === 'todo' && (
                        <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">
                            Se anula <strong>toda</strong> la venta, se devuelve el stock
                            {sale.metodo_pago === 'efectivo' && Number(sale.monto_cobrado ?? 0) > 0
                                ? ' y se registra el reembolso en la caja abierta'
                                : ''}
                            .
                        </p>
                    )}

                    <div className="mt-4 space-y-4">
                        <SelectField
                            label="Motivo"
                            value={form.data.cod_motivo}
                            onChange={(e) => cambiarMotivo(e.target.value)}
                            options={Object.entries(motivosCredito).map(([value, label]) => ({
                                value,
                                label,
                            }))}
                        />
                        <TextareaField
                            label="Descripción"
                            rows={2}
                            value={form.data.des_motivo}
                            onChange={(e) => form.setData('des_motivo', e.target.value)}
                            error={form.errors.des_motivo}
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowAnular(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" variant="danger" disabled={form.processing}>
                            <Ban className="size-4" /> Emitir nota de crédito
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-2">
            <dt className="text-slate-500 dark:text-slate-400">{label}</dt>
            <dd className="font-medium text-slate-700 dark:text-slate-200">{children}</dd>
        </div>
    );
}
