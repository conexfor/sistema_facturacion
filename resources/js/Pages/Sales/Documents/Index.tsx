import Badge from '@/Components/ui/Badge';
import { buttonClasses } from '@/Components/ui/Button';
import EmptyState from '@/Components/ui/EmptyState';
import { SelectField } from '@/Components/ui/Form';
import Pagination from '@/Components/ui/Pagination';
import PageHeader from '@/Components/ui/PageHeader';
import SearchInput from '@/Components/ui/SearchInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Paginated, Quote } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, FileText, Plus } from 'lucide-react';

const estadoTone: Record<string, 'slate' | 'sky' | 'emerald' | 'rose'> = {
    pendiente: 'slate',
    aprobada: 'sky',
    convertida: 'emerald',
    anulada: 'rose',
};

function numero(s: string, c: number): string {
    return `${s}-${String(c).padStart(6, '0')}`;
}

export default function Index({
    tipo,
    quotes,
    filters,
    moneda,
}: {
    tipo: 'cotizacion' | 'pedido';
    quotes: Paginated<Quote>;
    filters: Record<string, string | undefined>;
    moneda: string;
}) {
    const esCot = tipo === 'cotizacion';
    const titulo = esCot ? 'Cotizaciones' : 'Pedidos';
    const indexRoute = esCot ? 'cotizaciones.index' : 'pedidos.index';
    const createRoute = esCot ? 'cotizaciones.create' : 'pedidos.create';

    return (
        <AuthenticatedLayout>
            <Head title={titulo} />
            <div className="space-y-5">
                <PageHeader
                    title={titulo}
                    description={esCot ? 'Propuestas comerciales a clientes' : 'Pedidos de clientes por atender'}
                    breadcrumbs={[{ label: 'Ventas' }, { label: titulo }]}
                    actions={
                        <Link href={route(createRoute)} className={buttonClasses('primary')}>
                            <Plus className="size-4" /> {esCot ? 'Nueva cotización' : 'Nuevo pedido'}
                        </Link>
                    }
                />

                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <SearchInput routeName={indexRoute} filters={filters} placeholder="Buscar por número o cliente…" />
                        </div>
                        <SelectField
                            placeholder="Todos los estados"
                            value={filters.estado ?? ''}
                            onChange={(e) =>
                                router.get(
                                    route(indexRoute),
                                    { ...filters, estado: e.target.value || undefined, page: undefined },
                                    { preserveState: true, preserveScroll: true, replace: true },
                                )
                            }
                            options={[
                                { value: 'pendiente', label: 'Pendiente' },
                                { value: 'aprobada', label: 'Aprobada' },
                                { value: 'convertida', label: 'Convertida' },
                                { value: 'anulada', label: 'Anulada' },
                            ]}
                        />
                    </div>

                    {quotes.data.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title={`Sin ${titulo.toLowerCase()}`}
                            description={esCot ? 'Crea cotizaciones para tus clientes.' : 'Registra pedidos de clientes.'}
                            action={
                                <Link href={route(createRoute)} className={buttonClasses('primary', 'sm')}>
                                    <Plus className="size-4" /> Nuevo
                                </Link>
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                        <th className="pb-3 pr-3 font-semibold">Número</th>
                                        <th className="pb-3 pr-3 font-semibold">Cliente</th>
                                        <th className="pb-3 pr-3 font-semibold">Fecha</th>
                                        <th className="pb-3 pr-3 text-right font-semibold">Total</th>
                                        <th className="pb-3 pr-3 text-center font-semibold">Estado</th>
                                        <th className="pb-3 text-right font-semibold">Ver</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {quotes.data.map((d) => (
                                        <tr key={d.id} className="text-slate-700 dark:text-slate-300">
                                            <td className="py-3 pr-3 font-mono text-xs font-semibold">
                                                {numero(d.serie, d.correlativo)}
                                            </td>
                                            <td className="py-3 pr-3">{d.cliente_nombre}</td>
                                            <td className="whitespace-nowrap py-3 pr-3 text-xs text-slate-500">
                                                {new Date(d.fecha).toLocaleDateString('es-PE')}
                                            </td>
                                            <td className="py-3 pr-3 text-right font-semibold">
                                                {formatCurrency(Number(d.total), moneda)}
                                            </td>
                                            <td className="py-3 pr-3 text-center">
                                                <Badge tone={estadoTone[d.estado]}>{d.estado}</Badge>
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={route('documentos.show', d.id)}
                                                    className="inline-flex rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-brand-600 dark:hover:bg-slate-800"
                                                >
                                                    <Eye className="size-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={quotes.links} from={quotes.from} to={quotes.to} total={quotes.total} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
