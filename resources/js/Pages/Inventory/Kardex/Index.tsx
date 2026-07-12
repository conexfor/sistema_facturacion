import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber } from '@/lib/format';
import { Paginated, Product } from '@/types/models';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Package } from 'lucide-react';

export default function Index({
    products,
    filters,
}: {
    products: Paginated<Product>;
    filters: Record<string, string | undefined>;
}) {
    return (
        <AuthenticatedLayout>
            <Head title="Kardex" />
            <div className="space-y-5">
                <PageHeader
                    title="Kardex valorizado"
                    description="Selecciona un producto para ver su historial de movimientos"
                    breadcrumbs={[{ label: 'Inventario' }, { label: 'Kardex' }]}
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 max-w-md">
                        <SearchInput
                            routeName="inventario.kardex.index"
                            filters={filters}
                            placeholder="Buscar producto…"
                        />
                    </div>

                    {products.data.length === 0 ? (
                        <EmptyState
                            icon={Package}
                            title="Sin productos"
                            description="No hay productos que controlen stock."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {products.data.map((p) => (
                                <Link
                                    key={p.id}
                                    href={route('inventario.kardex.show', p.id)}
                                    className="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/40 dark:border-slate-800 dark:hover:border-brand-700 dark:hover:bg-brand-900/10"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium text-slate-800 dark:text-slate-100">
                                            {p.nombre}
                                        </p>
                                        <p className="font-mono text-xs text-slate-400">
                                            {p.codigo}
                                        </p>
                                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            Stock:{' '}
                                            <span className="font-semibold text-slate-700 dark:text-slate-200">
                                                {formatNumber(Number(p.stock_total ?? 0))}
                                            </span>{' '}
                                            {p.unit?.abreviatura}
                                        </p>
                                    </div>
                                    <ChevronRight className="size-5 shrink-0 text-slate-300 transition group-hover:text-brand-500" />
                                </Link>
                            ))}
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
        </AuthenticatedLayout>
    );
}
