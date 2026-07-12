import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Button from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated, Sale } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Eye, HandCoins } from 'lucide-react';
import { FormEvent, useState } from 'react';

function numero(s: Sale): string {
    return `${s.serie}-${String(s.correlativo).padStart(6, '0')}`;
}
function saldoOf(s: Sale): number {
    return Math.round((Number(s.total) - Number(s.monto_cobrado ?? 0)) * 100) / 100;
}

export default function Receivables({
    sales,
    filters,
    totalPorCobrar,
    moneda,
}: {
    sales: Paginated<Sale>;
    filters: Record<string, string | undefined>;
    totalPorCobrar: number;
    moneda: string;
}) {
    const [target, setTarget] = useState<Sale | null>(null);

    const form = useForm({
        monto: '',
        metodo_pago: 'efectivo',
        fecha: new Date().toISOString().slice(0, 10),
        observacion: '',
    });

    const openCobro = (s: Sale) => {
        setTarget(s);
        form.setData({
            monto: saldoOf(s).toFixed(2),
            metodo_pago: 'efectivo',
            fecha: new Date().toISOString().slice(0, 10),
            observacion: '',
        });
        form.clearErrors();
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (!target) return;
        form.post(route('cxc.cobrar', target.id), {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Cuentas por cobrar" />
            <div className="space-y-5">
                <PageHeader
                    title="Cuentas por cobrar"
                    description="Ventas al crédito pendientes de cobro"
                    breadcrumbs={[{ label: 'Caja y Finanzas' }, { label: 'Cuentas por cobrar' }]}
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Total por cobrar</p>
                        <p className="mt-1 text-2xl font-bold text-emerald-600">
                            {formatCurrency(totalPorCobrar, moneda)}
                        </p>
                    </Card>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="cxc.index"
                            filters={filters}
                            placeholder="Buscar por cliente…"
                        />
                    </div>

                    {sales.data.length === 0 ? (
                        <EmptyState
                            icon={CheckCircle2}
                            title="¡Todo cobrado!"
                            description="No hay ventas al crédito pendientes."
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Comprobante</th>
                                        <th className="pb-3 pr-3 font-semibold">Cliente</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Saldo</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {sales.data.map((s) => (
                                        <tr key={s.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs font-semibold">
                                                {numero(s)}
                                            </td>
                                            <td className="py-3 pr-3">{s.cliente_nombre}</td>
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(s.fecha).toLocaleDateString('es-PE')}
                                            </td>
                                            <td className="py-3 pr-3 text-right text-slate-500">
                                                {formatCurrency(Number(s.total), moneda)}
                                            </td>
                                            <td className="py-3 pr-3 text-right font-semibold text-emerald-600">
                                                {formatCurrency(saldoOf(s), moneda)}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Link
                                                        href={route('ventas.show', s.id)}
                                                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                    >
                                                        <Eye className="size-4" />
                                                    </Link>
                                                    <Button size="sm" onClick={() => openCobro(s)}>
                                                        <HandCoins className="size-4" /> Cobrar
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination
                        links={sales.links}
                        from={sales.from}
                        to={sales.to}
                        total={sales.total}
                    />
                </div>
            </div>

            <Modal show={!!target} onClose={() => setTarget(null)} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h3 className="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Registrar cobro
                    </h3>
                    <p className="mb-4 text-sm text-slate-400">
                        {target?.cliente_nombre} · Saldo:{' '}
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
                                    { value: 'tarjeta', label: 'Tarjeta' },
                                    { value: 'transferencia', label: 'Transferencia' },
                                    { value: 'yape', label: 'Yape' },
                                    { value: 'plin', label: 'Plin' },
                                    { value: 'deposito', label: 'Depósito' },
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
                            Registrar cobro
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
