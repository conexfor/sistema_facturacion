import { formatCurrency, formatNumber } from '@/lib/format';
import {
    HandCoins,
    Landmark,
    type LucideIcon,
    Package,
    ShoppingCart,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    'shopping-cart': ShoppingCart,
    'trending-up': TrendingUp,
    'hand-coins': HandCoins,
    landmark: Landmark,
    package: Package,
};

const colorMap: Record<string, string> = {
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300',
    emerald:
        'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
};

export interface Stat {
    key: string;
    label: string;
    value: number;
    format: 'currency' | 'number';
    delta: number;
    icon: string;
    color: string;
}

export default function StatCard({
    stat,
    moneda,
}: {
    stat: Stat;
    moneda: string;
}) {
    const Icon = iconMap[stat.icon] ?? ShoppingCart;
    const positive = stat.delta >= 0;
    const display =
        stat.format === 'currency'
            ? formatCurrency(stat.value, moneda)
            : formatNumber(stat.value);

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-start justify-between">
                <div
                    className={`flex size-11 items-center justify-center rounded-xl ${
                        colorMap[stat.color] ?? colorMap.brand
                    }`}
                >
                    <Icon className="size-5" />
                </div>
                {stat.delta !== 0 && (
                    <span
                        className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ${
                            positive
                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400'
                        }`}
                    >
                        {positive ? (
                            <TrendingUp className="size-3" />
                        ) : (
                            <TrendingDown className="size-3" />
                        )}
                        {Math.abs(stat.delta)}%
                    </span>
                )}
            </div>
            <p className="mt-4 text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">
                {display}
            </p>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {stat.label}
            </p>
        </div>
    );
}
