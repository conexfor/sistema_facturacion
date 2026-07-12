import Card from '@/Components/Card';
import Badge from '@/Components/ui/Badge';
import { SelectField, TextField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency, formatNumber } from '@/lib/format';
import { InventoryMovement, Paginated } from '@/types/models';
import { Head, router } from '@inertiajs/react';

interface ProductLite {
    id: number;
    codigo: string;
    nombre: string;
    costo_promedio: string;
    stock_total: number;
}

const tipoMeta: Record<string, { label: string; tone: 'emerald' | 'rose' | 'amber' | 'sky' }> = {
    entrada: { label: 'Entrada', tone: 'emerald' },
    salida: { label: 'Salida', tone: 'rose' },
    ajuste_entrada: { label: 'Ajuste +', tone: 'amber' },
    ajuste_salida: { label: 'Ajuste −', tone: 'amber' },
    transferencia_entrada: { label: 'Transf. +', tone: 'sky' },
    transferencia_salida: { label: 'Transf. −', tone: 'sky' },
};

const ENTRADAS = ['entrada', 'ajuste_entrada', 'transferencia_entrada'];

export default function Show({
    product,
    movements,
    stockPorAlmacen,
    warehouses,
    filters,
}: {
    product: ProductLite;
    movements: Paginated<InventoryMovement>;
    stockPorAlmacen: { almacen: string | null; cantidad: number }[];
    warehouses: { id: number; nombre: string }[];
    filters: { warehouse_id?: string; desde?: string; hasta?: string };
}) {
    const costoProm = Number(product.costo_promedio);
    const valorTotal = product.stock_total * costoProm;

    const applyFilter = (key: string, value: string) => {
        router.get(
            route('inventario.kardex.show', product.id),
            { ...filters, [key]: value || undefined, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Kardex: ${product.nombre}`} />
            <div className="space-y-5">
                <PageHeader
                    title={product.nombre}
                    description={`Código ${product.codigo}`}
                    breadcrumbs={[
                        { label: 'Inventario' },
                        { label: 'Kardex', href: route('inventario.kardex.index') },
                        { label: product.codigo },
                    ]}
                />

                {/* Resumen */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Stock total</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100">
                            {formatNumber(product.stock_total)}
                        </p>
                    </Card>
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Costo promedio</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100">
                            {formatCurrency(costoProm)}
                        </p>
                    </Card>
                    <Card>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Valor de inventario</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100">
                            {formatCurrency(valorTotal)}
                        </p>
                    </Card>
                </div>

                {stockPorAlmacen.length > 0 && (
                    <Card title="Stock por almacén">
                        <div className="flex flex-wrap gap-2">
                            {stockPorAlmacen.map((s, i) => (
                                <span
                                    key={i}
                                    className="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-sm dark:bg-slate-800"
                                >
                                    <span className="text-slate-500 dark:text-slate-400">
                                        {s.almacen}
                                    </span>
                                    <span className="font-semibold text-slate-800 dark:text-slate-100">
                                        {formatNumber(s.cantidad)}
                                    </span>
                                </span>
                            ))}
                        </div>
                    </Card>
                )}

                <Card title="Movimientos" subtitle="Kardex valorizado (costo promedio ponderado)">
                    {/* Filtros */}
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <SelectField
                            placeholder="Todos los almacenes"
                            value={filters.warehouse_id ?? ''}
                            onChange={(e) => applyFilter('warehouse_id', e.target.value)}
                            options={warehouses.map((w) => ({ value: w.id, label: w.nombre }))}
                        />
                        <TextField
                            type="date"
                            value={filters.desde ?? ''}
                            onChange={(e) => applyFilter('desde', e.target.value)}
                        />
                        <TextField
                            type="date"
                            value={filters.hasta ?? ''}
                            onChange={(e) => applyFilter('hasta', e.target.value)}
                        />
                    </div>

                    {movements.data.length === 0 ? (
                        <p className="py-8 text-center text-sm text-slate-400">
                            No hay movimientos para los filtros seleccionados.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 font-semibold">Tipo</th>
                                        <th className="pb-3 pr-3 font-semibold">Almacén</th>
                                        <th className="pb-3 pr-3 font-semibold">Detalle</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Entrada</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Salida</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">C. unit.</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Saldo</th>
                                        <th className="pb-3 text-right font-semibold">Valor saldo</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {movements.data.map((m) => {
                                        const meta = tipoMeta[m.tipo] ?? {
                                            label: m.tipo,
                                            tone: 'slate' as const,
                                        };
                                        const esEntrada = ENTRADAS.includes(m.tipo);
                                        const cant = Number(m.cantidad);
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
                                                    <Badge tone={meta.tone}>{meta.label}</Badge>
                                                </td>
                                                <td className="py-3 pr-3 text-slate-500">
                                                    {m.warehouse?.nombre ?? '—'}
                                                </td>
                                                <td className="py-3 pr-3">
                                                    <span className="text-slate-600 dark:text-slate-300">
                                                        {m.motivo ?? '—'}
                                                    </span>
                                                    {m.referencia && (
                                                        <span className="block text-xs text-slate-400">
                                                            {m.referencia}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                                    {esEntrada ? formatNumber(cant) : ''}
                                                </td>
                                                <td className="py-3 pr-3 text-right font-medium text-rose-600 dark:text-rose-400">
                                                    {!esEntrada ? formatNumber(cant) : ''}
                                                </td>
                                                <td className="py-3 pr-3 text-right text-slate-500">
                                                    {formatCurrency(Number(m.costo_unitario))}
                                                </td>
                                                <td className="py-3 pr-3 text-right font-semibold">
                                                    {formatNumber(Number(m.saldo_cantidad))}
                                                </td>
                                                <td className="py-3 text-right">
                                                    {formatCurrency(Number(m.saldo_valor))}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination
                        links={movements.links}
                        from={movements.from}
                        to={movements.to}
                        total={movements.total}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
