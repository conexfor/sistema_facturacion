import Card from '@/Components/Card';
import Modal from '@/Components/Modal';
import Badge from '@/Components/ui/Badge';
import Button, { buttonClasses } from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import { SelectField, Switch, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { CashRegister, CashSession, Paginated } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Pencil, Plus, Settings, Trash2, Wallet } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Index({
    sessions,
    activa,
    registers,
    allRegisters,
    moneda,
}: {
    sessions: Paginated<CashSession>;
    activa: CashSession | null;
    registers: CashRegister[];
    allRegisters: CashRegister[];
    moneda: string;
}) {
    const [showRegs, setShowRegs] = useState(false);
    const [editReg, setEditReg] = useState<CashRegister | null>(null);
    const [delReg, setDelReg] = useState<CashRegister | null>(null);

    const openForm = useForm({
        cash_register_id: registers[0] ? String(registers[0].id) : '',
        monto_apertura: '0',
        observacion: '',
    });

    const regForm = useForm({ nombre: '', is_active: true });

    const abrir = (e: FormEvent) => {
        e.preventDefault();
        openForm.post(route('caja.abrir'));
    };

    const submitReg = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => resetReg() };
        if (editReg) {
            regForm.put(route('caja.cajas.update', editReg.id), opts);
        } else {
            regForm.post(route('caja.cajas.store'), opts);
        }
    };

    const resetReg = () => {
        setEditReg(null);
        regForm.reset();
        regForm.clearErrors();
    };

    const activaIngresos = Number(activa?.ingresos ?? 0);
    const activaEgresos = Number(activa?.egresos ?? 0);
    const activaSaldo = activa
        ? Number(activa.monto_apertura) + activaIngresos - activaEgresos
        : 0;

    return (
        <AuthenticatedLayout>
            <Head title="Caja" />
            <div className="space-y-5">
                <PageHeader
                    title="Caja"
                    description="Apertura, arqueo y movimientos de efectivo"
                    breadcrumbs={[{ label: 'Caja y Finanzas' }, { label: 'Caja' }]}
                    actions={
                        <Button variant="secondary" onClick={() => setShowRegs(true)}>
                            <Settings className="size-4" /> Gestionar cajas
                        </Button>
                    }
                />

                {/* Sesión activa o apertura */}
                {activa ? (
                    <Card className="border-emerald-200 dark:border-emerald-900/50">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="flex size-2.5 animate-pulse rounded-full bg-emerald-500" />
                                    <span className="text-sm font-semibold text-emerald-600">
                                        Caja abierta
                                    </span>
                                </div>
                                <p className="mt-1 text-lg font-bold text-slate-800 dark:text-slate-100">
                                    {activa.cash_register?.nombre}
                                </p>
                                <p className="text-xs text-slate-400">
                                    Aperturada el{' '}
                                    {new Date(activa.fecha_apertura).toLocaleString('es-PE')}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-4 text-sm">
                                <Metric label="Apertura" value={formatCurrency(Number(activa.monto_apertura), moneda)} />
                                <Metric label="Ingresos" value={formatCurrency(activaIngresos, moneda)} tone="emerald" />
                                <Metric label="Egresos" value={formatCurrency(activaEgresos, moneda)} tone="rose" />
                                <Metric label="Saldo esperado" value={formatCurrency(activaSaldo, moneda)} strong />
                            </div>
                            <Link href={route('caja.show', activa.id)} className={buttonClasses('primary')}>
                                Ver / Cerrar <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </Card>
                ) : (
                    <Card title="Aperturar caja">
                        {registers.length === 0 ? (
                            <p className="text-sm text-slate-400">
                                No hay cajas activas. Crea una con “Gestionar cajas”.
                            </p>
                        ) : (
                            <form onSubmit={abrir} className="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                                <SelectField
                                    label="Caja"
                                    value={openForm.data.cash_register_id}
                                    onChange={(e) => openForm.setData('cash_register_id', e.target.value)}
                                    error={openForm.errors.cash_register_id}
                                    options={registers.map((r) => ({ value: r.id, label: r.nombre }))}
                                />
                                <TextField
                                    label="Monto de apertura"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={openForm.data.monto_apertura}
                                    onChange={(e) => openForm.setData('monto_apertura', e.target.value)}
                                    error={openForm.errors.monto_apertura}
                                />
                                <TextField
                                    label="Observación"
                                    value={openForm.data.observacion}
                                    onChange={(e) => openForm.setData('observacion', e.target.value)}
                                />
                                <Button type="submit" disabled={openForm.processing}>
                                    <Wallet className="size-4" /> Abrir caja
                                </Button>
                            </form>
                        )}
                    </Card>
                )}

                {/* Historial de sesiones */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 className="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Sesiones de caja
                    </h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                    <th className="pb-3 pr-3 font-semibold">Caja</th>
                                    <th className="pb-3 pr-3 font-semibold">Apertura</th>
                                    <th className="pb-3 pr-3 font-semibold">Responsable</th>
                                    <th className="pb-3 pr-3 text-right font-semibold">Monto inicial</th>
                                    <th className="pb-3 pr-3 text-right font-semibold">Diferencia</th>
                                    <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                    <th className="pb-3 text-right font-semibold">Ver</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {sessions.data.map((s) => (
                                    <tr key={s.id} className="text-slate-700 dark:text-slate-300">
                                        <td className="py-3 pr-3 font-medium">
                                            {s.cash_register?.nombre}
                                        </td>
                                        <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                            {new Date(s.fecha_apertura).toLocaleString('es-PE', {
                                                dateStyle: 'short',
                                                timeStyle: 'short',
                                            })}
                                        </td>
                                        <td className="py-3 pr-3 text-slate-500">{s.user?.name ?? '—'}</td>
                                        <td className="py-3 pr-3 text-right">
                                            {formatCurrency(Number(s.monto_apertura), moneda)}
                                        </td>
                                        <td className="py-3 pr-3 text-right">
                                            {s.diferencia !== null && s.estado === 'cerrada' ? (
                                                <span
                                                    className={
                                                        Number(s.diferencia) === 0
                                                            ? 'text-slate-500'
                                                            : Number(s.diferencia) > 0
                                                              ? 'text-emerald-600'
                                                              : 'text-rose-600'
                                                    }
                                                >
                                                    {formatCurrency(Number(s.diferencia), moneda)}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="py-3 pr-3 text-center">
                                            {s.estado === 'abierta' ? (
                                                <Badge tone="emerald">Abierta</Badge>
                                            ) : (
                                                <Badge tone="slate">Cerrada</Badge>
                                            )}
                                        </td>
                                        <td className="py-3 text-right">
                                            <Link
                                                href={route('caja.show', s.id)}
                                                className="inline-flex rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                            >
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        links={sessions.links}
                        from={sessions.from}
                        to={sessions.to}
                        total={sessions.total}
                    />
                </div>
            </div>

            {/* Gestionar cajas */}
            <Modal show={showRegs} onClose={() => setShowRegs(false)} maxWidth="md">
                <div className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Cajas registradoras
                    </h3>
                    <form onSubmit={submitReg} className="mb-4 flex items-end gap-2">
                        <div className="flex-1">
                            <TextField
                                label={editReg ? 'Editar caja' : 'Nueva caja'}
                                value={regForm.data.nombre}
                                onChange={(e) => regForm.setData('nombre', e.target.value)}
                                error={regForm.errors.nombre}
                                placeholder="Ej.: Caja 2"
                            />
                        </div>
                        {editReg && (
                            <div className="pb-1">
                                <Switch
                                    label="Activa"
                                    checked={regForm.data.is_active}
                                    onChange={(v) => regForm.setData('is_active', v)}
                                />
                            </div>
                        )}
                        <Button type="submit" disabled={regForm.processing}>
                            {editReg ? 'Guardar' : <Plus className="size-4" />}
                        </Button>
                        {editReg && (
                            <Button type="button" variant="secondary" onClick={resetReg}>
                                Cancelar
                            </Button>
                        )}
                    </form>
                    <ul className="divide-y divide-slate-100 dark:divide-slate-800">
                        {allRegisters.map((r) => (
                            <li key={r.id} className="flex items-center justify-between py-2 text-sm">
                                <span className="flex items-center gap-2">
                                    {r.nombre}
                                    {!r.is_active && <Badge tone="slate">inactiva</Badge>}
                                    {!!r.abiertas && r.abiertas > 0 && <Badge tone="emerald">abierta</Badge>}
                                </span>
                                <span className="flex gap-1">
                                    <button
                                        onClick={() => {
                                            setEditReg(r);
                                            regForm.setData({ nombre: r.nombre, is_active: r.is_active });
                                        }}
                                        className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                    >
                                        <Pencil className="size-4" />
                                    </button>
                                    <button
                                        onClick={() => setDelReg(r)}
                                        className="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </Modal>

            <ConfirmDialog
                show={!!delReg}
                title="Eliminar caja"
                message={`¿Eliminar la caja "${delReg?.nombre}"?`}
                onConfirm={() => {
                    if (!delReg) return;
                    router.delete(route('caja.cajas.destroy', delReg.id), {
                        preserveScroll: true,
                        onFinish: () => setDelReg(null),
                    });
                }}
                onClose={() => setDelReg(null)}
            />
        </AuthenticatedLayout>
    );
}

function Metric({
    label,
    value,
    tone,
    strong,
}: {
    label: string;
    value: string;
    tone?: 'emerald' | 'rose';
    strong?: boolean;
}) {
    const color =
        tone === 'emerald'
            ? 'text-emerald-600'
            : tone === 'rose'
              ? 'text-rose-600'
              : 'text-slate-800 dark:text-slate-100';
    return (
        <div>
            <p className="text-xs text-slate-400">{label}</p>
            <p className={`${strong ? 'text-base font-bold' : 'font-semibold'} ${color}`}>
                {value}
            </p>
        </div>
    );
}
