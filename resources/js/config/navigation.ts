import {
    ArrowLeftRight,
    Ban,
    BarChart3,
    Boxes,
    CalendarClock,
    ClipboardList,
    FileSpreadsheet,
    FileText,
    HandCoins,
    Landmark,
    LayoutDashboard,
    type LucideIcon,
    Package,
    PackageCheck,
    Receipt,
    Scale,
    ScrollText,
    ShieldCheck,
    ShoppingCart,
    SlidersHorizontal,
    Tags,
    Truck,
    UserCog,
    Users,
    Wallet,
    Warehouse,
} from 'lucide-react';

export interface NavItem {
    label: string;
    routeName?: string;
    match?: string;
    href?: string;
    icon: LucideIcon;
    soon?: boolean;
    /** Permiso requerido para ver el ítem (si se omite, visible para todos). */
    permission?: string;
}

export interface NavSection {
    title: string;
    items: NavItem[];
}

export const navigation: NavSection[] = [
    {
        title: 'Principal',
        items: [
            {
                label: 'Dashboard',
                routeName: 'dashboard',
                icon: LayoutDashboard,
                permission: 'dashboard.ver',
            },
        ],
    },
    {
        title: 'Inventario',
        items: [
            { label: 'Productos', icon: Package, routeName: 'inventario.productos.index', match: 'inventario.productos.*', permission: 'inventario.gestionar' },
            { label: 'Categorías', icon: Tags, routeName: 'inventario.categorias.index', match: 'inventario.categorias.*', permission: 'inventario.gestionar' },
            { label: 'Almacenes', icon: Warehouse, routeName: 'inventario.almacenes.index', match: 'inventario.almacenes.*', permission: 'inventario.gestionar' },
            { label: 'Kardex valorizado', icon: ClipboardList, routeName: 'inventario.kardex.index', match: 'inventario.kardex.*', permission: 'inventario.gestionar' },
            { label: 'Ajustes de stock', icon: SlidersHorizontal, routeName: 'inventario.ajustes.index', match: 'inventario.ajustes.*', permission: 'inventario.gestionar' },
            { label: 'Transferencias', icon: ArrowLeftRight, routeName: 'inventario.transferencias.index', match: 'inventario.transferencias.*', permission: 'inventario.gestionar' },
            { label: 'Lotes y vencimiento', icon: CalendarClock, routeName: 'inventario.lotes.index', match: 'inventario.lotes.*', permission: 'inventario.gestionar' },
        ],
    },
    {
        title: 'Ventas',
        items: [
            { label: 'Punto de venta (POS)', icon: ShoppingCart, routeName: 'ventas.pos', match: 'ventas.pos', permission: 'ventas.gestionar' },
            { label: 'Ventas', icon: Receipt, routeName: 'ventas.index', match: 'ventas.index', permission: 'ventas.gestionar' },
            { label: 'Cotizaciones', icon: FileText, routeName: 'cotizaciones.index', match: 'cotizaciones.*', permission: 'ventas.gestionar' },
            { label: 'Pedidos', icon: ClipboardList, routeName: 'pedidos.index', match: 'pedidos.*', permission: 'ventas.gestionar' },
        ],
    },
    {
        title: 'Clientes',
        items: [
            { label: 'Clientes', icon: Users, routeName: 'clientes.index', match: 'clientes.*', permission: 'ventas.gestionar' },
        ],
    },
    {
        title: 'Compras',
        items: [
            { label: 'Compras', icon: PackageCheck, routeName: 'compras.index', match: 'compras.index', permission: 'compras.gestionar' },
            { label: 'Proveedores', icon: Truck, routeName: 'compras.proveedores.index', match: 'compras.proveedores.*', permission: 'compras.gestionar' },
            { label: 'Cuentas por pagar', icon: Landmark, routeName: 'compras.payables', match: 'compras.payables', permission: 'compras.gestionar' },
        ],
    },
    {
        title: 'Caja y Finanzas',
        items: [
            { label: 'Caja', icon: Wallet, routeName: 'caja.index', match: 'caja.index', permission: 'caja.gestionar' },
            { label: 'Flujo de caja', icon: Scale, routeName: 'caja.flujo', match: 'caja.flujo', permission: 'caja.gestionar' },
            { label: 'Cuentas por cobrar', icon: HandCoins, routeName: 'cxc.index', match: 'cxc.*', permission: 'caja.gestionar' },
        ],
    },
    {
        title: 'Facturación SUNAT',
        items: [
            {
                label: 'Comprobantes',
                icon: ScrollText,
                routeName: 'comprobantes.index',
                match: 'comprobantes.*',
                permission: 'ventas.gestionar',
            },
            {
                label: 'Resúmenes de boletas',
                icon: CalendarClock,
                routeName: 'resumenes.index',
                match: 'resumenes.*',
                permission: 'ventas.gestionar',
            },
            {
                label: 'Comunicación de baja',
                icon: Ban,
                routeName: 'bajas.index',
                match: 'bajas.*',
                permission: 'ventas.gestionar',
            },
            {
                label: 'Notas de crédito/débito',
                icon: FileText,
                routeName: 'notas.index',
                match: 'notas.*',
                permission: 'ventas.gestionar',
            },
            {
                label: 'Guías de remisión',
                icon: Truck,
                routeName: 'guias.index',
                match: 'guias.*',
                permission: 'ventas.gestionar',
            },
            {
                label: 'Libros electrónicos',
                icon: FileSpreadsheet,
                routeName: 'libros.index',
                match: 'libros.*',
                permission: 'reportes.ver',
            },
        ],
    },
    {
        title: 'Análisis',
        items: [
            {
                label: 'Reportes',
                icon: BarChart3,
                routeName: 'reportes.index',
                match: 'reportes.*',
                permission: 'reportes.ver',
            },
        ],
    },
    {
        title: 'Configuración',
        items: [
            { label: 'Usuarios', icon: UserCog, routeName: 'settings.usuarios.index', match: 'settings.usuarios.*', permission: 'usuarios.gestionar' },
            { label: 'Roles y permisos', icon: ShieldCheck, routeName: 'settings.roles.index', match: 'settings.roles.*', permission: 'usuarios.gestionar' },
            { label: 'Configuración SUNAT', icon: ScrollText, routeName: 'settings.sunat.edit', match: 'settings.sunat.*', permission: 'configuracion.gestionar' },
            { label: 'Sucursales', icon: Boxes, routeName: 'settings.sucursales.index', match: 'settings.sucursales.*', permission: 'configuracion.gestionar' },
        ],
    },
];
