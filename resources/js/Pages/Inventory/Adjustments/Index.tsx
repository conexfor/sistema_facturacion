import Card from '@/Components/Card';
import Badge from '@/Components/ui/Badge';
import Button from '@/Components/ui/Button';
import { SelectField, TextareaField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber } from '@/lib/format';
import { InventoryMovement } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';
import { SlidersHorizontal } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface Option {
    id: number;
    codigo?: string;
    nombre: string;
}

export default function Index({
    products,
    warehouses,
    recientes,
}: {
    products: Option[];
    warehouses: Option[];
    recientes: InventoryMovement[];
}) {
    const [stockActual, setStockActual] = useState<number | null>(null);
    const [loadingStock, setLoadingStock] = useState(false);

    const form = useForm({
        product_id: '',
        warehouse_id: '',
        cantidad_objetivo: '',
        motivo: '',
    });

    useEffect(() => {
        if (!form.data.product_id || !form.data.warehouse_id) {
            setStockActual(null);
            return;
        }
        setLoadingStock(true);
        axios
            .get(route('inventario.ajustes.stock-actual'), {
                params: {
                    product_id: form.data.product_id,
                    warehouse_id: form.data.warehouse_id,
                },
            })
            .then((r) => setStockActual(Number(r.data.cantidad)))
            .catch(() => setStockActual(null))
            .finally(() => setLoadingStock(false));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.product_id, form.data.warehouse_id]);

    const objetivo = form.data.cantidad_objetivo === ''
        ? null
        : Number(form.data.cantidad_objetivo);
    const diff = stockActual !== null && objetivo !== null ? objetivo - stockActual : null;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('inventario.ajustes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('cantidad_objetivo', 'motivo');
                setStockActual(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Ajustes de inventario" />
            <div className="space-y-5">
                <PageHeader
                    title="Ajustes de inventario"
                    description="Corrige el stock de un producto en un almacén (inventario físico, mermas, etc.)"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Ajustes' }]}
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <Card title="Nuevo ajuste">
                            <form onSubmit={submit} className="space-y-4">
                                <SelectField
                                    label="Producto"
                                    required
                                    placeholder="— Seleccionar producto —"
                                    value={form.data.product_id}
                                    onChange={(e) => form.setData('product_id', e.target.value)}
                                    error={form.errors.product_id}
                                    options={products.map((p) => ({
                                        value: p.id,
                                        label: `${p.codigo} · ${p.nombre}`,
                                    }))}
                                />
                                <SelectField
                                    label="Almacén"
                                    required
                                    placeholder="— Seleccionar almacén —"
                                    value={form.data.warehouse_id}
                                    onChange={(e) => form.setData('warehouse_id', e.target.value)}
                                    error={form.errors.warehouse_id}
                                    options={warehouses.map((w) => ({
                                        value: w.id,
                                        label: w.nombre,
                                    }))}
                                />

                                {stockActual !== null && (
                                    <div className="rounded-lg bg-slate-50 px-4 py-3 text-sm dark:bg-slate-800/60">
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-500 dark:text-slate-400">
                                                Stock actual
                                            </span>
                                            <span className="font-semibold text-slate-800 dark:text-slate-100">
                                                {loadingStock ? '…' : formatNumber(stockActual)}
                                            </span>
                                        </div>
                                        {diff !== null && diff !== 0 && (
                                            <div className="mt-1 flex items-center justify-between">
                                                <span className="text-slate-500 dark:text-slate-400">
                                                    Diferencia
                                                </span>
                                                <span
                                                    className={
                                                        diff > 0
                                                            ? 'font-semibold text-emerald-600'
                                                            : 'font-semibold text-rose-600'
                                                    }
                                                >
                                                    {diff > 0 ? '+' : ''}
                                                    {formatNumber(diff)}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                )}

                                <TextField
                                    label="Cantidad objetivo"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    required
                                    value={form.data.cantidad_objetivo}
                                    onChange={(e) =>
                                        form.setData('cantidad_objetivo', e.target.value)
                                    }
                                    error={form.errors.cantidad_objetivo}
                                    hint="El stock quedará exactamente en esta cantidad"
                                />
                                <TextareaField
                                    label="Motivo"
                                    required
                                    rows={2}
                                    value={form.data.motivo}
                                    onChange={(e) => form.setData('motivo', e.target.value)}
                                    error={form.errors.motivo}
                                    placeholder="Ej.: Inventario físico, merma, rotura…"
                                />
                                <Button type="submit" disabled={form.processing} className="w-full">
                                    <SlidersHorizontal className="size-4" /> Registrar ajuste
                                </Button>
                            </form>
                        </Card>
                    </div>

                    <div className="lg:col-span-3">
                        <Card title="Ajustes recientes">
                            {recientes.length === 0 ? (
                                <p className="py-8 text-center text-sm text-slate-400">
                                    Aún no se han registrado ajustes.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                                <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                                <th className="pb-3 pr-3 font-semibold">Producto</th>
                                                <th className="pb-3 pr-3 font-semibold">Almacén</th>
                                                <th className="pb-3 pr-3 font-semibold">Motivo</th>
                                                <th className="pb-3 text-right font-semibold">Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {recientes.map((m) => {
                                                const esEntrada = m.tipo === 'ajuste_entrada';
                                                return (
                                                    <tr
                                                        key={m.id}
                                                        className="text-slate-700 dark:text-slate-300"
                                                    >
                                                        <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                            {new Date(m.fecha).toLocaleString('es-PE', {
                                                                dateStyle: 'short',
                                                                timeStyle: 'short',
                                                            })}
                                                        </td>
                                                        <td className="py-3 pr-3">
                                                            <span className="font-medium text-slate-800 dark:text-slate-100">
                                                                {m.product?.nombre}
                                                            </span>
                                                            <span className="block font-mono text-xs text-slate-400">
                                                                {m.product?.codigo}
                                                            </span>
                                                        </td>
                                                        <td className="py-3 pr-3 text-slate-500">
                                                            {m.warehouse?.nombre}
                                                        </td>
                                                        <td className="py-3 pr-3 text-slate-500">
                                                            {m.motivo ?? '—'}
                                                        </td>
                                                        <td className="py-3 text-right">
                                                            <Badge tone={esEntrada ? 'emerald' : 'rose'}>
                                                                {esEntrada ? '+' : '−'}
                                                                {formatNumber(Number(m.cantidad))}
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
