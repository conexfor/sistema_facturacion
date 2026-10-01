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
            className={`rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/[0.06] dark:bg-[#0d1426]/60 dark:shadow-none dark:backdrop-blur-md ${className}`}
        >
            {(title || action) && (
                <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-white/[0.06]">
                    <div>
                        {title && (
                            <h3 className="font-display text-[15px] font-semibold text-slate-800 dark:text-ff-text">
                                {title}
                            </h3>
                        )}
                        {subtitle && (
                            <p className="text-xs text-slate-400 dark:text-ff-muted">{subtitle}</p>
                        )}
                    </div>
                    {action}
                </div>
            )}
            <div className={`p-5 ${bodyClassName}`}>{children}</div>
        </div>
    );
}
