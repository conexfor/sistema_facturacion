export type NotifyType = 'success' | 'error' | 'warning';

export interface NotifyDetail {
    type: NotifyType;
    message: string;
}

export const NOTIFY_EVENT = 'app:notify';

/**
 * Muestra un aviso (toast) desde fuera de React, p. ej. desde los eventos
 * globales de Inertia. Lo atiende AuthenticatedLayout; si la página actual no
 * tiene un layout que lo escuche (login, registro...), se usa un alert.
 */
export function notify(type: NotifyType, message: string): void {
    const event = new CustomEvent<NotifyDetail>(NOTIFY_EVENT, {
        detail: { type, message },
        cancelable: true,
    });

    // dispatchEvent devuelve false si algún listener llamó a preventDefault().
    const atendido = !window.dispatchEvent(event);
    if (!atendido) {
        window.alert(message);
    }
}
