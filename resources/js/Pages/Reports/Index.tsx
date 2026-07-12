import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Boxes,
    ChevronRight,
    type LucideIcon,
    Package,
    Receipt,
    ShoppingBag,
    TrendingUp,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    receipt: Receipt,
    package: Package,
    'trending-up': TrendingUp,
    'shopping-bag': ShoppingBag,
    boxes: Boxes,
};

interface ReportCard {
    key: string;
    title: string;
    description: string;
    icon: string;
}

export default function Index({ reportes }: { reportes: ReportCard[] }) {
    return (
        <AuthenticatedLayout>
            <Head title="Reportes" />
            <div className="space-y-5">
                <PageHeader
                    title="Reportes"
                    description="Consulta y exporta la información de tu negocio"
                    breadcrumbs={[{ label: 'Análisis' }, { label: 'Reportes' }]}
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {reportes.map((r) => {
                        const Icon = iconMap[r.icon] ?? Receipt;
                        return (
                            <Link
                                key={r.key}
                                href={route('reportes.show', r.key)}
                                className="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-700"
                            >
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                                    <Icon className="size-5" />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block font-semibold text-slate-800 dark:text-slate-100">
                                        {r.title}
                                    </span>
                                    <span className="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">
                                        {r.description}
                                    </span>
                                </span>
                                <ChevronRight className="size-5 shrink-0 text-slate-300 transition group-hover:text-brand-500" />
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
