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
