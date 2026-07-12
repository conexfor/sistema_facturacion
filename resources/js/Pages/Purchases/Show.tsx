import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency, formatNumber } from '@/lib/format';
import { Purchase } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CreditCard, Printer } from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';

const estadoTone: Record<string, 'emerald' | 'amber' | 'rose'> = {
    pagada: 'emerald',
    parcial: 'amber',
    pendiente: 'rose',
};

export default function Show({ purchase, saldo }: { purchase: Purchase; saldo: number }) {
    const moneda = purchase.moneda;
    const [showPay, setShowPay] = useState(false);

    const form = useForm({
        monto: saldo.toFixed(2),
        metodo_pago: 'efectivo',
        fecha: new Date().toISOString().slice(0, 10),
        observacion: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('compras.pagar', purchase.id), {
            preserveScroll: true,
            onSuccess: () => setShowPay(false),
        });
    };

    const numero = [purchase.serie, purchase.numero].filter(Boolean).join('-') || `#${purchase.id}`;

    return (
        <AuthenticatedLayout>
            <Head title={`Compra ${numero}`} />
            <div className="space-y-5">
                <PageHeader
                    title={`Compra ${numero}`}
                    description={purchase.supplier?.razon_social ?? 'Sin proveedor'}
                    breadcrumbs={[
                        { label: 'Compras', href: route('compras.index') },
                        { label: numero },
                    ]}
                    actions={
                        <>
                            <Link href={route('compras.index')} className={buttonClasses('secondary')}>
                                <ArrowLeft className="size-4" /> Volver
                            </Link>
                            <a
                                href={route('compras.pdf', purchase.id)}
                                target="_blank"
                                rel="noopener"
                                className={buttonClasses('secondary')}
                            >
                                <Printer className="size-4" /> PDF
                            </a>
                            {saldo > 0 && (
                                <Button onClick={() => setShowPay(true)}>
                                    <CreditCard className="size-4" /> Registrar pago
                                </Button>
                            )}
                        </>
                    }
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card title="Detalle">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="pb-3 pr-3 font-semibold">Producto</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">Cant.</th>
                                            <th className="pb-3 pr-3 text-right font-semibold">Costo</th>
                                            <th className="pb-3 text-right font-semibold">Importe</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {purchase.items?.map((it) => (
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
                                                    {formatCurrency(Number(it.costo_unitario), moneda)}
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
                                    <dt>Subtotal (valor)</dt>
                                    <dd>{formatCurrency(Number(purchase.subtotal), moneda)}</dd>
                                </div>
                                <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                    <dt>IGV</dt>
                                    <dd>{formatCurrency(Number(purchase.igv), moneda)}</dd>
                                </div>
                                <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                    <dt>Total comprobante</dt>
                                    <dd>{formatCurrency(Number(purchase.total), moneda)}</dd>
                                </div>
                                {Number(purchase.percepcion_monto ?? 0) > 0 && (
                                    <>
                                        <div className="flex justify-between text-amber-600">
                                            <dt>Percepción ({Number(purchase.percepcion_tasa)}%)</dt>
                                            <dd>{formatCurrency(Number(purchase.percepcion_monto), moneda)}</dd>
                                        </div>
                                        <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                            <dt>Total a pagar</dt>
                                            <dd>{formatCurrency(Number(purchase.total) + Number(purchase.percepcion_monto), moneda)}</dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </Card>

                        <Card title="Pagos">
                            {purchase.payments && purchase.payments.length > 0 ? (
                                <table className="w-full text-sm">
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {purchase.payments.map((pay) => (
                                            <tr key={pay.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-2 text-xs text-slate-500">
                                                    {new Date(pay.fecha).toLocaleDateString('es-PE')}
                                                </td>
                                                <td className="py-2 capitalize">{pay.metodo_pago}</td>
                                                <td className="py-2 text-xs text-slate-400">
                                                    {pay.user?.name}
                                                </td>
                                                <td className="py-2 text-right font-semibold">
                                                    {formatCurrency(Number(pay.monto), moneda)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <p className="py-4 text-center text-sm text-slate-400">
                                    No hay pagos registrados.
                                </p>
                            )}
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Estado de pago">
                            <dl className="space-y-3 text-sm">
                                <Row label="Estado">
                                    <Badge tone={estadoTone[purchase.estado_pago]}>
                                        {purchase.estado_pago}
                                    </Badge>
                                </Row>
                                <Row label="Pagado">
                                    {formatCurrency(Number(purchase.monto_pagado), moneda)}
                                </Row>
                                <Row label="Saldo">
                                    <span className={saldo > 0 ? 'text-rose-600' : 'text-emerald-600'}>
                                        {formatCurrency(saldo, moneda)}
                                    </span>
                                </Row>
                                {purchase.fecha_vencimiento && (
                                    <Row label="Vence">
                                        {new Date(purchase.fecha_vencimiento).toLocaleDateString('es-PE')}
                                    </Row>
                                )}
                            </dl>
                        </Card>

                        <Card title="Información">
                            <dl className="space-y-3 text-sm">
                                <Row label="Almacén">{purchase.warehouse?.nombre ?? '—'}</Row>
                                <Row label="Registró">{purchase.user?.name ?? '—'}</Row>
                                <Row label="Fecha">
                                    {new Date(purchase.fecha).toLocaleDateString('es-PE')}
                                </Row>
                            </dl>
                            {purchase.supplier && (
                                <div className="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                                    <p className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {purchase.supplier.razon_social}
                                    </p>
                                    <p className="text-xs text-slate-400">
                                        {purchase.supplier.numero_documento}
                                    </p>
                                </div>
                            )}
                        </Card>
                    </div>
                </div>
            </div>

            <Modal show={showPay} onClose={() => setShowPay(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Registrar pago
                    </h3>
                    <p className="mb-4 text-sm text-slate-400">
                        Saldo pendiente: {formatCurrency(saldo, moneda)}
                    </p>
                    <div className="space-y-4">
                        <TextField
                            label="Monto"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={form.data.monto}
                            onChange={(e) => form.setData('monto', e.target.value)}
                            error={form.errors.monto}
                        />
                        <div className="grid grid-cols-2 gap-3">
                            <SelectField
                                label="Método"
                                value={form.data.metodo_pago}
                                onChange={(e) => form.setData('metodo_pago', e.target.value)}
                                options={[
                                    { value: 'efectivo', label: 'Efectivo' },
                                    { value: 'transferencia', label: 'Transferencia' },
                                    { value: 'deposito', label: 'Depósito' },
                                    { value: 'cheque', label: 'Cheque' },
                                    { value: 'tarjeta', label: 'Tarjeta' },
                                    { value: 'yape', label: 'Yape' },
                                    { value: 'plin', label: 'Plin' },
                                ]}
                            />
                            <TextField
                                label="Fecha"
                                type="date"
                                value={form.data.fecha}
                                onChange={(e) => form.setData('fecha', e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowPay(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Registrar pago
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
