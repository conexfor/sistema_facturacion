import BrandLogo from '@/Components/BrandLogo';
import { navigation, type NavItem } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';
import { useLayoutEffect, useRef } from 'react';

const SCROLL_KEY = 'sidebar-scroll';

export function isActive(item: NavItem): boolean {
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

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}

export default function SidebarContent({
    collapsed = false,
    onNavigate,
}: {
    collapsed?: boolean;
    onNavigate?: () => void;
}) {
    const { user, company, roles, permissions } = usePage().props.auth;

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

    const role = user.position ?? roles?.[0] ?? user.email;

    return (
        <div className="flex h-full flex-col border-r border-slate-200 bg-white dark:border-white/[0.06] dark:bg-[rgba(10,14,26,0.95)] dark:backdrop-blur-[20px]">
            {/* Marca */}
            <div
                className={`flex h-16 shrink-0 items-center border-b border-slate-200 dark:border-white/[0.06] ${
                    collapsed ? 'justify-center px-0' : 'px-5'
                }`}
            >
                <BrandLogo
                    size="sm"
                    showWordmark={!collapsed}
                    subtitle={company?.nombre_comercial ?? company?.razon_social ?? undefined}
                />
            </div>

            {/* Navegación */}
            <nav ref={navRef} className="flex-1 space-y-5 overflow-y-auto px-3 py-4">
                {visibleSections.map((section) => (
                    <div key={section.title}>
                        {!collapsed ? (
                            <p className="px-3 pb-1.5 font-display text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400 dark:text-ff-dim">
                                {section.title}
                            </p>
                        ) : (
                            <div className="mx-auto mb-2 h-px w-6 bg-slate-200 dark:bg-white/[0.06]" />
                        )}

                        <ul className="space-y-0.5">
                            {section.items.map((item) => {
                                const Icon = item.icon;
                                const active = isActive(item);

                                const base =
                                    'group flex items-center gap-3 rounded-lg border px-3 py-2 text-[13px] font-medium transition duration-200';
                                const justify = collapsed ? 'justify-center px-0' : '';

                                if (item.soon) {
                                    return (
                                        <li key={item.label}>
                                            <div
                                                title={
                                                    collapsed
                                                        ? `${item.label} (próximamente)`
                                                        : undefined
                                                }
                                                className={`${base} ${justify} cursor-default border-transparent text-slate-400 dark:text-ff-dim`}
                                            >
                                                <Icon className="size-[18px] shrink-0" />
                                                {!collapsed && (
                                                    <>
                                                        <span className="truncate">
                                                            {item.label}
                                                        </span>
                                                        <span className="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-400 dark:bg-white/5">
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
                                            title={collapsed ? item.label : undefined}
                                            className={`${base} ${justify} ${
                                                active
                                                    ? 'border-brand-500/25 bg-brand-500/10 text-brand-700 dark:border-[#00d4aa]/20 dark:bg-[#00d4aa]/10 dark:text-ff-cyan'
                                                    : 'border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-ff-muted dark:hover:bg-white/5 dark:hover:text-ff-text'
                                            }`}
                                        >
                                            <Icon className="size-[18px] shrink-0" />
                                            {!collapsed && (
                                                <span className="truncate">{item.label}</span>
                                            )}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </nav>

            {/* Usuario */}
            <div
                className={`shrink-0 border-t border-slate-200 p-3 dark:border-white/[0.06] ${
                    collapsed ? 'flex flex-col items-center gap-2' : ''
                }`}
            >
                <div className={`flex items-center gap-3 ${collapsed ? 'flex-col' : ''}`}>
                    <Link
                        href={route('profile.edit')}
                        onClick={onNavigate}
                        title={collapsed ? `${user.name} · Mi perfil` : 'Mi perfil'}
                        className="flex min-w-0 flex-1 items-center gap-3 rounded-lg p-1 transition duration-200 hover:bg-slate-100 dark:hover:bg-white/5"
                    >
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00d4aa] via-[#3b82f6] to-[#8b5cf6] font-display text-xs font-bold text-white shadow-glow-cyan">
                            {initials(user.name)}
                        </span>
                        {!collapsed && (
                            <span className="min-w-0">
                                <span className="block truncate text-sm font-semibold text-slate-800 dark:text-ff-text">
                                    {user.name}
                                </span>
                                <span className="block truncate text-xs capitalize text-slate-400 dark:text-ff-muted">
                                    {role}
                                </span>
                            </span>
                        )}
                    </Link>

                    <div className={`flex shrink-0 ${collapsed ? 'flex-col' : ''} gap-0.5`}>
                        <Link
                            href={route('settings.index')}
                            onClick={onNavigate}
                            title="Configuración"
                            aria-label="Configuración"
                            className="rounded-lg p-2 text-slate-400 transition duration-200 hover:bg-slate-100 hover:text-slate-700 dark:text-ff-dim dark:hover:bg-white/5 dark:hover:text-ff-text"
                        >
                            <Settings className="size-4" />
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            title="Cerrar sesión"
                            aria-label="Cerrar sesión"
                            className="rounded-lg p-2 text-slate-400 transition duration-200 hover:bg-rose-50 hover:text-rose-600 dark:text-ff-dim dark:hover:bg-[#f43f5e]/10 dark:hover:text-ff-rose"
                        >
                            <LogOut className="size-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
