import { formatNumber } from '@/lib/format';
import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, Bell, CheckCircle2, PackageOpen } from 'lucide-react';
import { useState } from 'react';

export default function NotificationsBell() {
    const { notificaciones } = usePage().props;
    const count = notificaciones?.count ?? 0;
    const items = notificaciones?.stock_bajo ?? [];
    const [open, setOpen] = useState(false);

    return (
        <div className="relative">
            <button
                onClick={() => setOpen((o) => !o)}
                className="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="Notificaciones"
            >
                <Bell className="size-5" />
                {count > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white dark:ring-slate-900">
                        {count > 9 ? '9+' : count}
                    </span>
                )}
            </button>

            {open && (
                <>
                    {/* Overlay para cerrar al hacer click afuera */}
                    <div
                        className="fixed inset-0 z-40"
                        onClick={() => setOpen(false)}
                    />
                    <div className="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                            <span className="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                Notificaciones
                            </span>
                            {count > 0 && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                    <AlertTriangle className="size-3" />
                                    {count} con stock bajo
                                </span>
                            )}
                        </div>

                        {items.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-4 py-8 text-center">
                                <CheckCircle2 className="mb-2 size-7 text-emerald-500" />
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Todo en orden, sin alertas de stock.
                                </p>
                            </div>
                        ) : (
                            <ul className="max-h-80 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700">
                                {items.map((p) => (
                                    <li key={p.id}>
                                        <Link
                                            href={route('inventario.productos.edit', p.id)}
                                            onClick={() => setOpen(false)}
                                            className="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50"
                                        >
                                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                                <PackageOpen className="size-4" />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-medium text-slate-800 dark:text-slate-100">
                                                    {p.nombre}
                                                </span>
                                                <span className="font-mono text-xs text-slate-400">
                                                    {p.codigo}
                                                </span>
                                            </span>
                                            <span className="shrink-0 text-right text-xs">
                                                <span className="block font-semibold text-rose-600 dark:text-rose-400">
                                                    {formatNumber(p.stock)}
                                                </span>
                                                <span className="text-slate-400">
                                                    mín. {formatNumber(p.minimo)}
                                                </span>
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <Link
                            href={route('inventario.productos.index')}
                            onClick={() => setOpen(false)}
                            className="block border-t border-slate-100 px-4 py-2.5 text-center text-sm font-medium text-brand-600 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50"
                        >
                            Ver inventario
                        </Link>
                    </div>
                </>
            )}
        </div>
    );
}
