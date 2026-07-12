import Card from '@/Components/Card';
import Button from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { PackagePlus, Search, Trash2, Truck, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface WarehouseOpt {
    id: number;
    nombre: string;
}
interface ProductResult {
    id: number;
    codigo: string;
    nombre: string;
    precio_compra: string;
    afecto_igv: boolean;
    controla_stock: boolean;
    unit?: { abreviatura: string } | null;
}
interface Line {
    product_id: number;
    codigo: string;
    nombre: string;
    cantidad: number;
    costo_unitario: number;
    descuento: number;
    afecto_igv: boolean;
}
interface SupplierOpt {
    id: number;
    numero_documento: string | null;
    razon_social: string;
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}
function round2(n: number): number {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}

export default function Create({
    warehouses,
    defaultWarehouseId,
    igv,
    moneda,
}: {
    warehouses: WarehouseOpt[];
    defaultWarehouseId: number | null;
    igv: number;
    moneda: string;
}) {
    const [warehouseId, setWarehouseId] = useState(defaultWarehouseId ?? 0);
    const [tipo, setTipo] = useState('factura');
    const [serie, setSerie] = useState('');
    const [numero, setNumero] = useState('');
    const [fecha, setFecha] = useState(today());
    const [vencimiento, setVencimiento] = useState('');
    const [condicion, setCondicion] = useState('contado');
    const [metodo, setMetodo] = useState('efectivo');
    const [percepcionTasa, setPercepcionTasa] = useState('');
    const [observacion, setObservacion] = useState('');
    const [processing, setProcessing] = useState(false);

    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ProductResult[]>([]);
    const [lines, setLines] = useState<Line[]>([]);

    const [supplier, setSupplier] = useState<SupplierOpt | null>(null);
    const [supQuery, setSupQuery] = useState('');
    const [supResults, setSupResults] = useState<SupplierOpt[]>([]);
    const [showSup, setShowSup] = useState(false);

    useEffect(() => {
        if (query.trim() === '') {
            setResults([]);
            return;
        }
        const t = setTimeout(() => {
            axios
                .get(route('compras.buscar-productos'), { params: { q: query } })
                .then((r) => setResults(r.data))
                .catch(() => setResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [query]);

    useEffect(() => {
        if (!showSup) return;
        const t = setTimeout(() => {
            axios
                .get(route('compras.proveedores.buscar'), { params: { q: supQuery } })
                .then((r) => setSupResults(r.data))
                .catch(() => setSupResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [supQuery, showSup]);

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
                    costo_unitario: Number(p.precio_compra),
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

    const removeLine = (i: number) =>
        setLines((prev) => prev.filter((_, idx) => idx !== i));

    const totals = useMemo(() => {
        let subtotal = 0;
        let igvTotal = 0;
        for (const l of lines) {
            const base = Math.max(0, l.cantidad * l.costo_unitario - l.descuento);
            subtotal += base;
            if (l.afecto_igv) igvTotal += base * (igv / 100);
        }
        return {
            subtotal: round2(subtotal),
            igv: round2(igvTotal),
            total: round2(subtotal + igvTotal),
        };
    }, [lines, igv]);

    const percepcion = round2((totals.total * (Number(percepcionTasa) || 0)) / 100);
    const totalPagar = round2(totals.total + percepcion);

    const submit = () => {
        if (lines.length === 0 || processing) return;
        setProcessing(true);
        router.post(
            route('compras.store'),
            {
                supplier_id: supplier?.id ?? null,
                warehouse_id: warehouseId,
                tipo_comprobante: tipo,
                serie: serie || null,
                numero: numero || null,
                fecha,
                fecha_vencimiento: condicion === 'credito' ? vencimiento || null : null,
                condicion_pago: condicion,
                metodo_pago: metodo,
                percepcion_tasa: Number(percepcionTasa) || 0,
                observacion: observacion || null,
                items: lines.map((l) => ({
                    product_id: l.product_id,
                    cantidad: l.cantidad,
                    costo_unitario: l.costo_unitario,
                    descuento: l.descuento,
                    afecto_igv: l.afecto_igv,
                })),
            },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Nueva compra" />
            <PageHeader
                title="Registrar compra"
                description="Ingreso de mercadería al inventario"
                breadcrumbs={[
                    { label: 'Compras', href: route('compras.index') },
                    { label: 'Nueva' },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    {/* Datos del comprobante */}
                    <Card title="Datos del comprobante">
                        <div className="space-y-4">
                            {/* Proveedor */}
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Proveedor
                                </label>
                                {supplier ? (
                                    <div className="flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                        <span>
                                            <span className="block text-sm font-medium text-slate-800 dark:text-slate-100">
                                                {supplier.razon_social}
                                            </span>
                                            <span className="text-xs text-slate-400">
                                                {supplier.numero_documento}
                                            </span>
                                        </span>
                                        <button
                                            onClick={() => setSupplier(null)}
                                            className="rounded p-1 text-slate-400 hover:text-rose-600"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div className="relative">
                                        <Truck className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            value={supQuery}
                                            onFocus={() => setShowSup(true)}
                                            onChange={(e) => setSupQuery(e.target.value)}
                                            placeholder="Buscar proveedor por nombre o RUC…"
                                            className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                        />
                                        {showSup && supResults.length > 0 && (
                                            <div className="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                                {supResults.map((s) => (
                                                    <button
                                                        key={s.id}
                                                        onClick={() => {
                                                            setSupplier(s);
                                                            setShowSup(false);
                                                            setSupQuery('');
                                                        }}
                                                        className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                                    >
                                                        <span className="block font-medium text-slate-800 dark:text-slate-100">
                                                            {s.razon_social}
                                                        </span>
                                                        <span className="font-mono text-xs text-slate-400">
                                                            {s.numero_documento}
                                                        </span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <SelectField
                                    label="Comprobante"
                                    value={tipo}
                                    onChange={(e) => setTipo(e.target.value)}
                                    options={[
                                        { value: 'factura', label: 'Factura' },
                                        { value: 'boleta', label: 'Boleta' },
                                        { value: 'recibo', label: 'Recibo' },
                                        { value: 'nota', label: 'Nota' },
                                        { value: 'ticket', label: 'Ticket' },
                                    ]}
                                />
                                <TextField
                                    label="Serie"
                                    value={serie}
                                    onChange={(e) => setSerie(e.target.value)}
                                    placeholder="F001"
                                />
                                <TextField
                                    label="Número"
                                    value={numero}
                                    onChange={(e) => setNumero(e.target.value)}
                                    placeholder="000123"
                                />
                                <TextField
                                    label="Fecha"
                                    type="date"
                                    value={fecha}
                                    onChange={(e) => setFecha(e.target.value)}
                                />
                            </div>

                            <SelectField
                                label="Almacén de ingreso"
                                value={String(warehouseId)}
                                onChange={(e) => setWarehouseId(Number(e.target.value))}
                                options={warehouses.map((w) => ({ value: w.id, label: w.nombre }))}
                            />
                        </div>
                    </Card>

                    {/* Productos */}
                    <Card title="Productos">
                        <div className="relative mb-4">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Buscar producto a comprar…"
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
                                                <span className="block truncate font-medium text-slate-800 dark:text-slate-100">
                                                    {p.nombre}
                                                </span>
                                                <span className="font-mono text-xs text-slate-400">
                                                    {p.codigo}
                                                </span>
                                            </span>
                                            <span className="shrink-0 text-xs text-slate-400">
                                                últ. {formatCurrency(Number(p.precio_compra), moneda)}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {lines.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-10 text-center">
                                <PackagePlus className="mb-2 size-8 text-slate-300" />
                                <p className="text-sm text-slate-400">
                                    Busca productos para agregarlos a la compra
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="p-2 font-semibold">Producto</th>
                                            <th className="p-2 text-center font-semibold">Cant.</th>
                                            <th className="p-2 text-right font-semibold">Costo unit.</th>
                                            <th className="p-2 text-right font-semibold">Desc.</th>
                                            <th className="p-2 text-right font-semibold">Importe</th>
                                            <th className="p-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {lines.map((l, i) => {
                                            const base = Math.max(
                                                0,
                                                l.cantidad * l.costo_unitario - l.descuento,
                                            );
                                            return (
                                                <tr key={l.product_id}>
                                                    <td className="p-2">
                                                        <div className="font-medium text-slate-800 dark:text-slate-100">
                                                            {l.nombre}
                                                        </div>
                                                        <div className="font-mono text-xs text-slate-400">
                                                            {l.codigo}
                                                        </div>
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.0001"
                                                            value={l.cantidad}
                                                            onChange={(e) =>
                                                                updateLine(i, 'cantidad', Number(e.target.value))
                                                            }
                                                            className="w-20 rounded-lg border-slate-300 py-1 text-center text-sm dark:border-slate-700 dark:bg-slate-800"
                                                        />
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={l.costo_unitario}
                                                            onChange={(e) =>
                                                                updateLine(i, 'costo_unitario', Number(e.target.value))
                                                            }
                                                            className="w-24 rounded-lg border-slate-300 py-1 text-right text-sm dark:border-slate-700 dark:bg-slate-800"
                                                        />
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={l.descuento}
                                                            onChange={(e) =>
                                                                updateLine(i, 'descuento', Number(e.target.value))
                                                            }
                                                            className="w-20 rounded-lg border-slate-300 py-1 text-right text-sm dark:border-slate-700 dark:bg-slate-800"
                                                        />
                                                    </td>
                                                    <td className="p-2 text-right font-semibold">
                                                        {formatCurrency(base, moneda)}
                                                    </td>
                                                    <td className="p-2 text-right">
                                                        <button
                                                            onClick={() => removeLine(i)}
                                                            className="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30"
                                                        >
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

                {/* Resumen y pago */}
                <div className="space-y-4">
                    <Card title="Condición de pago">
                        <div className="space-y-3">
                            <SelectField
                                label="Condición"
                                value={condicion}
                                onChange={(e) => setCondicion(e.target.value)}
                                options={[
                                    { value: 'contado', label: 'Contado' },
                                    { value: 'credito', label: 'Crédito' },
                                ]}
                            />
                            {condicion === 'contado' ? (
                                <SelectField
                                    label="Método de pago"
                                    value={metodo}
                                    onChange={(e) => setMetodo(e.target.value)}
                                    options={[
                                        { value: 'efectivo', label: 'Efectivo' },
                                        { value: 'transferencia', label: 'Transferencia' },
                                        { value: 'deposito', label: 'Depósito' },
                                        { value: 'cheque', label: 'Cheque' },
                                        { value: 'tarjeta', label: 'Tarjeta' },
                                        { value: 'yape', label: 'Yape' },
                                        { value: 'plin', label: 'Plin' },
                                    ]}
                                />
                            ) : (
                                <TextField
                                    label="Fecha de vencimiento"
                                    type="date"
                                    value={vencimiento}
                                    onChange={(e) => setVencimiento(e.target.value)}
                                />
                            )}
                        </div>
                    </Card>

                    <Card title="Resumen">
                        <div className="mb-3">
                            <TextField
                                label="Percepción (%)"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value={percepcionTasa}
                                onChange={(e) => setPercepcionTasa(e.target.value)}
                                hint="Si el proveedor te cobra percepción (ej. 2%)"
                                placeholder="0"
                            />
                        </div>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                <dt>Subtotal (valor)</dt>
                                <dd>{formatCurrency(totals.subtotal, moneda)}</dd>
                            </div>
                            <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                <dt>IGV ({igv}%)</dt>
                                <dd>{formatCurrency(totals.igv, moneda)}</dd>
                            </div>
                            <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                <dt>Total comprobante</dt>
                                <dd>{formatCurrency(totals.total, moneda)}</dd>
                            </div>
                            {percepcion > 0 && (
                                <>
                                    <div className="flex justify-between text-amber-600">
                                        <dt>Percepción ({percepcionTasa}%)</dt>
                                        <dd>{formatCurrency(percepcion, moneda)}</dd>
                                    </div>
                                    <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                        <dt>Total a pagar</dt>
                                        <dd>{formatCurrency(totalPagar, moneda)}</dd>
                                    </div>
                                </>
                            )}
                        </dl>
                        <Button
                            onClick={submit}
                            disabled={lines.length === 0 || processing}
                            className="mt-4 w-full py-3 text-base"
                        >
                            <PackagePlus className="size-5" /> Registrar compra
                        </Button>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
