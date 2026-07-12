import Card from '@/Components/Card';
import { buttonClasses } from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import { Download, FileSpreadsheet, Receipt, ShoppingBag } from 'lucide-react';

interface LibroInfo {
    count: number;
    total: number;
    filename: string;
}

function periodoToMonth(p: string): string {
    return `${p.slice(0, 4)}-${p.slice(4, 6)}`;
}

export default function Index({
    periodo,
    ventas,
    compras,
    moneda,
}: {
    periodo: string;
    ventas: LibroInfo;
    compras: LibroInfo;
    moneda: string;
}) {
    const cambiarPeriodo = (month: string) => {
        if (!month) return;
        const p = month.replace('-', '');
        router.get(route('libros.index'), { periodo: p }, { preserveState: true, replace: true });
    };

    const etiqueta = new Date(`${periodoToMonth(periodo)}-01T00:00:00`).toLocaleDateString('es-PE', {
        month: 'long',
        year: 'numeric',
    });

    return (
        <AuthenticatedLayout>
            <Head title="Libros electrónicos" />
            <div className="space-y-5">
                <PageHeader
                    title="Libros electrónicos"
                    description="Registro de Ventas y Compras en formato PLE/SIRE"
                    breadcrumbs={[{ label: 'Facturación SUNAT' }, { label: 'Libros electrónicos' }]}
                    actions={
                        <input
                            type="month"
                            value={periodoToMonth(periodo)}
                            onChange={(e) => cambiarPeriodo(e.target.value)}
                            className="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"
                        />
                    }
                />

                <p className="text-sm capitalize text-slate-500 dark:text-slate-400">
                    Periodo: <span className="font-semibold">{etiqueta}</span>
                </p>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <LibroCard
                        icon={Receipt}
                        titulo="Registro de Ventas e Ingresos"
                        codigo="14.1"
                        info={ventas}
                        moneda={moneda}
                        href={route('libros.ventas', { periodo })}
                        tone="brand"
                    />
                    <LibroCard
                        icon={ShoppingBag}
                        titulo="Registro de Compras"
                        codigo="8.1"
                        info={compras}
                        moneda={moneda}
                        href={route('libros.compras', { periodo })}
                        tone="emerald"
                    />
                </div>

                <Card>
                    <div className="flex items-start gap-3">
                        <FileSpreadsheet className="size-5 shrink-0 text-slate-400" />
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Los archivos se generan en <strong>formato PLE</strong> (texto, campos separados por «|»),
                            compatibles con el Programa de Libros Electrónicos y SIRE de SUNAT. Descárgalos y
                            valídalos con el aplicativo PLE antes de la presentación.
                        </p>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function LibroCard({
    icon: Icon,
    titulo,
    codigo,
    info,
    moneda,
    href,
    tone,
}: {
    icon: typeof Receipt;
    titulo: string;
    codigo: string;
    info: LibroInfo;
    moneda: string;
    href: string;
    tone: 'brand' | 'emerald';
}) {
    const color = tone === 'brand' ? 'text-brand-600 bg-brand-50 dark:bg-brand-900/30' : 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30';
    return (
        <Card>
            <div className="flex items-start justify-between">
                <div className="flex items-center gap-3">
                    <span className={`flex size-11 items-center justify-center rounded-xl ${color}`}>
                        <Icon className="size-5" />
                    </span>
                    <div>
                        <p className="font-semibold text-slate-800 dark:text-slate-100">{titulo}</p>
                        <p className="text-xs text-slate-400">PLE {codigo}</p>
                    </div>
                </div>
            </div>
            <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt className="text-xs text-slate-400">Registros</dt>
                    <dd className="text-lg font-bold text-slate-800 dark:text-slate-100">{info.count}</dd>
                </div>
                <div>
                    <dt className="text-xs text-slate-400">Total</dt>
                    <dd className="text-lg font-bold text-slate-800 dark:text-slate-100">
                        {formatCurrency(info.total, moneda)}
                    </dd>
                </div>
            </dl>
            <p className="mt-3 truncate font-mono text-xs text-slate-400">{info.filename}</p>
            <a
                href={href}
                className={`${buttonClasses('primary')} mt-4 w-full ${info.count === 0 ? 'pointer-events-none opacity-50' : ''}`}
            >
                <Download className="size-4" /> Descargar TXT (PLE)
            </a>
        </Card>
    );
}
