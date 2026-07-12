import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { ReactNode } from 'react';

export interface Crumb {
    label: string;
    href?: string;
}

export default function PageHeader({
    title,
    description,
    breadcrumbs,
    actions,
}: {
    title: string;
    description?: string;
    breadcrumbs?: Crumb[];
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <nav className="mb-1 flex items-center gap-1 text-xs text-slate-400">
                        {breadcrumbs.map((c, i) => (
                            <span key={i} className="flex items-center gap-1">
                                {i > 0 && <ChevronRight className="size-3" />}
                                {c.href ? (
                                    <Link
                                        href={c.href}
                                        className="hover:text-brand-600"
                                    >
                                        {c.label}
                                    </Link>
                                ) : (
                                    <span className="text-slate-500 dark:text-slate-300">
                                        {c.label}
                                    </span>
                                )}
                            </span>
                        ))}
                    </nav>
                )}
                <h1 className="text-xl font-bold text-slate-800 dark:text-slate-100">
                    {title}
                </h1>
                {description && (
                    <p className="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        {description}
                    </p>
                )}
            </div>
            {actions && <div className="flex items-center gap-2">{actions}</div>}
        </div>
    );
}
