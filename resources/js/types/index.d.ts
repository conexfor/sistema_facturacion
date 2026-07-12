export interface User {
    id: number;
    company_id: number | null;
    branch_id: number | null;
    name: string;
    email: string;
    phone?: string | null;
    position?: string | null;
    avatar_path?: string | null;
    is_active?: boolean;
    last_login_at?: string | null;
    email_verified_at?: string;
}

export interface Company {
    id: number;
    ruc: string;
    razon_social: string;
    nombre_comercial: string | null;
    moneda: string;
    igv_porcentaje: string;
    logo_path: string | null;
}

export interface Branch {
    id: number;
    company_id: number;
    codigo: string;
    nombre: string;
    is_main: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    appName: string;
    auth: {
        user: User;
        company: Company | null;
        branch: Branch | null;
        roles: string[];
        permissions: string[];
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    notificaciones: {
        count: number;
        stock_bajo: {
            id: number;
            codigo: string;
            nombre: string;
            stock: number;
            minimo: number;
        }[];
    };
};
