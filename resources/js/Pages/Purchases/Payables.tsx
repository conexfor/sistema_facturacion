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
import { formatCurrency } from '@/lib/format';
import { Paginated, Purchase } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, CreditCard, Eye } from 'lucide-react';
import { FormEvent, useState } from 'react';

function saldoOf(p: Purchase): number {
    return Math.round((Number(p.total) - Number(p.monto_pagado)) * 100) / 100;
}

export default function Payables({
    purchases,
    filters,
    totalDeuda,
    moneda,
}: {
    purchases: Paginated<Purchase>;
    filters: Record<string, string | undefined>;
    totalDeuda: number;
    moneda: string;
}) {
    const [target, setTarget] = useState<Purchase | null>(null);

    const form = useForm({
        monto: '',
        metodo_pago: 'efectivo',
        fecha: new Date().toISOString().slice(0, 10),
        observacion: '',
    });

    const openPay = (p: Purchase) => {
        setTarget(p);
        form.setData({
            monto: saldoOf(p).toFixed(2),
            metodo_pago: 'efectivo',
            fecha: new Date().toISOString().slice(0, 10),
            observacion: '',
        });
        form.clearErrors();
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (!target) return;
        form.post(route('compras.pagar', target.id), {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Cuentas por pagar" />
            <div className="space-y-5">
                <PageHeader
                    title="Cuentas por pagar"
                    description="Deudas pendientes con proveedores"
                    breadcrumbs={[{ label: 'Compras' }, { label: 'Cuentas por pagar' }]}
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card className="sm:col-span-1">
                        <p className="text-sm text-slate-500 dark:text-slate-400">Deuda total</p>
                        <p className="mt-1 text-2xl font-bold text-rose-600">
                            {formatCurrency(totalDeuda, moneda)}
                        </p>
                    </Card>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="compras.payables"
                            filters={filters}
                            placeholder="Buscar por proveedor…"
                        />
                    </div>

                    {purchases.data.length === 0 ? (
                        <EmptyState
                            icon={CheckCircle2}
                            title="¡Todo al día!"
                            description="No tienes cuentas por pagar pendientes."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 pr-3 font-semibold">Proveedor</th>
                                        <th className="pb-3 pr-3 font-semibold">Vence</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Saldo</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {purchases.data.map((p) => {
                                        const vencido =
                                            p.fecha_vencimiento &&
                                            new Date(p.fecha_vencimiento) < new Date();
                                        return (
                                            <tr key={p.id} className="text-slate-700 dark:text-slate-300">
                                                <td className="py-3 pr-3 font-mono text-xs">
                                                    {[p.serie, p.numero].filter(Boolean).join('-') || '—'}
                                                </td>
                                                <td className="py-3 pr-3">
                                                    {p.supplier?.razon_social ?? '—'}
                                                </td>
                                                <td className="py-3 pr-3 text-xs">
                                                    {p.fecha_vencimiento ? (
                                                        <span className={vencido ? 'font-semibold text-rose-600' : 'text-slate-500'}>
                                                            {new Date(p.fecha_vencimiento).toLocaleDateString('es-PE')}
                                                            {vencido && (
                                                                <Badge tone="rose">
                                                                    <span className="ml-1">vencida</span>
                                                                </Badge>
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-3 text-right text-slate-500">
                                                    {formatCurrency(Number(p.total), moneda)}
                                                </td>
                                                <td className="py-3 pr-3 text-right font-semibold text-rose-600">
                                                    {formatCurrency(saldoOf(p), moneda)}
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link
                                                            href={route('compras.show', p.id)}
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                        <Button size="sm" onClick={() => openPay(p)}>
                                                            <CreditCard className="size-4" /> Pagar
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination
                        links={purchases.links}
                        from={purchases.from}
                        to={purchases.to}
                        total={purchases.total}
                    />
                </div>
            </div>

            <Modal show={!!target} onClose={() => setTarget(null)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Registrar pago
                    </h3>
                    <p className="mb-4 text-sm text-slate-400">
                        {target?.supplier?.razon_social} · Saldo:{' '}
                        {target && formatCurrency(saldoOf(target), moneda)}
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
                        <Button type="button" variant="secondary" onClick={() => setTarget(null)}>
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
