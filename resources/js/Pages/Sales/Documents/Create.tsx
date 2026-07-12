import Card from '@/Components/Card';
import Button from '@/Components/ui/Button';
import { TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { FileText, Search, Trash2, UserRound, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface ProductResult {
    id: number;
    codigo: string;
    nombre: string;
    precio_venta: string;
    afecto_igv: boolean;
}
interface Line {
    product_id: number;
    codigo: string;
    nombre: string;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
    afecto_igv: boolean;
}
interface ClienteOpt {
    id: number;
    numero_documento: string | null;
    razon_social: string;
}

function today() {
    return new Date().toISOString().slice(0, 10);
}
function round2(n: number) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}

export default function Create({
    tipo,
    igv,
    moneda,
}: {
    tipo: 'cotizacion' | 'pedido';
    igv: number;
    moneda: string;
}) {
    const esCot = tipo === 'cotizacion';
    const [fecha, setFecha] = useState(today());
    const [validez, setValidez] = useState('');
    const [observacion, setObservacion] = useState('');
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ProductResult[]>([]);
    const [lines, setLines] = useState<Line[]>([]);
    const [processing, setProcessing] = useState(false);

    const [cliente, setCliente] = useState<ClienteOpt | null>(null);
    const [cQuery, setCQuery] = useState('');
    const [cResults, setCResults] = useState<ClienteOpt[]>([]);
    const [showC, setShowC] = useState(false);

    useEffect(() => {
        const t = setTimeout(() => {
            axios
                .get(route('documentos.buscar-productos'), { params: { q: query } })
                .then((r) => setResults(r.data))
                .catch(() => setResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [query]);

    useEffect(() => {
        if (!showC) return;
        const t = setTimeout(() => {
            axios
                .get(route('documentos.buscar-clientes'), { params: { q: cQuery } })
                .then((r) => setCResults(r.data))
                .catch(() => setCResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [cQuery, showC]);

    const addLine = (p: ProductResult) => {
        setLines((prev) => {
            if (prev.some((l) => l.product_id === p.id)) return prev;
            return [
                ...prev,
                {
                    product_id: p.id,
                    codigo: p.codigo,
                    nombre: p.nombre,
                    cantidad: 1,
                    precio_unitario: Number(p.precio_venta),
                    descuento: 0,
                    afecto_igv: p.afecto_igv,
                },
            ];
        });
        setQuery('');
        setResults([]);
    };
    const updateLine = (i: number, field: keyof Line, value: number) =>
        setLines((prev) => {
            const next = [...prev];
            next[i] = { ...next[i], [field]: value };
            return next;
        });
    const removeLine = (i: number) => setLines((prev) => prev.filter((_, idx) => idx !== i));

    const totals = useMemo(() => {
        let total = 0;
        let igvTotal = 0;
        for (const l of lines) {
            const lt = Math.max(0, l.cantidad * l.precio_unitario - l.descuento);
            total += lt;
            if (l.afecto_igv) igvTotal += lt - lt / (1 + igv / 100);
        }
        return { total: round2(total), igv: round2(igvTotal), subtotal: round2(total - igvTotal) };
    }, [lines, igv]);

    const submit = () => {
        if (lines.length === 0 || processing) return;
        setProcessing(true);
        router.post(
            route('documentos.store'),
            {
                tipo,
                customer_id: cliente?.id ?? null,
                fecha,
                fecha_validez: esCot ? validez || null : null,
                observacion: observacion || null,
                items: lines.map((l) => ({
                    product_id: l.product_id,
                    cantidad: l.cantidad,
                    precio_unitario: l.precio_unitario,
                    descuento: l.descuento,
                })),
            },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={esCot ? 'Nueva cotización' : 'Nuevo pedido'} />
            <PageHeader
                title={esCot ? 'Nueva cotización' : 'Nuevo pedido'}
                breadcrumbs={[
                    { label: 'Ventas' },
                    {
                        label: esCot ? 'Cotizaciones' : 'Pedidos',
                        href: route(esCot ? 'cotizaciones.index' : 'pedidos.index'),
                    },
                    { label: 'Nuevo' },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <Card title="Datos">
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Cliente
                                </label>
                                {cliente ? (
                                    <div className="flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                        <span className="text-sm font-medium text-slate-800 dark:text-slate-100">
                                            {cliente.razon_social}
                                        </span>
                                        <button onClick={() => setCliente(null)} className="rounded p-1 text-slate-400 hover:text-rose-600">
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div className="relative">
                                        <UserRound className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            value={cQuery}
                                            onFocus={() => setShowC(true)}
                                            onChange={(e) => setCQuery(e.target.value)}
                                            placeholder="Sin cliente (buscar…)"
                                            className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                        />
                                        {showC && cResults.length > 0 && (
                                            <div className="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                                {cResults.map((c) => (
                                                    <button
                                                        key={c.id}
                                                        onClick={() => {
                                                            setCliente(c);
                                                            setShowC(false);
                                                        }}
                                                        className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                                    >
                                                        {c.razon_social}
                                                        <span className="ml-2 font-mono text-xs text-slate-400">{c.numero_documento}</span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <TextField label="Fecha" type="date" value={fecha} onChange={(e) => setFecha(e.target.value)} />
                                {esCot && (
                                    <TextField label="Válido hasta" type="date" value={validez} onChange={(e) => setValidez(e.target.value)} />
                                )}
                                <TextField label="Observación" value={observacion} onChange={(e) => setObservacion(e.target.value)} />
                            </div>
                        </div>
                    </Card>

                    <Card title="Productos">
                        <div className="relative mb-4">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Buscar producto…"
                                className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                            />
                            {results.length > 0 && (
                                <div className="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                    {results.map((p) => (
                                        <button
                                            key={p.id}
                                            onClick={() => addLine(p)}
                                            className="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                        >
                                            <span className="min-w-0">
                                                <span className="block truncate font-medium text-slate-800 dark:text-slate-100">{p.nombre}</span>
                                                <span className="font-mono text-xs text-slate-400">{p.codigo}</span>
                                            </span>
                                            <span className="shrink-0 font-semibold text-brand-600">
                                                {formatCurrency(Number(p.precio_venta), moneda)}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {lines.length === 0 ? (
                            <p className="py-8 text-center text-sm text-slate-400">Busca productos para agregarlos</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="p-2 font-semibold">Producto</th>
                                            <th className="p-2 text-center font-semibold">Cant.</th>
                                            <th className="p-2 text-right font-semibold">Precio</th>
                                            <th className="p-2 text-right font-semibold">Desc.</th>
                                            <th className="p-2 text-right font-semibold">Importe</th>
                                            <th className="p-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {lines.map((l, i) => {
                                            const lt = Math.max(0, l.cantidad * l.precio_unitario - l.descuento);
                                            return (
                                                <tr key={l.product_id}>
                                                    <td className="p-2">
                                                        <div className="font-medium text-slate-800 dark:text-slate-100">{l.nombre}</div>
                                                        <div className="font-mono text-xs text-slate-400">{l.codigo}</div>
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        <input type="number" min="0" step="0.0001" value={l.cantidad}
                                                            onChange={(e) => updateLine(i, 'cantidad', Number(e.target.value))}
                                                            className="w-20 rounded-lg border-slate-300 py-1 text-center text-sm dark:border-slate-700 dark:bg-slate-800" />
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        <input type="number" min="0" step="0.01" value={l.precio_unitario}
                                                            onChange={(e) => updateLine(i, 'precio_unitario', Number(e.target.value))}
                                                            className="w-24 rounded-lg border-slate-300 py-1 text-right text-sm dark:border-slate-700 dark:bg-slate-800" />
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        <input type="number" min="0" step="0.01" value={l.descuento}
                                                            onChange={(e) => updateLine(i, 'descuento', Number(e.target.value))}
                                                            className="w-20 rounded-lg border-slate-300 py-1 text-right text-sm dark:border-slate-700 dark:bg-slate-800" />
                                                    </td>
                                                    <td className="p-2 text-right font-semibold">{formatCurrency(lt, moneda)}</td>
                                                    <td className="p-2 text-right">
                                                        <button onClick={() => removeLine(i)} className="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30">
                                                            <Trash2 className="size-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Resumen">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                <dt>Subtotal</dt>
                                <dd>{formatCurrency(totals.subtotal, moneda)}</dd>
                            </div>
                            <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                <dt>IGV ({igv}%)</dt>
                                <dd>{formatCurrency(totals.igv, moneda)}</dd>
                            </div>
                            <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                <dt>Total</dt>
                                <dd>{formatCurrency(totals.total, moneda)}</dd>
                            </div>
                        </dl>
                        <Button onClick={submit} disabled={lines.length === 0 || processing} className="mt-4 w-full py-3 text-base">
                            <FileText className="size-5" /> Guardar {esCot ? 'cotización' : 'pedido'}
                        </Button>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
