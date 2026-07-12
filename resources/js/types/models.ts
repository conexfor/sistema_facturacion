export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

export interface Category {
    id: number;
    nombre: string;
    descripcion: string | null;
    is_active: boolean;
    products_count?: number;
}

export interface Unit {
    id: number;
    codigo: string;
    nombre: string;
    abreviatura: string;
}

export interface Warehouse {
    id: number;
    codigo: string;
    nombre: string;
    direccion: string | null;
    branch_id: number | null;
    branch?: { id: number; nombre: string } | null;
    is_main: boolean;
    is_active: boolean;
    stocks_count?: number;
}

export interface Product {
    id: number;
    category_id: number | null;
    unit_id: number | null;
    codigo: string;
    codigo_barras: string | null;
    nombre: string;
    descripcion: string | null;
    tipo: 'bien' | 'servicio';
    precio_compra: string;
    precio_venta: string;
    afecto_igv: boolean;
    tipo_afectacion_igv: string;
    costo_promedio: string;
    stock_minimo: string;
    controla_stock: boolean;
    is_active: boolean;
    category?: { id: number; nombre: string } | null;
    unit?: { id: number; abreviatura: string } | null;
    stock_total?: number | null;
}

export interface Customer {
    id: number;
    tipo_documento: string;
    numero_documento: string | null;
    razon_social: string;
    nombre_comercial: string | null;
    direccion: string | null;
    ubigeo: string | null;
    estado_contribuyente: string | null;
    condicion_domicilio: string | null;
    email: string | null;
    telefono: string | null;
    is_active: boolean;
}

export interface SaleItem {
    id: number;
    product_id: number | null;
    descripcion: string;
    cantidad: string;
    precio_unitario: string;
    descuento: string;
    afecto_igv: boolean;
    base: string;
    igv: string;
    total: string;
    costo_unitario: string;
    product?: { id: number; codigo: string } | null;
}

export interface Sale {
    id: number;
    tipo_comprobante: 'boleta' | 'factura' | 'nota_venta';
    serie: string;
    correlativo: number;
    numero?: string;
    cliente_documento: string | null;
    cliente_nombre: string;
    fecha: string;
    moneda: string;
    subtotal: string;
    igv: string;
    descuento_total: string;
    total: string;
    aplica_percepcion?: boolean;
    percepcion_tasa?: string;
    percepcion_monto?: string;
    monto_cobrado?: string;
    estado: 'pagada' | 'pendiente' | 'anulada';
    sunat_estado?: string;
    sunat_xml_path?: string | null;
    sunat_cdr_path?: string | null;
    sunat_mensaje?: string | null;
    sunat_enviado_at?: string | null;
    metodo_pago: string;
    monto_recibido: string;
    vuelto: string;
    observacion: string | null;
    items?: SaleItem[];
    customer?: Customer | null;
    user?: { id: number; name: string } | null;
    warehouse?: { id: number; nombre: string } | null;
}

export interface Supplier {
    id: number;
    tipo_documento: string;
    numero_documento: string | null;
    razon_social: string;
    nombre_comercial: string | null;
    direccion: string | null;
    contacto: string | null;
    email: string | null;
    telefono: string | null;
    is_active: boolean;
}

export interface PurchaseItem {
    id: number;
    product_id: number | null;
    descripcion: string;
    cantidad: string;
    costo_unitario: string;
    descuento: string;
    afecto_igv: boolean;
    base: string;
    igv: string;
    total: string;
    product?: { id: number; codigo: string } | null;
}

export interface PurchasePayment {
    id: number;
    fecha: string;
    monto: string;
    metodo_pago: string;
    observacion: string | null;
    user?: { id: number; name: string } | null;
}

export interface Purchase {
    id: number;
    tipo_comprobante: string;
    serie: string | null;
    numero: string | null;
    fecha: string;
    fecha_vencimiento: string | null;
    moneda: string;
    subtotal: string;
    igv: string;
    total: string;
    percepcion_tasa?: string;
    percepcion_monto?: string;
    monto_pagado: string;
    estado_pago: 'pagada' | 'pendiente' | 'parcial';
    observacion: string | null;
    items?: PurchaseItem[];
    payments?: PurchasePayment[];
    supplier?: Supplier | null;
    user?: { id: number; name: string } | null;
    warehouse?: { id: number; nombre: string } | null;
}

export interface CashRegister {
    id: number;
    nombre: string;
    is_active: boolean;
    abiertas?: number;
}

export interface CashMovement {
    id: number;
    fecha: string;
    tipo: 'ingreso' | 'egreso';
    categoria: string;
    concepto: string;
    monto: string;
    metodo_pago: string;
    referencia: string | null;
    user?: { id: number; name: string } | null;
}

export interface CashSession {
    id: number;
    cash_register_id: number;
    fecha_apertura: string;
    monto_apertura: string;
    fecha_cierre: string | null;
    monto_esperado: string | null;
    monto_contado: string | null;
    diferencia: string | null;
    estado: 'abierta' | 'cerrada';
    observacion: string | null;
    ingresos?: number | string | null;
    egresos?: number | string | null;
    cash_register?: { id: number; nombre: string } | null;
    user?: { id: number; name: string } | null;
    movements?: CashMovement[];
}

export interface TransferItem {
    id: number;
    product_id: number | null;
    descripcion: string;
    cantidad: string;
    costo_unitario: string;
    product?: { id: number; codigo: string } | null;
}

export interface Transfer {
    id: number;
    fecha: string;
    estado: string;
    observacion: string | null;
    items_count?: number;
    items?: TransferItem[];
    origin?: { id: number; nombre: string } | null;
    dest?: { id: number; nombre: string } | null;
    user?: { id: number; name: string } | null;
}

export interface Note {
    id: number;
    tipo: 'credito' | 'debito';
    tipo_doc: string;
    serie: string;
    correlativo: number;
    fecha: string;
    moneda: string;
    cod_motivo: string;
    des_motivo: string;
    subtotal: string;
    igv: string;
    total: string;
    sunat_estado: string;
    sunat_xml_path: string | null;
    sunat_cdr_path: string | null;
    sale?: {
        id: number;
        serie: string;
        correlativo: number;
        tipo_comprobante: string;
    } | null;
    user?: { id: number; name: string } | null;
}

export interface Despatch {
    id: number;
    serie: string;
    correlativo: number;
    fecha_emision: string;
    fecha_traslado: string;
    cod_traslado: string;
    des_traslado: string;
    mod_traslado: string;
    dest_nombre: string;
    sunat_estado: string;
    sunat_xml_path: string | null;
    sunat_cdr_path: string | null;
    user?: { id: number; name: string } | null;
}

export interface QuoteItem {
    id: number;
    product_id: number | null;
    descripcion: string;
    cantidad: string;
    precio_unitario: string;
    descuento: string;
    total: string;
    product?: { id: number; codigo: string } | null;
}

export interface Quote {
    id: number;
    tipo: 'cotizacion' | 'pedido';
    serie: string;
    correlativo: number;
    fecha: string;
    fecha_validez: string | null;
    moneda: string;
    cliente_nombre: string;
    cliente_documento: string | null;
    subtotal: string;
    igv: string;
    descuento_total: string;
    total: string;
    estado: 'pendiente' | 'aprobada' | 'convertida' | 'anulada';
    sale_id: number | null;
    observacion: string | null;
    items?: QuoteItem[];
    user?: { id: number; name: string } | null;
    sale?: { id: number; serie: string; correlativo: number } | null;
}

export interface ProductLot {
    id: number;
    lote: string;
    fecha_vencimiento: string | null;
    cantidad: string;
    estado_calc?: string;
    product?: { id: number; codigo: string; nombre: string } | null;
    warehouse?: { id: number; nombre: string } | null;
}

export interface InventoryMovement {
    id: number;
    fecha: string;
    tipo: string;
    motivo: string | null;
    cantidad: string;
    costo_unitario: string;
    saldo_cantidad: string;
    saldo_costo_promedio: string;
    saldo_valor: string;
    referencia: string | null;
    warehouse?: { id: number; nombre: string } | null;
    user?: { id: number; name: string } | null;
    product?: { id: number; codigo: string; nombre: string } | null;
}
