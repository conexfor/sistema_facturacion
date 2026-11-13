/**
 * Fecha local (YYYY-MM-DD) de un Date, por defecto hoy. No usar
 * toISOString().slice(0, 10): da la fecha UTC, que en Perú después de las
 * 7 p. m. ya es el día siguiente.
 */
export function fechaLocal(d: Date = new Date()): string {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
}

const currencySymbols: Record<string, string> = {
    PEN: 'S/',
    USD: '$',
    EUR: '€',
};

export function formatCurrency(value: number, moneda = 'PEN'): string {
    const symbol = currencySymbols[moneda] ?? moneda + ' ';
    return (
        symbol +
        ' ' +
        value.toLocaleString('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

export function formatNumber(value: number): string {
    return value.toLocaleString('es-PE');
}

export function currencySymbol(moneda = 'PEN'): string {
    return currencySymbols[moneda] ?? moneda;
}

/** Formato compacto para ejes de gráficos: S/ 12.5k, S/ 1.2M. */
export function formatCompactCurrency(value: number, moneda = 'PEN'): string {
    const symbol = currencySymbol(moneda);
    const abs = Math.abs(value);
    if (abs >= 1_000_000) return `${symbol}${(value / 1_000_000).toFixed(1).replace(/\.0$/, '')}M`;
    if (abs >= 1_000) return `${symbol}${(value / 1_000).toFixed(abs >= 10_000 ? 0 : 1).replace(/\.0$/, '')}k`;
    return `${symbol}${Math.round(value)}`;
}
