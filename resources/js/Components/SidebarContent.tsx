import { navigation, type NavItem } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/react';
import { Boxes } from 'lucide-react';
import { useLayoutEffect, useRef } from 'react';

const SCROLL_KEY = 'sidebar-scroll';

function isActive(item: NavItem): boolean {
    if (!item.routeName) return false;
    try {
        return route().current(item.match ?? item.routeName);
    } catch {
        return false;
    }
}

function itemHref(item: NavItem): string {
    if (item.routeName) {
        try {
            return route(item.routeName);
        } catch {
            return '#';
        }
    }
    return item.href ?? '#';
}

export default function SidebarContent({
    collapsed = false,
    onNavigate,
}: {
    collapsed?: boolean;
    onNavigate?: () => void;
}) {
    const { company, permissions } = usePage().props.auth;

    // Conserva la posición de scroll de la navegación entre visitas (el layout
    // se remonta en cada navegación de Inertia y, sin esto, volvería al inicio).
    const navRef = useRef<HTMLElement>(null);
    useLayoutEffect(() => {
        const el = navRef.current;
        if (!el) return;

        const saved = sessionStorage.getItem(SCROLL_KEY);
        if (saved) el.scrollTop = Number(saved);

        const onScroll = () => sessionStorage.setItem(SCROLL_KEY, String(el.scrollTop));
        el.addEventListener('scroll', onScroll, { passive: true });
        return () => el.removeEventListener('scroll', onScroll);
    }, []);

    const visibleSections = navigation
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (item) => !item.permission || permissions.includes(item.permission),
            ),
        }))
        .filter((section) => section.items.length > 0);

    return (
        <div className="flex h-full flex-col bg-white dark:bg-slate-900">
            {/* Marca */}
            <div className="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-4 dark:border-slate-800">
                <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm shadow-brand-600/30">
                    <Boxes className="size-5" />
                </div>
                {!collapsed && (
                    <div className="min-w-0">
                        <p className="truncate text-sm font-bold text-slate-800 dark:text-slate-100">
                            {company?.nombre_comercial ?? 'ERP Comercial'}
                        </p>
                        <p className="truncate text-xs text-slate-400">
                            Gestión empresarial
                        </p>
                    </div>
                )}
            </div>

            {/* Navegación */}
            <nav ref={navRef} className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                {visibleSections.map((section) => (
                    <div key={section.title}>
                        {!collapsed ? (
                            <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                {section.title}
                            </p>
                        ) : (
                            <div className="mx-auto mb-2 h-px w-6 bg-slate-200 dark:bg-slate-800" />
                        )}

                        <ul className="space-y-1">
                            {section.items.map((item) => {
                                const Icon = item.icon;
                                const active = isActive(item);

                                const base =
                                    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
                                const justify = collapsed
                                    ? 'justify-center'
                                    : '';

                                if (item.soon) {
                                    return (
                                        <li key={item.label}>
                                            <div
                                                title={
                                                    collapsed
                                                        ? `${item.label} (próximamente)`
                                                        : undefined
                                                }
                                                className={`${base} ${justify} cursor-default text-slate-400 dark:text-slate-500`}
                                            >
                                                <Icon className="size-5 shrink-0" />
                                                {!collapsed && (
                                                    <>
                                                        <span className="truncate">
                                                            {item.label}
                                                        </span>
                                                        <span className="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-400 dark:bg-slate-800">
                                                            pronto
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        </li>
                                    );
                                }

                                return (
                                    <li key={item.label}>
                                        <Link
                                            href={itemHref(item)}
                                            onClick={onNavigate}
                                            title={
                                                collapsed ? item.label : undefined
                                            }
                                            className={`${base} ${justify} ${
                                                active
                                                    ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30'
                                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                                            }`}
                                        >
                                            <Icon className="size-5 shrink-0" />
                                            {!collapsed && (
                                                <span className="truncate">
                                                    {item.label}
                                                </span>
                                            )}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </nav>

            {/* Pie: RUC empresa */}
            {!collapsed && company && (
                <div className="shrink-0 border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                    <p className="truncate text-xs font-medium text-slate-500 dark:text-slate-400">
                        {company.razon_social}
                    </p>
                    <p className="text-xs text-slate-400">RUC {company.ruc}</p>
                </div>
            )}
        </div>
    );
}
