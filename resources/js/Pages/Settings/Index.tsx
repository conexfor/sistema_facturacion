import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Building2,
    ChevronRight,
    type LucideIcon,
    ScrollText,
    ShieldCheck,
    UserCircle,
    UserCog,
} from 'lucide-react';

interface Seccion {
    label: string;
    description: string;
    icon: LucideIcon;
    routeName: string;
    permission?: string;
}

const secciones: Seccion[] = [
    {
        label: 'Datos de la empresa',
        description: 'RUC, razón social y datos generales de facturación.',
        icon: Building2,
        routeName: 'settings.empresa.edit',
        permission: 'configuracion.gestionar',
    },
    {
        label: 'Usuarios',
        description: 'Gestiona los usuarios y su acceso al sistema.',
        icon: UserCog,
        routeName: 'settings.usuarios.index',
        permission: 'usuarios.gestionar',
    },
    {
        label: 'Roles y permisos',
        description: 'Define roles y los permisos de cada uno.',
        icon: ShieldCheck,
        routeName: 'settings.roles.index',
        permission: 'usuarios.gestionar',
    },
    {
        label: 'Configuración SUNAT',
        description: 'Certificado, clave SOL y modo de facturación electrónica.',
        icon: ScrollText,
        routeName: 'settings.sunat.edit',
        permission: 'configuracion.gestionar',
    },
    {
        label: 'Mi perfil',
        description: 'Actualiza tus datos y tu contraseña.',
        icon: UserCircle,
        routeName: 'profile.edit',
    },
];

export default function Index() {
    const permisos = usePage().props.auth.permissions ?? [];
    const visibles = secciones.filter(
        (s) => !s.permission || permisos.includes(s.permission),
    );

    return (
        <AuthenticatedLayout>
            <Head title="Configuración" />
            <div className="space-y-5">
                <PageHeader
                    title="Configuración"
                    description="Ajustes generales del sistema"
                    breadcrumbs={[{ label: 'Configuración' }]}
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {visibles.map((s) => {
                        const Icon = s.icon;
                        return (
                            <Link
                                key={s.label}
                                href={route(s.routeName)}
                                className="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-700"
                            >
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                                    <Icon className="size-5" />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="flex items-center justify-between gap-2">
                                        <span className="font-semibold text-slate-800 dark:text-slate-100">
                                            {s.label}
                                        </span>
                                        <ChevronRight className="size-4 shrink-0 text-slate-300 transition group-hover:text-brand-500" />
                                    </span>
                                    <span className="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">
                                        {s.description}
                                    </span>
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
