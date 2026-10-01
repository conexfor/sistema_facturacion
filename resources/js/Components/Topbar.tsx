import NotificationsBell from '@/Components/NotificationsBell';
import { isActive } from '@/Components/SidebarContent';
import { navigation } from '@/config/navigation';
import { useTheme } from '@/hooks/useTheme';
import { Link, usePage } from '@inertiajs/react';
import { MapPin, Menu, Moon, Plus, Sun } from 'lucide-react';

function currentSectionTitle(): string {
    for (const section of navigation) {
        const item = section.items.find(isActive);
        if (item) return item.label;
    }
    try {
        if (route().current('profile.*')) return 'Mi perfil';
        if (route().current('settings.*')) return 'Configuración';
    } catch {
        // rutas no disponibles
    }
    return 'Panel';
}

const today = new Date().toLocaleDateString('es-PE', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const iconBtn =
    'rounded-lg p-2 text-slate-500 transition duration-200 hover:bg-slate-100 hover:text-slate-800 dark:text-ff-muted dark:hover:bg-white/5 dark:hover:text-ff-text';

export default function Topbar({
    onToggleCollapse,
    onOpenMobile,
}: {
    onToggleCollapse: () => void;
    onOpenMobile: () => void;
}) {
    const { branch, permissions } = usePage().props.auth;
    const { isDark, toggleTheme } = useTheme();
    const puedeVender = permissions.includes('ventas.gestionar');

    return (
        <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur-xl sm:px-6 dark:border-white/[0.06] dark:bg-[#070b14]/70">
            {/* Hamburguesa: drawer en móvil, colapsar en escritorio */}
            <button onClick={onOpenMobile} className={`${iconBtn} lg:hidden`} aria-label="Abrir menú">
                <Menu className="size-5" />
            </button>
            <button
                onClick={onToggleCollapse}
                className={`${iconBtn} hidden lg:block`}
                aria-label="Colapsar menú"
            >
                <Menu className="size-5" />
            </button>

            {/* Título de sección + fecha */}
            <div className="min-w-0">
                <h2 className="truncate font-display text-lg font-semibold leading-tight tracking-tight text-slate-800 dark:text-ff-text">
                    {currentSectionTitle()}
                </h2>
                <p className="hidden truncate font-mono text-[11px] capitalize text-slate-400 sm:block dark:text-ff-dim">
                    {today}
                </p>
            </div>

            <div className="ml-auto flex items-center gap-1.5 sm:gap-2">
                {branch && (
                    <span className="hidden items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 md:inline-flex dark:border-white/[0.06] dark:bg-white/[0.03] dark:text-ff-muted">
                        <MapPin className="size-3.5 text-brand-500 dark:text-ff-cyan" />
                        {branch.nombre}
                    </span>
                )}

                <button onClick={toggleTheme} className={iconBtn} aria-label="Cambiar tema">
                    {isDark ? <Sun className="size-5" /> : <Moon className="size-5" />}
                </button>

                <NotificationsBell />

                {puedeVender && (
                    <Link
                        href={route('ventas.pos')}
                        className="ff-btn-primary ml-1 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 font-display text-sm font-semibold transition duration-200"
                    >
                        <Plus className="size-4" strokeWidth={2.5} />
                        <span className="hidden sm:inline">Nueva Factura</span>
                    </Link>
                )}
            </div>
        </header>
    );
}
