import { PropsWithChildren, ReactNode } from 'react';

export default function Card({
    title,
    subtitle,
    action,
    className = '',
    bodyClassName = '',
    children,
}: PropsWithChildren<{
    title?: ReactNode;
    subtitle?: ReactNode;
    action?: ReactNode;
    className?: string;
    bodyClassName?: string;
}>) {
    return (
        <div
            className={`rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 ${className}`}
        >
            {(title || action) && (
                <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div>
                        {title && (
                            <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {title}
                            </h3>
                        )}
                        {subtitle && (
                            <p className="text-xs text-slate-400">{subtitle}</p>
                        )}
                    </div>
                    {action}
                </div>
            )}
            <div className={`p-5 ${bodyClassName}`}>{children}</div>
        </div>
    );
}
