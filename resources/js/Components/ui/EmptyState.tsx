import { type LucideIcon, PackageOpen } from 'lucide-react';
import { ReactNode } from 'react';

export default function EmptyState({
    icon: Icon = PackageOpen,
    title,
    description,
    action,
}: {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <div className="mb-4 flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                <Icon className="size-7" />
            </div>
            <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-200">
                {title}
            </h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-slate-400">
                    {description}
                </p>
            )}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
