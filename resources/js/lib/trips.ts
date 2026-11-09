import { TripEstado } from '@/types/models';

export const estadoTone: Record<TripEstado, 'slate' | 'sky' | 'emerald' | 'amber' | 'rose'> = {
    programado: 'slate',
    en_ruta: 'sky',
    entregado: 'emerald',
    incidencia: 'amber',
    cancelado: 'rose',
};

export function fechaCorta(iso: string | null): string {
    if (!iso) return '—';
    return iso.slice(0, 10).split('-').reverse().join('/');
}
