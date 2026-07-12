import Badge from '@/Components/ui/Badge';
import { buttonClasses } from '@/Components/ui/Button';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency, formatNumber } from '@/lib/format';
import { Category, Paginated, Product } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    History,
    Package,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface Filters {
    search?: string;
    category_id?: string;
    estado?: string;
    [key: string]: string | undefined;
}

export default function Index({
    products,
    categories,
    filters,
}: {
    products: Paginated<Product>;
    categories: Category[];
    filters: Filters;
}) {
    const [toDelete, setToDelete] = useState<Product | null>(null);
    const [processing, setProcessing] = useState(false);

    const applyFilter = (key: string, value: string) => {
        router.get(
            route('inventario.productos.index'),
            { ...filters, [key]: value || undefined, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const confirmDelete = () => {
        if (!toDelete) return;
        setProcessing(true);
        router.delete(route('inventario.productos.destroy', toDelete.id), {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Productos" />
            <div className="space-y-5">
                <PageHeader
                    title="Productos"
                    description="Catálogo de productos y servicios"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Productos' }]}
                    actions={
                        <Link
                            href={route('inventario.productos.create')}
                            className={buttonClasses('primary')}
                        >
                            <Plus className="size-4" /> Nuevo producto
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    {/* Filtros */}
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="lg:col-span-2">
                            <SearchInput
                                routeName="inventario.productos.index"
                                filters={filters}
                                placeholder="Buscar por nombre, código o código de barras…"
                            />
                        </div>
                        <SelectField
                            placeholder="Todas las categorías"
                            value={filters.category_id ?? ''}
                            onChange={(e) => applyFilter('category_id', e.target.value)}
                            options={categories.map((c) => ({
                                value: c.id,
                                label: c.nombre,
                            }))}
                        />
                        <SelectField
                            value={filters.estado ?? ''}
                            onChange={(e) => applyFilter('estado', e.target.value)}
                            placeholder="Todos los estados"
                            options={[
                                { value: 'activo', label: 'Activos' },
                                { value: 'inactivo', label: 'Inactivos' },
                            ]}
                        />
                    </div>

                    {products.data.length === 0 ? (
                        <EmptyState
                            icon={Package}
                            title="No hay productos"
                            description="Crea tu primer producto para empezar a controlar el inventario."
                            action={
                                <Link
                                    href={route('inventario.productos.create')}
                                    className={buttonClasses('primary', 'sm')}
                                >
                                    <Plus className="size-4" /> Nuevo producto
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Código</th>
                                        <th className="pb-3 pr-3 font-semibold">Producto</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Stock</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">P. venta</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {products.data.map((p) => {
                                        const stock = Number(p.stock_total ?? 0);
                                        const min = Number(p.stock_minimo);
                                        const low = p.controla_stock && stock <= min;
                                        return (
                                            <tr
                                                key={p.id}
                                                className="text-slate-700 dark:text-slate-300"
                                            >
                                                <td className="py-3 pr-3 font-mono text-xs">
                                                    {p.codigo}
                                                </td>
                                                <td className="py-3 pr-3">
                                                    <div className="font-medium text-slate-800 dark:text-slate-100">
                                                        {p.nombre}
                                                    </div>
                                                    <div className="text-xs text-slate-400">
                                                        {p.category?.nombre ?? 'Sin categoría'}
                                                    </div>
                                                </td>
                                                <td className="py-3 pr-3 text-right">
                                                    {p.controla_stock ? (
                                                        <span className="inline-flex items-center justify-end gap-1.5">
                                                            {low && (
                                                                <AlertTriangle className="size-3.5 text-amber-500" />
                                                            )}
                                                            <span
                                                                className={
                                                                    low
                                                                        ? 'font-semibold text-amber-600 dark:text-amber-400'
                                                                        : 'font-medium'
                                                                }
                                                            >
                                                                {formatNumber(stock)}
                                                            </span>
                                                            <span className="text-xs text-slate-400">
                                                                {p.unit?.abreviatura}
                                                            </span>
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-slate-400">
                                                            servicio
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-3 text-right font-medium">
                                                    {formatCurrency(Number(p.precio_venta))}
                                                </td>
                                                <td className="py-3 pr-3 text-center">
                                                    {p.is_active ? (
                                                        <Badge tone="emerald">Activo</Badge>
                                                    ) : (
                                                        <Badge tone="slate">Inactivo</Badge>
                                                    )}
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link
                                                            href={route('inventario.kardex.show', p.id)}
                                                            title="Kardex"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <History className="size-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('inventario.productos.edit', p.id)}
                                                            title="Editar"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => setToDelete(p)}
                                                            title="Eliminar"
                                                            className="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </button>
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
                        links={products.links}
                        from={products.from}
                        to={products.to}
                        total={products.total}
                    />
                </div>
            </div>

            <ConfirmDialog
                show={!!toDelete}
                title="Eliminar producto"
                message={`¿Eliminar "${toDelete?.nombre}"? Esta acción no se puede deshacer.`}
                processing={processing}
                onConfirm={confirmDelete}
                onClose={() => setToDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
