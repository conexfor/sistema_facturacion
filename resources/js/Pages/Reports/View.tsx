import Card from '@/Components/Card';
import { buttonClasses } from '@/Components/ui/Button';
import { TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import { FileSpreadsheet, FileText } from 'lucide-react';

interface Column {
    label: string;
    align: 'left' | 'right' | 'center';
    format: 'text' | 'date' | 'currency' | 'number' | 'int';
}
interface SummaryItem {
    label: string;
    value: number | string;
    format: string;
}
interface Report {
    key: string;
    title: string;
    description: string;
    filtros: string[];
    periodo: { desde: string; hasta: string } | null;
    columns: Column[];
    rows: (string | number)[][];
    summary: SummaryItem[];
}

function fmt(value: string | number, format: string, moneda: string): string {
    if (format === 'currency') return formatCurrency(Number(value), moneda);
    if (format === 'number') return Number(value).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (format === 'int') return Number(value).toLocaleString('es-PE');
    return String(value);
}

const alignClass: Record<string, string> = {
    right: 'text-right',
    center: 'text-center',
    left: 'text-left',
};

export default function View({
    report,
    filters,
    moneda,
}: {
    report: Report;
    filters: { desde?: string; hasta?: string };
    moneda: string;
}) {
    const usaFechas = report.filtros.includes('fechas');

    const applyFilter = (key: string, value: string) =>
        router.get(
            route('reportes.show', report.key),
            { ...filters, [key]: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );

    const exportUrl = (formato: 'excel' | 'pdf') =>
        route('reportes.export', {
            key: report.key,
            formato,
            ...(usaFechas && report.periodo ? report.periodo : {}),
        });

    return (
        <AuthenticatedLayout>
            <Head title={report.title} />
            <div className="space-y-5">
                <PageHeader
                    title={report.title}
                    description={report.description}
                    breadcrumbs={[
                        { label: 'Reportes', href: route('reportes.index') },
                        { label: report.title },
                    ]}
                    actions={
                        <>
                            <a href={exportUrl('excel')} className={buttonClasses('secondary')}>
                                <FileSpreadsheet className="size-4" /> Excel
                            </a>
                            <a href={exportUrl('pdf')} className={buttonClasses('secondary')}>
                                <FileText className="size-4" /> PDF
                            </a>
                        </>
                    }
                />

                {/* Filtros + resumen */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    {usaFechas && (
                        <div className="mb-4 flex flex-wrap items-end gap-3">
                            <TextField
                                label="Desde"
                                type="date"
                                value={filters.desde ?? report.periodo?.desde ?? ''}
                                onChange={(e) => applyFilter('desde', e.target.value)}
                            />
                            <TextField
                                label="Hasta"
                                type="date"
                                value={filters.hasta ?? report.periodo?.hasta ?? ''}
                                onChange={(e) => applyFilter('hasta', e.target.value)}
                            />
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        {report.summary.map((s, i) => (
                            <div
                                key={i}
                                className="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50"
                            >
                                <p className="text-xs text-slate-500 dark:text-slate-400">
                                    {s.label}
                                </p>
                                <p className="mt-0.5 text-lg font-bold text-slate-800 dark:text-slate-100">
                                    {fmt(s.value, s.format, moneda)}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Tabla */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                    {report.columns.map((c, i) => (
                                        <th
                                            key={i}
                                            className={`pb-3 pr-3 font-semibold ${alignClass[c.align]}`}
                                        >
                                            {c.label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {report.rows.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={report.columns.length}
                                            className="py-8 text-center text-sm text-slate-400"
                                        >
                                            Sin datos para los filtros seleccionados.
                                        </td>
                                    </tr>
                                ) : (
                                    report.rows.map((row, ri) => (
                                        <tr
                                            key={ri}
                                            className="text-slate-700 dark:text-slate-300"
                                        >
                                            {row.map((cell, ci) => (
                                                <td
                                                    key={ci}
                                                    className={`py-2.5 pr-3 ${alignClass[report.columns[ci].align]} ${
                                                        report.columns[ci].format === 'currency'
                                                            ? 'font-medium'
                                                            : ''
                                                    }`}
                                                >
                                                    {fmt(cell, report.columns[ci].format, moneda)}
                                                </td>
                                            ))}
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
