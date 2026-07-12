import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { CashSession } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Lock, Plus } from 'lucide-react';
import { FormEvent, useState } from 'react';

const categoriaLabel: Record<string, string> = {
    venta: 'Venta',
    cobro: 'Cobro',
    pago_compra: 'Pago compra',
    gasto: 'Gasto',
    retiro: 'Retiro',
    deposito: 'Depósito',
    ajuste: 'Ajuste',
    otro: 'Otro',
};

export default function Show({
    session,
    ingresos,
    egresos,
    saldoEsperado,
    moneda,
}: {
    session: CashSession;
    ingresos: number;
    egresos: number;
    saldoEsperado: number;
    moneda: string;
}) {
    const abierta = session.estado === 'abierta';
    const [showMov, setShowMov] = useState(false);
    const [showClose, setShowClose] = useState(false);

    const movForm = useForm({
        tipo: 'egreso',
        categoria: 'gasto',
        concepto: '',
        monto: '',
    });

    const closeForm = useForm({ monto_contado: saldoEsperado.toFixed(2), observacion: '' });

    const submitMov = (e: FormEvent) => {
        e.preventDefault();
        movForm.post(route('caja.movimiento', session.id), {
            preserveScroll: true,
            onSuccess: () => {
                movForm.reset();
                setShowMov(false);
            },
        });
    };

    const submitClose = (e: FormEvent) => {
        e.preventDefault();
        closeForm.post(route('caja.cerrar', session.id), {
            preserveScroll: true,
            onSuccess: () => setShowClose(false),
        });
    };

    const diferenciaPreview =
        closeForm.data.monto_contado === ''
            ? 0
            : Number(closeForm.data.monto_contado) - saldoEsperado;

    return (
        <AuthenticatedLayout>
            <Head title={`Caja ${session.cash_register?.nombre ?? ''}`} />
            <div className="space-y-5">
                <PageHeader
                    title={session.cash_register?.nombre ?? 'Sesión de caja'}
                    description={`Aperturada el ${new Date(session.fecha_apertura).toLocaleString('es-PE')}`}
                    breadcrumbs={[
                        { label: 'Caja', href: route('caja.index') },
                        { label: `Sesión #${session.id}` },
                    ]}
                    actions={
                        <>
                            <Link href={route('caja.index')} className={buttonClasses('secondary')}>
                                <ArrowLeft className="size-4" /> Volver
                            </Link>
                            {abierta && (
                                <>
                                    <Button variant="secondary" onClick={() => setShowMov(true)}>
                                        <Plus className="size-4" /> Movimiento
                                    </Button>
                                    <Button onClick={() => setShowClose(true)}>
                                        <Lock className="size-4" /> Cerrar caja
                                    </Button>
                                </>
                            )}
                        </>
                    }
                />

                {/* Resumen */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Apertura</p>
                        <p className="mt-1 text-xl font-bold text-slate-800 dark:text-slate-100">
                            {formatCurrency(Number(session.monto_apertura), moneda)}
                        </p>
                    </Card>
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Ingresos</p>
                        <p className="mt-1 text-xl font-bold text-emerald-600">
                            {formatCurrency(ingresos, moneda)}
                        </p>
                    </Card>
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Egresos</p>
                        <p className="mt-1 text-xl font-bold text-rose-600">
                            {formatCurrency(egresos, moneda)}
                        </p>
                    </Card>
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {abierta ? 'Saldo esperado' : 'Contado (arqueo)'}
                        </p>
                        <p className="mt-1 text-xl font-bold text-slate-800 dark:text-slate-100">
                            {abierta
                                ? formatCurrency(saldoEsperado, moneda)
                                : formatCurrency(Number(session.monto_contado ?? 0), moneda)}
                        </p>
                    </Card>
                </div>

                {!abierta && (
                    <Card title="Resultado del arqueo">
                        <div className="flex flex-wrap gap-6 text-sm">
                            <div>
                                <p className="text-xs text-slate-400">Esperado</p>
                                <p className="font-semibold">
                                    {formatCurrency(Number(session.monto_esperado ?? 0), moneda)}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400">Contado</p>
                                <p className="font-semibold">
                                    {formatCurrency(Number(session.monto_contado ?? 0), moneda)}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400">Diferencia</p>
                                <p
                                    className={`font-bold ${
                                        Number(session.diferencia) === 0
                                            ? 'text-slate-600'
                                            : Number(session.diferencia) > 0
                                              ? 'text-emerald-600'
                                              : 'text-rose-600'
                                    }`}
                                >
                                    {formatCurrency(Number(session.diferencia ?? 0), moneda)}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-400">Cerrada</p>
                                <p className="font-semibold">
                                    {session.fecha_cierre
                                        ? new Date(session.fecha_cierre).toLocaleString('es-PE')
                                        : '—'}
                                </p>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Movimientos */}
                <Card title="Movimientos">
                    {session.movements && session.movements.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Hora</th>
                                        <th className="pb-3 pr-3 font-semibold">Concepto</th>
                                        <th className="pb-3 pr-3 font-semibold">Categoría</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Ingreso</th>
                                        <th className="pb-3 text-right font-semibold">Egreso</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {session.movements.map((m) => (
                                        <tr key={m.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(m.fecha).toLocaleTimeString('es-PE', {
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                })}
                                            </td>
                                            <td className="py-3 pr-3">
                                                {m.concepto}
                                                {m.referencia && (
                                                    <span className="block text-xs text-slate-400">
                                                        {m.referencia}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 pr-3">
                                                <Badge tone="slate">
                                                    {categoriaLabel[m.categoria] ?? m.categoria}
                                                </Badge>
                                            </td>
                                            <td className="py-3 pr-3 text-right font-medium text-emerald-600">
                                                {m.tipo === 'ingreso'
                                                    ? formatCurrency(Number(m.monto), moneda)
                                                    : ''}
                                            </td>
                                            <td className="py-3 text-right font-medium text-rose-600">
                                                {m.tipo === 'egreso'
                                                    ? formatCurrency(Number(m.monto), moneda)
                                                    : ''}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="py-6 text-center text-sm text-slate-400">
                            Sin movimientos registrados.
                        </p>
                    )}
                </Card>
            </div>

            {/* Modal movimiento */}
            <Modal show={showMov} onClose={() => setShowMov(false)} maxWidth="md">
                <form onSubmit={submitMov} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Nuevo movimiento
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                            <SelectField
                                label="Tipo"
                                value={movForm.data.tipo}
                                onChange={(e) => movForm.setData('tipo', e.target.value)}
                                options={[
                                    { value: 'ingreso', label: 'Ingreso' },
                                    { value: 'egreso', label: 'Egreso' },
                                ]}
                            />
                            <SelectField
                                label="Categoría"
                                value={movForm.data.categoria}
                                onChange={(e) => movForm.setData('categoria', e.target.value)}
                                options={[
                                    { value: 'gasto', label: 'Gasto' },
                                    { value: 'retiro', label: 'Retiro' },
                                    { value: 'deposito', label: 'Depósito' },
                                    { value: 'ajuste', label: 'Ajuste' },
                                    { value: 'otro', label: 'Otro' },
                                ]}
                            />
                        </div>
                        <TextField
                            label="Concepto"
                            required
                            value={movForm.data.concepto}
                            onChange={(e) => movForm.setData('concepto', e.target.value)}
                            error={movForm.errors.concepto}
                        />
                        <TextField
                            label="Monto"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={movForm.data.monto}
                            onChange={(e) => movForm.setData('monto', e.target.value)}
                            error={movForm.errors.monto}
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowMov(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={movForm.processing}>
                            Registrar
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal cierre / arqueo */}
            <Modal show={showClose} onClose={() => setShowClose(false)} maxWidth="md">
                <form onSubmit={submitClose} className="p-6">
                    <h3 className="mb-1 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Cerrar caja (arqueo)
                    </h3>
                    <p className="mb-4 text-sm text-slate-400">
                        Saldo esperado en efectivo: {formatCurrency(saldoEsperado, moneda)}
                    </p>
                    <div className="space-y-4">
                        <TextField
                            label="Efectivo contado"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={closeForm.data.monto_contado}
                            onChange={(e) => closeForm.setData('monto_contado', e.target.value)}
                            error={closeForm.errors.monto_contado}
                        />
                        <div className="rounded-lg bg-slate-50 px-4 py-3 text-sm dark:bg-slate-800/60">
                            <div className="flex justify-between">
                                <span className="text-slate-500 dark:text-slate-400">Diferencia</span>
                                <span
                                    className={`font-bold ${
                                        diferenciaPreview === 0
                                            ? 'text-slate-600 dark:text-slate-300'
                                            : diferenciaPreview > 0
                                              ? 'text-emerald-600'
                                              : 'text-rose-600'
                                    }`}
                                >
                                    {formatCurrency(diferenciaPreview, moneda)}
                                </span>
                            </div>
                        </div>
                        <TextField
                            label="Observación"
                            value={closeForm.data.observacion}
                            onChange={(e) => closeForm.setData('observacion', e.target.value)}
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowClose(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" variant="danger" disabled={closeForm.processing}>
                            <Lock className="size-4" /> Cerrar caja
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
