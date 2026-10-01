import { formatCurrency, formatNumber } from '@/lib/format';
import {
    ArrowDownRight,
    ArrowUpRight,
    FileText,
    HandCoins,
    Landmark,
    type LucideIcon,
    Minus,
    Package,
    ShoppingCart,
    TrendingUp,
    Users,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    'shopping-cart': ShoppingCart,
    'trending-up': TrendingUp,
    'hand-coins': HandCoins,
    'file-text': FileText,
    users: Users,
    landmark: Landmark,
    package: Package,
};

/** Caja del ícono: fondo tenue del color de acento + ícono en el acento. */
const accentMap: Record<string, { box: string; hover: string }> = {
    cyan: {
        box: 'bg-[#00d4aa]/10 text-[#00a383] dark:bg-[#00d4aa]/12 dark:text-[#00d4aa]',
        hover: 'hover:border-[#00d4aa]/30',
    },
    blue: {
        box: 'bg-[#3b82f6]/10 text-[#2563eb] dark:bg-[#3b82f6]/12 dark:text-[#60a5fa]',
        hover: 'hover:border-[#3b82f6]/30',
    },
    purple: {
        box: 'bg-[#8b5cf6]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/12 dark:text-[#a78bfa]',
        hover: 'hover:border-[#8b5cf6]/30',
    },
    amber: {
        box: 'bg-[#f59e0b]/10 text-[#d97706] dark:bg-[#f59e0b]/12 dark:text-[#fbbf24]',
        hover: 'hover:border-[#f59e0b]/30',
    },
    rose: {
        box: 'bg-[#f43f5e]/10 text-[#e11d48] dark:bg-[#f43f5e]/12 dark:text-[#fb7185]',
        hover: 'hover:border-[#f43f5e]/30',
    },
};

export interface Stat {
    key: string;
    label: string;
    value: number;
    format: 'currency' | 'number';
    delta: number;
    icon: string;
    color: string;
    hint?: string;
    /** Monto opcional que antecede al hint (p. ej. "S/ 1,200.00 por cobrar"). */
    hintAmount?: number;
}

export default function StatCard({
    stat,
    moneda,
}: {
    stat: Stat;
    moneda: string;
}) {
    const Icon = iconMap[stat.icon] ?? ShoppingCart;
    const accent = accentMap[stat.color] ?? accentMap.cyan;
    const display =
        stat.format === 'currency'
            ? formatCurrency(stat.value, moneda)
            : formatNumber(stat.value);

    const trend =
        stat.delta > 0
            ? {
                  Icon: ArrowUpRight,
                  cls: 'bg-[#00d4aa]/10 text-[#00a383] dark:text-[#00d4aa]',
              }
            : stat.delta < 0
              ? {
                    Icon: ArrowDownRight,
                    cls: 'bg-[#f43f5e]/10 text-[#e11d48] dark:text-[#fb7185]',
                }
              : {
                    Icon: Minus,
                    cls: 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-ff-muted',
                };

    return (
        <div
            className={`group rounded-2xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:border-white/[0.06] dark:bg-ff-elevated/80 dark:hover:shadow-black/30 ${accent.hover}`}
        >
            <div className="flex items-start justify-between">
                <span
                    className={`flex size-10 items-center justify-center rounded-xl ${accent.box}`}
                >
                    <Icon className="size-5" />
                </span>
                <span
                    className={`inline-flex items-center gap-0.5 rounded-full px-2 py-1 font-mono text-[11px] font-semibold ${trend.cls}`}
                    title="Variación respecto al periodo anterior"
                >
                    <trend.Icon className="size-3.5" />
                    {Math.abs(stat.delta).toLocaleString('es-PE')}%
                </span>
            </div>

            <p className="mt-5 truncate font-display text-[28px] font-bold leading-none tracking-tight text-slate-900 tabular-nums dark:text-ff-text">
                {display}
            </p>
            <p className="mt-2 text-sm font-medium text-slate-600 dark:text-ff-muted">
                {stat.label}
            </p>
            {stat.hint && (
                <p className="mt-0.5 truncate text-xs text-slate-400 dark:text-ff-dim">
                    {stat.hintAmount !== undefined && (
                        <span className="font-mono font-medium text-slate-500 dark:text-ff-muted">
                            {formatCurrency(stat.hintAmount, moneda)}{' '}
                        </span>
                    )}
                    {stat.hint}
                </p>
            )}
        </div>
    );
}
