import Card from '@/Components/Card';
import StatCard, { type Stat } from '@/Components/dashboard/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCompactCurrency, formatCurrency, formatNumber } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Wallet } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface DashboardProps {
    moneda: string;
    stats: Stat[];
    stockBajo: number;
    caja: { abierta: boolean; nombre?: string | null; saldo?: number };
    salesByMonth: { categories: string[]; ingresos: number[]; meta: number[] };
    salesByCategory: { labels: string[]; series: number[] };
    salesByDay: { dia: string; fecha: string; monto: number; cantidad: number }[];
    topProducts: { name: string; qty: number; amount: number }[];
    recentSales: {
        id: number;
        doc: string;
        cliente: string;
        tipo: string;
        total: number;
        estado: 'pagada' | 'pendiente' | 'vencida' | 'anulada' | string;
        fecha: string | null;
    }[];
}

const C = {
    cyan: '#00d4aa',
    blue: '#3b82f6',
    purple: '#8b5cf6',
    amber: '#f59e0b',
    rose: '#f43f5e',
};

/* Orden fijo del donut: azul y púrpura no quedan contiguos (se distinguen mejor). */
const CATEGORY_COLORS = [C.cyan, C.blue, C.amber, C.purple];

const TIPOS: Record<string, string> = {
    factura: 'Factura',
    boleta: 'Boleta',
    nota_venta: 'Nota de venta',
};

const ESTADOS: Record<string, { label: string; cls: string }> = {
    pagada: {
        label: 'Pagada',
        cls: 'border-[#00d4aa]/25 bg-[#00d4aa]/10 text-[#00826a] dark:text-[#00d4aa]',
    },
    pendiente: {
        label: 'Pendiente',
        cls: 'border-[#f59e0b]/25 bg-[#f59e0b]/10 text-[#b45309] dark:text-[#fbbf24]',
    },
    vencida: {
        label: 'Vencida',
        cls: 'border-[#f43f5e]/25 bg-[#f43f5e]/10 text-[#be123c] dark:text-[#fb7185]',
    },
    anulada: {
        label: 'Anulada',
        cls: 'border-slate-300 bg-slate-100 text-slate-500 line-through dark:border-white/10 dark:bg-white/5 dark:text-ff-muted',
    },
};

/** Sigue la clase `dark` de <html> (el toggle vive en el Topbar). */
function useIsDark(): boolean {
    const [dark, setDark] = useState(() =>
        typeof document === 'undefined'
            ? true
            : document.documentElement.classList.contains('dark'),
    );
    useEffect(() => {
        const root = document.documentElement;
        const obs = new MutationObserver(() =>
            setDark(root.classList.contains('dark')),
        );
        obs.observe(root, { attributes: true, attributeFilter: ['class'] });
        return () => obs.disconnect();
    }, []);
    return dark;
}

/** Tooltip glassmorphism compartido por los gráficos. */
function GlassTooltip({
    active,
    label,
    rows,
}: {
    active?: boolean;
    label?: ReactNode;
    rows: { color: string; name: string; value: string; dashed?: boolean }[];
}) {
    if (!active || rows.length === 0) return null;
    return (
        <div className="min-w-44 rounded-xl border border-slate-200 bg-white/90 px-3.5 py-3 shadow-xl backdrop-blur-xl dark:border-white/[0.09] dark:bg-[rgba(13,20,38,0.85)] dark:shadow-black/50">
            {label && (
                <p className="mb-2 font-display text-xs font-semibold text-slate-700 dark:text-ff-text">
                    {label}
                </p>
            )}
            <ul className="space-y-1.5">
                {rows.map((r) => (
                    <li key={r.name} className="flex items-center gap-2 text-xs">
                        <span
                            className="h-0.5 w-3 shrink-0 rounded-full"
                            style={
                                r.dashed
                                    ? { borderTop: `2px dashed ${r.color}`, height: 0 }
                                    : { backgroundColor: r.color, height: 8, width: 8 }
                            }
                        />
                        <span className="text-slate-500 dark:text-ff-muted">{r.name}</span>
                        <span className="ml-auto pl-4 font-mono font-semibold text-slate-800 tabular-nums dark:text-ff-text">
                            {r.value}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function LegendItem({
    color,
    label,
    dashed,
}: {
    color: string;
    label: string;
    dashed?: boolean;
}) {
    return (
        <span className="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-ff-muted">
            {dashed ? (
                <span className="w-4 border-t-2 border-dashed" style={{ borderColor: color }} />
            ) : (
                <span className="size-2 rounded-full" style={{ backgroundColor: color }} />
            )}
            {label}
        </span>
    );
}

export default function Dashboard({
    moneda,
    stats,
    stockBajo,
    caja,
    salesByMonth,
    salesByCategory,
    salesByDay,
    topProducts,
    recentSales,
}: DashboardProps) {
    const isDark = useIsDark();
    const ink = {
        grid: isDark ? 'rgba(255,255,255,0.05)' : '#e2e8f0',
        axis: isDark ? '#4a5568' : '#94a3b8',
        meta: isDark ? 'rgba(240,244,255,0.55)' : '#64748b',
        surface: isDark ? '#0d1426' : '#ffffff',
    };
    const axisTick = { fill: ink.axis, fontSize: 11, fontFamily: 'JetBrains Mono, monospace' };
    const money = (v: number) => formatCurrency(v, moneda);
    const compact = (v: number) => formatCompactCurrency(v, moneda);

    const monthly = salesByMonth.categories.map((mes, i) => ({
        mes,
        ingresos: salesByMonth.ingresos[i] ?? 0,
        meta: salesByMonth.meta[i] ?? 0,
    }));

    const categoryTotal = salesByCategory.series.reduce((a, b) => a + b, 0);
    const categories = salesByCategory.labels.map((name, i) => ({
        name,
        value: salesByCategory.series[i] ?? 0,
        color: CATEGORY_COLORS[i % CATEGORY_COLORS.length],
        pct: categoryTotal > 0 ? ((salesByCategory.series[i] ?? 0) / categoryTotal) * 100 : 0,
    }));

    const weekTotal = salesByDay.reduce((a, d) => a + d.monto, 0);
    const weekDocs = salesByDay.reduce((a, d) => a + d.cantidad, 0);
    const maxAmount = Math.max(...topProducts.map((p) => p.amount), 1);

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            {/* Alertas operativas */}
            {(stockBajo > 0 || caja.abierta) && (
                <div className="mb-5 flex flex-wrap gap-2">
                    {stockBajo > 0 && (
                        <Link
                            href={route('inventario.productos.index', { estado: 'activo' })}
                            className="inline-flex items-center gap-2 rounded-full border border-[#f59e0b]/25 bg-[#f59e0b]/10 px-3.5 py-1.5 text-xs font-medium text-[#b45309] transition duration-200 hover:bg-[#f59e0b]/15 dark:text-[#fbbf24]"
                        >
                            <AlertTriangle className="size-3.5" />
                            <span className="font-mono font-semibold">{stockBajo}</span>
                            producto(s) con stock bajo o agotado
                        </Link>
                    )}
                    {caja.abierta && (
                        <Link
                            href={route('caja.index')}
                            className="inline-flex items-center gap-2 rounded-full border border-[#00d4aa]/25 bg-[#00d4aa]/10 px-3.5 py-1.5 text-xs font-medium text-[#00826a] transition duration-200 hover:bg-[#00d4aa]/15 dark:text-[#00d4aa]"
                        >
                            <Wallet className="size-3.5" />
                            {caja.nombre} abierta · saldo
                            <span className="font-mono font-semibold">
                                {money(caja.saldo ?? 0)}
                            </span>
                        </Link>
                    )}
                </div>
            )}

            {/* KPIs */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 min-[1000px]:grid-cols-4">
                {stats.map((stat) => (
                    <StatCard key={stat.key} stat={stat} moneda={moneda} />
                ))}
            </div>

            {/* Ingresos mensuales + categorías */}
            <div className="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card
                    title="Ingresos mensuales"
                    subtitle="Últimos 12 meses frente a la meta (promedio de 3 meses previos +10%)"
                    className="lg:col-span-2"
                    action={
                        <div className="hidden items-center gap-4 sm:flex">
                            <LegendItem color={C.cyan} label="Ingresos" />
                            <LegendItem color={ink.meta} label="Meta" dashed />
                        </div>
                    }
                >
                    <div className="h-[320px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={monthly} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                                <defs>
                                    <linearGradient id="ffIngresos" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={C.cyan} stopOpacity={0.32} />
                                        <stop offset="100%" stopColor={C.cyan} stopOpacity={0} />
                                    </linearGradient>
                                    <linearGradient id="ffMeta" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={ink.meta} stopOpacity={0.08} />
                                        <stop offset="100%" stopColor={ink.meta} stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid vertical={false} stroke={ink.grid} strokeDasharray="4 6" />
                                <XAxis dataKey="mes" tick={axisTick} axisLine={false} tickLine={false} dy={8} />
                                <YAxis
                                    tick={axisTick}
                                    axisLine={false}
                                    tickLine={false}
                                    width={64}
                                    tickFormatter={compact}
                                />
                                <Tooltip
                                    cursor={{ stroke: ink.axis, strokeDasharray: '3 4' }}
                                    content={({ active, payload, label }) => (
                                        <GlassTooltip
                                            active={active}
                                            label={label}
                                            rows={[
                                                {
                                                    color: C.cyan,
                                                    name: 'Ingresos',
                                                    value: money(Number(payload?.[0]?.payload?.ingresos ?? 0)),
                                                },
                                                {
                                                    color: ink.meta,
                                                    name: 'Meta',
                                                    value: money(Number(payload?.[0]?.payload?.meta ?? 0)),
                                                    dashed: true,
                                                },
                                            ]}
                                        />
                                    )}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="meta"
                                    name="Meta"
                                    stroke={ink.meta}
                                    strokeWidth={1.5}
                                    strokeDasharray="5 5"
                                    fill="url(#ffMeta)"
                                    dot={false}
                                    activeDot={false}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="ingresos"
                                    name="Ingresos"
                                    stroke={C.cyan}
                                    strokeWidth={2.5}
                                    fill="url(#ffIngresos)"
                                    dot={false}
                                    activeDot={{ r: 5, stroke: ink.surface, strokeWidth: 2, fill: C.cyan }}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
                </Card>

                <Card title="Ventas por categoría" subtitle="Mes actual">
                    {categoryTotal > 0 ? (
                        <div className="flex h-[320px] flex-col">
                            <div className="relative min-h-0 flex-1">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={categories}
                                            dataKey="value"
                                            nameKey="name"
                                            innerRadius="68%"
                                            outerRadius="92%"
                                            paddingAngle={2}
                                            stroke={ink.surface}
                                            strokeWidth={2}
                                            cornerRadius={4}
                                        >
                                            {categories.map((c) => (
                                                <Cell key={c.name} fill={c.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            content={({ active, payload }) => {
                                                const p = payload?.[0]?.payload as
                                                    | (typeof categories)[number]
                                                    | undefined;
                                                return (
                                                    <GlassTooltip
                                                        active={active}
                                                        rows={
                                                            p
                                                                ? [{ color: p.color, name: p.name, value: money(p.value) }]
                                                                : []
                                                        }
                                                    />
                                                );
                                            }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-ff-dim">
                                        Total
                                    </span>
                                    <span className="font-display text-lg font-bold text-slate-800 dark:text-ff-text">
                                        {compact(categoryTotal)}
                                    </span>
                                </div>
                            </div>
                            <ul className="mt-4 grid grid-cols-2 gap-x-4 gap-y-2">
                                {categories.map((c) => (
                                    <li key={c.name} className="flex min-w-0 items-center gap-2 text-xs">
                                        <span className="size-2 shrink-0 rounded-full" style={{ backgroundColor: c.color }} />
                                        <span className="truncate text-slate-600 dark:text-ff-muted">{c.name}</span>
                                        <span className="ml-auto font-mono font-semibold text-slate-800 tabular-nums dark:text-ff-text">
                                            {c.pct.toFixed(0)}%
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : (
                        <div className="flex h-[320px] items-center justify-center text-center text-sm text-slate-400 dark:text-ff-dim">
                            Aún no hay ventas este mes.
                        </div>
                    )}
                </Card>
            </div>

            {/* Ventas diarias + productos top */}
            <div className="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card
                    title="Ventas diarias"
                    subtitle={
                        <>
                            Semana actual ·{' '}
                            <span className="font-mono">{money(weekTotal)}</span> en{' '}
                            <span className="font-mono">{formatNumber(weekDocs)}</span> comprobantes
                        </>
                    }
                    className="lg:col-span-2"
                    action={
                        <div className="hidden items-center gap-4 sm:flex">
                            <LegendItem color={C.blue} label="Monto (eje izq.)" />
                            <LegendItem color={C.purple} label="Comprobantes (eje der.)" />
                        </div>
                    }
                >
                    <div className="h-[280px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={salesByDay} margin={{ top: 8, right: 0, left: 0, bottom: 0 }} barGap={4}>
                                <defs>
                                    <linearGradient id="ffMonto" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={C.blue} stopOpacity={1} />
                                        <stop offset="100%" stopColor={C.blue} stopOpacity={0.35} />
                                    </linearGradient>
                                    <linearGradient id="ffCantidad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={C.purple} stopOpacity={1} />
                                        <stop offset="100%" stopColor={C.purple} stopOpacity={0.35} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid vertical={false} stroke={ink.grid} strokeDasharray="4 6" />
                                <XAxis dataKey="dia" tick={axisTick} axisLine={false} tickLine={false} dy={8} />
                                <YAxis
                                    yAxisId="monto"
                                    tick={axisTick}
                                    axisLine={false}
                                    tickLine={false}
                                    width={64}
                                    tickFormatter={compact}
                                />
                                <YAxis
                                    yAxisId="cantidad"
                                    orientation="right"
                                    tick={axisTick}
                                    axisLine={false}
                                    tickLine={false}
                                    width={36}
                                    allowDecimals={false}
                                />
                                <Tooltip
                                    cursor={{ fill: isDark ? 'rgba(255,255,255,0.03)' : 'rgba(15,23,42,0.04)' }}
                                    content={({ active, payload }) => {
                                        const d = payload?.[0]?.payload as
                                            | DashboardProps['salesByDay'][number]
                                            | undefined;
                                        return (
                                            <GlassTooltip
                                                active={active}
                                                label={
                                                    d
                                                        ? `${d.dia} ${new Date(`${d.fecha}T00:00:00`).toLocaleDateString('es-PE', { day: '2-digit', month: 'short' })}`
                                                        : undefined
                                                }
                                                rows={
                                                    d
                                                        ? [
                                                              { color: C.blue, name: 'Monto', value: money(d.monto) },
                                                              { color: C.purple, name: 'Comprobantes', value: formatNumber(d.cantidad) },
                                                          ]
                                                        : []
                                                }
                                            />
                                        );
                                    }}
                                />
                                <Bar
                                    yAxisId="monto"
                                    dataKey="monto"
                                    name="Monto"
                                    fill="url(#ffMonto)"
                                    radius={[6, 6, 0, 0]}
                                    maxBarSize={28}
                                />
                                <Bar
                                    yAxisId="cantidad"
                                    dataKey="cantidad"
                                    name="Comprobantes"
                                    fill="url(#ffCantidad)"
                                    radius={[6, 6, 0, 0]}
                                    maxBarSize={28}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </Card>

                <Card title="Productos más vendidos" subtitle="Por importe del mes">
                    {topProducts.length === 0 ? (
                        <p className="flex h-[280px] items-center justify-center text-sm text-slate-400 dark:text-ff-dim">
                            Sin ventas registradas este mes.
                        </p>
                    ) : (
                        <ul className="space-y-4">
                            {topProducts.map((p, i) => (
                                <li key={i}>
                                    <div className="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                        <span className="truncate text-slate-700 dark:text-ff-text">
                                            <span className="mr-1.5 font-mono text-xs text-slate-400 dark:text-ff-dim">
                                                {String(i + 1).padStart(2, '0')}
                                            </span>
                                            {p.name}
                                        </span>
                                        <span className="shrink-0 font-mono text-xs font-semibold text-slate-800 tabular-nums dark:text-ff-text">
                                            {money(p.amount)}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-white/[0.05]">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-[#00b894] to-[#00d4aa]"
                                                style={{ width: `${(p.amount / maxAmount) * 100}%` }}
                                            />
                                        </div>
                                        <span className="w-16 text-right font-mono text-[11px] text-slate-400 dark:text-ff-dim">
                                            {formatNumber(p.qty)} und
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            {/* Últimas facturas */}
            <Card
                title="Últimos comprobantes"
                subtitle="Emisiones más recientes"
                className="mt-5"
                bodyClassName="p-0"
                action={
                    <Link
                        href={route('ventas.index')}
                        className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition duration-200 hover:border-[#00d4aa]/40 hover:text-[#00826a] dark:border-white/[0.08] dark:text-ff-muted dark:hover:text-ff-cyan"
                    >
                        Ver todas <ArrowRight className="size-3.5" />
                    </Link>
                }
            >
                {recentSales.length === 0 ? (
                    <p className="py-10 text-center text-sm text-slate-400 dark:text-ff-dim">
                        Aún no se han registrado ventas.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-[11px] uppercase tracking-[0.1em] text-slate-400 dark:text-ff-dim">
                                    <th className="px-5 py-3 font-semibold">N° Comprobante</th>
                                    <th className="px-5 py-3 font-semibold">Cliente</th>
                                    <th className="px-5 py-3 font-semibold">Tipo</th>
                                    <th className="px-5 py-3 text-right font-semibold">Monto</th>
                                    <th className="px-5 py-3 font-semibold">Estado</th>
                                    <th className="px-5 py-3 text-right font-semibold">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentSales.map((s) => {
                                    const estado = ESTADOS[s.estado] ?? ESTADOS.pendiente;
                                    return (
                                        <tr
                                            key={s.id}
                                            onClick={() => router.visit(route('ventas.show', s.id))}
                                            className="group cursor-pointer border-t border-slate-100 transition duration-200 hover:bg-slate-50 dark:border-white/[0.04] dark:hover:bg-white/[0.02]"
                                        >
                                            <td className="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-medium text-[#00826a] dark:text-ff-cyan">
                                                {s.doc}
                                            </td>
                                            <td className="max-w-[280px] truncate px-5 py-3.5 text-slate-700 transition duration-200 group-hover:text-[#00826a] dark:text-ff-text dark:group-hover:text-ff-cyan">
                                                {s.cliente}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-slate-500 dark:text-ff-muted">
                                                {TIPOS[s.tipo] ?? s.tipo}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-right font-mono font-bold text-slate-800 tabular-nums dark:text-ff-text">
                                                {money(s.total)}
                                            </td>
                                            <td className="px-5 py-3.5">
                                                <span
                                                    className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${estado.cls}`}
                                                >
                                                    {estado.label}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-right font-mono text-xs text-slate-500 dark:text-ff-muted">
                                                {s.fecha
                                                    ? new Date(`${s.fecha}T00:00:00`).toLocaleDateString('es-PE')
                                                    : '—'}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </AuthenticatedLayout>
    );
}
