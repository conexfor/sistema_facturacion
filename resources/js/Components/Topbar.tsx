import Dropdown from '@/Components/Dropdown';
import NotificationsBell from '@/Components/NotificationsBell';
import { useTheme } from '@/hooks/useTheme';
import { usePage } from '@inertiajs/react';
import {
    ChevronDown,
    LogOut,
    MapPin,
    Menu,
    Moon,
    PanelLeft,
    Settings,
    Sun,
    UserCircle,
} from 'lucide-react';

export default function Topbar({
    onToggleCollapse,
    onOpenMobile,
}: {
    onToggleCollapse: () => void;
    onOpenMobile: () => void;
}) {
    const { user, branch } = usePage().props.auth;
    const { isDark, toggleTheme } = useTheme();

    return (
        <header className="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-slate-200 bg-white/90 px-3 backdrop-blur-sm sm:px-4 dark:border-slate-800 dark:bg-slate-900/90">
            {/* Toggle móvil */}
            <button
                onClick={onOpenMobile}
                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="Abrir menú"
            >
                <Menu className="size-5" />
            </button>

            {/* Toggle colapsar (escritorio) */}
            <button
                onClick={onToggleCollapse}
                className="hidden rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:block dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="Colapsar menú"
            >
                <PanelLeft className="size-5" />
            </button>

            <div className="ml-auto flex items-center gap-1 sm:gap-2">
                {/* Sucursal actual */}
                {branch && (
                    <span className="hidden items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 md:inline-flex dark:bg-slate-800 dark:text-slate-300">
                        <MapPin className="size-3.5 text-brand-500" />
                        {branch.nombre}
                    </span>
                )}

                {/* Tema */}
                <button
                    onClick={toggleTheme}
                    className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                    aria-label="Cambiar tema"
                >
                    {isDark ? (
                        <Sun className="size-5" />
                    ) : (
                        <Moon className="size-5" />
                    )}
                </button>

                {/* Notificaciones */}
                <NotificationsBell />

                {/* Usuario */}
                <Dropdown>
                    <Dropdown.Trigger>
                        <button className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800">
                            <span className="flex size-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                                {user.name.charAt(0).toUpperCase()}
                            </span>
                            <span className="hidden text-left sm:block">
                                <span className="block text-sm font-semibold leading-tight text-slate-700 dark:text-slate-200">
                                    {user.name}
                                </span>
                                <span className="block text-xs leading-tight text-slate-400">
                                    {user.position ?? user.email}
                                </span>
                            </span>
                            <ChevronDown className="size-4 text-slate-400" />
                        </button>
                    </Dropdown.Trigger>
                    <Dropdown.Content>
                        <Dropdown.Link href={route('profile.edit')}>
                            <span className="flex items-center gap-2">
                                <UserCircle className="size-4" /> Mi perfil
                            </span>
                        </Dropdown.Link>
                        <Dropdown.Link href={route('settings.index')}>
                            <span className="flex items-center gap-2">
                                <Settings className="size-4" /> Configuración
                            </span>
                        </Dropdown.Link>
                        <Dropdown.Link
                            href={route('logout')}
                            method="post"
                            as="button"
                        >
                            <span className="flex items-center gap-2">
                                <LogOut className="size-4" /> Cerrar sesión
                            </span>
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            </div>
        </header>
    );
}
