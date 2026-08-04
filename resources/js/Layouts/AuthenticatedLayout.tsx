import SidebarContent from '@/Components/SidebarContent';
import Topbar from '@/Components/Topbar';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, X, XCircle } from 'lucide-react';
import {
    PropsWithChildren,
    ReactNode,
    useEffect,
    useState,
} from 'react';

export default function AuthenticatedLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { flash } = usePage().props;

    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof localStorage === 'undefined') return false;
        return localStorage.getItem('sidebar-collapsed') === '1';
    });
    const [mobileOpen, setMobileOpen] = useState(false);
    const [toast, setToast] = useState<{
        type: 'success' | 'error' | 'warning';
        message: string;
    } | null>(null);

    useEffect(() => {
        localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
    }, [collapsed]);

    useEffect(() => {
        if (flash?.success) setToast({ type: 'success', message: flash.success });
        else if (flash?.error) setToast({ type: 'error', message: flash.error });
        else if (flash?.warning) setToast({ type: 'warning', message: flash.warning });
    }, [flash]);

    useEffect(() => {
        if (!toast) return;
        const t = setTimeout(() => setToast(null), 4000);
        return () => clearTimeout(t);
    }, [toast]);

    const sidebarWidth = collapsed ? 'lg:w-20' : 'lg:w-72';
    const mainPad = collapsed ? 'lg:pl-20' : 'lg:pl-72';

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
            {/* Sidebar escritorio */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 hidden border-r border-slate-200 transition-all duration-200 lg:block dark:border-slate-800 ${sidebarWidth}`}
            >
                <SidebarContent collapsed={collapsed} />
            </aside>

            {/* Drawer móvil */}
            {mobileOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <div
                        className="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                        onClick={() => setMobileOpen(false)}
                    />
                    <div className="absolute inset-y-0 left-0 w-72 shadow-xl">
                        <button
                            onClick={() => setMobileOpen(false)}
                            className="absolute -right-11 top-3 rounded-lg bg-white/10 p-2 text-white"
                            aria-label="Cerrar menú"
                        >
                            <X className="size-5" />
                        </button>
                        <SidebarContent
                            collapsed={false}
                            onNavigate={() => setMobileOpen(false)}
                        />
                    </div>
                </div>
            )}

            {/* Contenido */}
            <div className={`flex min-h-screen flex-col transition-all duration-200 ${mainPad}`}>
                <Topbar
                    onToggleCollapse={() => setCollapsed((c) => !c)}
                    onOpenMobile={() => setMobileOpen(true)}
                />

                {header && (
                    <div className="border-b border-slate-200 bg-white px-4 py-5 sm:px-6 lg:px-8 dark:border-slate-800 dark:bg-slate-900">
                        {header}
                    </div>
                )}

                <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    {children}
                </main>
            </div>

            {/* Toast de mensajes flash */}
            {toast && (
                <div className="fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-xl border bg-white px-4 py-3 shadow-lg dark:bg-slate-800 dark:text-slate-100"
                    style={{
                        borderColor:
                            toast.type === 'success' ? '#86efac' : toast.type === 'warning' ? '#fcd34d' : '#fca5a5',
                    }}
                >
                    {toast.type === 'success' ? (
                        <CheckCircle2 className="size-5 text-emerald-500" />
                    ) : toast.type === 'warning' ? (
                        <AlertTriangle className="size-5 text-amber-500" />
                    ) : (
                        <XCircle className="size-5 text-rose-500" />
                    )}
                    <span className="text-sm font-medium">{toast.message}</span>
                    <button
                        onClick={() => setToast(null)}
                        className="ml-2 text-slate-400 hover:text-slate-600"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            )}
        </div>
    );
}
