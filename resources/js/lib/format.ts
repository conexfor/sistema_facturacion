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
