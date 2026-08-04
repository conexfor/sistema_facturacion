import Modal from '@/Components/Modal';
import Button from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatCurrency } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import {
    Loader2,
    Search,
    Search as SearchIcon,
    ShoppingCart,
    Trash2,
    UserPlus,
    UserRound,
    X,
} from 'lucide-react';
import { FormEvent, useEffect, useMemo, useRef, useState } from 'react';

const tiposDoc = [
    { value: '6', label: 'RUC' },
    { value: '1', label: 'DNI' },
    { value: '7', label: 'Pasaporte' },
    { value: '4', label: 'Carné de extranjería' },
    { value: '0', label: 'Otros' },
];

interface WarehouseOpt {
    id: number;
    nombre: string;
}
interface ProductResult {
    id: number;
    codigo: string;
    nombre: string;
    precio_venta: string;
    afecto_igv: boolean;
    controla_stock: boolean;
    stock: number | null;
    unit?: { abreviatura: string } | null;
}
interface CartLine {
    product_id: number;
    codigo: string;
    nombre: string;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
    afecto_igv: boolean;
    controla_stock: boolean;
    stock: number;
    unidad: string;
}
interface CustomerOpt {
    id: number;
    tipo_documento: string;
    numero_documento: string | null;
    razon_social: string;
}

const metodos = [
    { value: 'efectivo', label: 'Efectivo' },
    { value: 'tarjeta', label: 'Tarjeta' },
    { value: 'yape', label: 'Yape' },
    { value: 'plin', label: 'Plin' },
    { value: 'transferencia', label: 'Transferencia' },
    { value: 'credito', label: 'Crédito' },
];

export default function Pos({
    warehouses,
    defaultWarehouseId,
    igv,
    moneda,
    agentePercepcion = false,
    percepcionTasa = 2,
}: {
    warehouses: WarehouseOpt[];
    defaultWarehouseId: number | null;
    igv: number;
    moneda: string;
    agentePercepcion?: boolean;
    percepcionTasa?: number;
}) {
    const [warehouseId, setWarehouseId] = useState<number>(defaultWarehouseId ?? 0);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ProductResult[]>([]);
    const [cart, setCart] = useState<CartLine[]>([]);
    const [tipo, setTipo] = useState('boleta');
    const [metodo, setMetodo] = useState('efectivo');
    const [montoRecibido, setMontoRecibido] = useState('');
    const [aplicaPercepcion, setAplicaPercepcion] = useState(false);
    const [observacion, setObservacion] = useState('');
    const [processing, setProcessing] = useState(false);

    const [customer, setCustomer] = useState<CustomerOpt | null>(null);
    const [custQuery, setCustQuery] = useState('');
    const [custResults, setCustResults] = useState<CustomerOpt[]>([]);
    const [showCust, setShowCust] = useState(false);

    // Alta rápida de cliente (RUC/DNI) desde el POS
    const [showNewCust, setShowNewCust] = useState(false);
    const [nc, setNc] = useState({
        tipo_documento: '6',
        numero_documento: '',
        razon_social: '',
        direccion: '',
        telefono: '',
        email: '',
    });
    const [ncLoading, setNcLoading] = useState(false);
    const [ncError, setNcError] = useState<string | null>(null);

    const searchRef = useRef<HTMLInputElement>(null);

    const setNcField = (field: keyof typeof nc, value: string) =>
        setNc((prev) => ({ ...prev, [field]: value }));

    const openNewCustomer = () => {
        setNc({
            tipo_documento: '6',
            numero_documento: custQuery.replace(/\D/g, ''),
            razon_social: '',
            direccion: '',
            telefono: '',
            email: '',
        });
        setNcError(null);
        setShowNewCust(true);
    };

    const ncConsultar = () => {
        setNcError(null);
        setNcLoading(true);
        axios
            .get(route('clientes.consultar'), {
                params: { tipo: nc.tipo_documento, numero: nc.numero_documento },
            })
            .then((r) => {
                const d = r.data.data;
                setNc((prev) => ({
                    ...prev,
                    razon_social: d.razon_social || prev.razon_social,
                    direccion: d.direccion ?? prev.direccion,
                }));
            })
            .catch((e) =>
                setNcError(e.response?.data?.message ?? 'No se pudo consultar el documento.'),
            )
            .finally(() => setNcLoading(false));
    };

    const ncGuardar = (e: FormEvent) => {
        e.preventDefault();
        setNcError(null);
        setNcLoading(true);
        axios
            .post(route('clientes.rapido'), {
                tipo_documento: nc.tipo_documento,
                numero_documento: nc.numero_documento || null,
                razon_social: nc.razon_social,
                direccion: nc.direccion || null,
                telefono: nc.telefono || null,
                email: nc.email || null,
            })
            .then((r) => {
                setCustomer(r.data);
                setShowNewCust(false);
                setCustQuery('');
                setShowCust(false);
            })
            .catch((e) => {
                const errs = e.response?.data?.errors;
                const first = errs ? (Object.values(errs)[0] as string[])[0] : null;
                setNcError(first ?? e.response?.data?.message ?? 'No se pudo guardar el cliente.');
            })
            .finally(() => setNcLoading(false));
    };

    const ncCanLookup =
        ['1', '6'].includes(nc.tipo_documento) && nc.numero_documento.length >= 8;

    // Búsqueda de productos (debounced). No busca con texto vacío para que el
    // dropdown desaparezca al seleccionar un producto.
    useEffect(() => {
        if (query.trim() === '') {
            setResults([]);
            return;
        }
        const t = setTimeout(() => {
            axios
                .get(route('ventas.buscar-productos'), {
                    params: { q: query, warehouse_id: warehouseId },
                })
                .then((r) => setResults(r.data))
                .catch(() => setResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [query, warehouseId]);

    // Búsqueda de clientes (debounced)
    useEffect(() => {
        if (!showCust) return;
        const t = setTimeout(() => {
            axios
                .get(route('ventas.buscar-clientes'), { params: { q: custQuery } })
                .then((r) => setCustResults(r.data))
                .catch(() => setCustResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [custQuery, showCust]);

    const addToCart = (p: ProductResult) => {
        setCart((prev) => {
            const i = prev.findIndex((l) => l.product_id === p.id);
            if (i >= 0) {
                const next = [...prev];
                next[i] = { ...next[i], cantidad: next[i].cantidad + 1 };
                return next;
            }
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
                    controla_stock: p.controla_stock,
                    stock: Number(p.stock ?? 0),
                    unidad: p.unit?.abreviatura ?? '',
                },
            ];
        });
        setQuery('');
        setResults([]);
        searchRef.current?.focus();
    };

    const updateLine = (i: number, field: keyof CartLine, value: number) => {
        setCart((prev) => {
            const next = [...prev];
            next[i] = { ...next[i], [field]: value };
            return next;
        });
    };

    const removeLine = (i: number) =>
        setCart((prev) => prev.filter((_, idx) => idx !== i));

    const totals = useMemo(() => {
        let total = 0;
        let igvTotal = 0;
        let descuento = 0;
        for (const l of cart) {
            const lineTotal = Math.max(0, l.cantidad * l.precio_unitario - l.descuento);
            total += lineTotal;
            descuento += l.descuento;
            if (l.afecto_igv) {
                igvTotal += lineTotal - lineTotal / (1 + igv / 100);
            }
        }
        return {
            total: round2(total),
            igv: round2(igvTotal),
            subtotal: round2(total - igvTotal),
            descuento: round2(descuento),
        };
    }, [cart, igv]);

    const percepcion =
        agentePercepcion && aplicaPercepcion ? round2((totals.total * percepcionTasa) / 100) : 0;
    const totalCobrar = round2(totals.total + percepcion);

    const recibido = montoRecibido === '' ? 0 : Number(montoRecibido);
    const vuelto = metodo === 'efectivo' ? Math.max(0, round2(recibido - totalCobrar)) : 0;

    const hayStockInsuficiente = cart.some(
        (l) => l.controla_stock && l.cantidad > l.stock,
    );

    const clienteIdentificado =
        !!customer && customer.tipo_documento !== '0' && !!customer.numero_documento;
    const boletaRequiereCliente = tipo === 'boleta' && totals.total > 700 && !clienteIdentificado;

    const submit = () => {
        if (cart.length === 0 || processing || boletaRequiereCliente) return;
        setProcessing(true);
        router.post(
            route('ventas.pos.store'),
            {
                tipo_comprobante: tipo,
                warehouse_id: warehouseId,
                customer_id: customer?.id ?? null,
                metodo_pago: metodo,
                monto_recibido: metodo === 'efectivo' ? recibido : totalCobrar,
                aplica_percepcion: agentePercepcion && aplicaPercepcion,
                observacion: observacion || null,
                items: cart.map((l) => ({
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
            <Head title="Punto de venta" />
            <PageHeader
                title="Punto de venta"
                description="Registra ventas de forma rápida"
                breadcrumbs={[{ label: 'Ventas' }, { label: 'POS' }]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                {/* Productos + carrito */}
                <div className="space-y-4 lg:col-span-2">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    ref={searchRef}
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    placeholder="Buscar producto por nombre, código o código de barras…"
                                    className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    autoFocus
                                />
                                {results.length > 0 && (
                                    <div className="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                        {results.map((p) => (
                                            <button
                                                key={p.id}
                                                onClick={() => addToCart(p)}
                                                className="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                            >
                                                <span className="min-w-0">
                                                    <span className="block truncate font-medium text-slate-800 dark:text-slate-100">
                                                        {p.nombre}
                                                    </span>
                                                    <span className="font-mono text-xs text-slate-400">
                                                        {p.codigo}
                                                        {p.controla_stock &&
                                                            ` · stock ${p.stock ?? 0}`}
                                                    </span>
                                                </span>
                                                <span className="shrink-0 font-semibold text-brand-600">
                                                    {formatCurrency(Number(p.precio_venta), moneda)}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                            <div className="sm:w-52">
                                <SelectField
                                    value={String(warehouseId)}
                                    onChange={(e) => setWarehouseId(Number(e.target.value))}
                                    options={warehouses.map((w) => ({
                                        value: w.id,
                                        label: w.nombre,
                                    }))}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        {cart.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <ShoppingCart className="mb-3 size-10 text-slate-300" />
                                <p className="text-sm text-slate-400">
                                    Busca y agrega productos para iniciar la venta
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="p-3 font-semibold">Producto</th>
                                            <th className="p-3 text-center font-semibold">Cantidad</th>
                                            <th className="p-3 text-right font-semibold">Precio</th>
                                            <th className="p-3 text-right font-semibold">Desc.</th>
                                            <th className="p-3 text-right font-semibold">Importe</th>
                                            <th className="p-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {cart.map((l, i) => {
                                            const lineTotal = Math.max(
                                                0,
                                                l.cantidad * l.precio_unitario - l.descuento,
                                            );
                                            const low = l.controla_stock && l.cantidad > l.stock;
                                            return (
                                                <tr key={l.product_id}>
                                                    <td className="p-3">
                                                        <div className="font-medium text-slate-800 dark:text-slate-100">
                                                            {l.nombre}
                                                        </div>
                                                        <div className="font-mono text-xs text-slate-400">
                                                            {l.codigo}
                                                            {low && (
                                                                <span className="ml-2 text-rose-500">
                                                                    stock: {l.stock}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="p-3 text-center">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.0001"
                                                            value={l.cantidad}
                                                            onChange={(e) =>
                                                                updateLine(i, 'cantidad', Number(e.target.value))
                                                            }
                                                            className={`w-20 rounded-lg border-slate-300 py-1 text-center text-sm dark:border-slate-700 dark:bg-slate-800 ${
                                                                low ? 'border-rose-400 text-rose-600' : ''
                                                            }`}
                                                        />
                                                    </td>
                                                    <td className="p-3 text-right">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={l.precio_unitario}
                                                            onChange={(e) =>
                                                                updateLine(i, 'precio_unitario', Number(e.target.value))
                                                            }
                                                            className="w-24 rounded-lg border-slate-300 py-1 text-right text-sm dark:border-slate-700 dark:bg-slate-800"
                                                        />
                                                    </td>
                                                    <td className="p-3 text-right">
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
                                                    <td className="p-3 text-right font-semibold">
                                                        {formatCurrency(lineTotal, moneda)}
                                                    </td>
                                                    <td className="p-3 text-right">
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
                    </div>
                </div>

                {/* Panel de cobro */}
                <div className="space-y-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        {/* Cliente */}
                        <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Cliente
                        </label>
                        {customer ? (
                            <div className="flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                <span className="min-w-0">
                                    <span className="block truncate text-sm font-medium text-slate-800 dark:text-slate-100">
                                        {customer.razon_social}
                                    </span>
                                    <span className="text-xs text-slate-400">
                                        {customer.numero_documento}
                                    </span>
                                </span>
                                <button
                                    onClick={() => setCustomer(null)}
                                    className="rounded p-1 text-slate-400 hover:text-rose-600"
                                >
                                    <X className="size-4" />
                                </button>
                            </div>
                        ) : (
                            <div className="relative">
                                <UserRound className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={custQuery}
                                    onFocus={() => setShowCust(true)}
                                    onChange={(e) => setCustQuery(e.target.value)}
                                    placeholder="Consumidor final (buscar cliente…)"
                                    className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                />
                                {showCust && custResults.length > 0 && (
                                    <div className="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                        {custResults.map((c) => (
                                            <button
                                                key={c.id}
                                                onClick={() => {
                                                    setCustomer(c);
                                                    setShowCust(false);
                                                    setCustQuery('');
                                                }}
                                                className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                            >
                                                <span className="block font-medium text-slate-800 dark:text-slate-100">
                                                    {c.razon_social}
                                                </span>
                                                <span className="font-mono text-xs text-slate-400">
                                                    {c.numero_documento}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        {!customer && (
                            <button
                                type="button"
                                onClick={openNewCustomer}
                                className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700"
                            >
                                <UserPlus className="size-3.5" /> Nuevo cliente (RUC/DNI)
                            </button>
                        )}

                        <div className="mt-4 grid grid-cols-2 gap-3">
                            <SelectField
                                label="Comprobante"
                                value={tipo}
                                onChange={(e) => setTipo(e.target.value)}
                                options={[
                                    { value: 'boleta', label: 'Boleta' },
                                    { value: 'factura', label: 'Factura' },
                                    { value: 'nota_venta', label: 'Nota de venta' },
                                ]}
                            />
                            <SelectField
                                label="Método de pago"
                                value={metodo}
                                onChange={(e) => setMetodo(e.target.value)}
                                options={metodos}
                            />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                <dt>Subtotal (gravado)</dt>
                                <dd>{formatCurrency(totals.subtotal, moneda)}</dd>
                            </div>
                            <div className="flex justify-between text-slate-500 dark:text-slate-400">
                                <dt>IGV ({igv}%)</dt>
                                <dd>{formatCurrency(totals.igv, moneda)}</dd>
                            </div>
                            {totals.descuento > 0 && (
                                <div className="flex justify-between text-rose-500">
                                    <dt>Descuento</dt>
                                    <dd>− {formatCurrency(totals.descuento, moneda)}</dd>
                                </div>
                            )}
                            <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                <dt>Total</dt>
                                <dd>{formatCurrency(totals.total, moneda)}</dd>
                            </div>
                            {agentePercepcion && aplicaPercepcion && (
                                <>
                                    <div className="flex justify-between text-amber-600">
                                        <dt>Percepción ({percepcionTasa}%)</dt>
                                        <dd>{formatCurrency(percepcion, moneda)}</dd>
                                    </div>
                                    <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                                        <dt>Total a cobrar</dt>
                                        <dd>{formatCurrency(totalCobrar, moneda)}</dd>
                                    </div>
                                </>
                            )}
                        </dl>

                        {agentePercepcion && (
                            <label className="mt-3 flex cursor-pointer items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                <input
                                    type="checkbox"
                                    checked={aplicaPercepcion}
                                    onChange={(e) => setAplicaPercepcion(e.target.checked)}
                                    className="rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                                />
                                Aplicar percepción del IGV ({percepcionTasa}%)
                            </label>
                        )}

                        {metodo === 'efectivo' && (
                            <div className="mt-4 space-y-2">
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Monto recibido
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={montoRecibido}
                                    onChange={(e) => setMontoRecibido(e.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-right text-lg font-semibold dark:border-slate-700 dark:bg-slate-800"
                                />
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500 dark:text-slate-400">Vuelto</span>
                                    <span className="font-semibold text-emerald-600">
                                        {formatCurrency(vuelto, moneda)}
                                    </span>
                                </div>
                            </div>
                        )}

                        {hayStockInsuficiente && (
                            <p className="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-600 dark:bg-rose-900/20">
                                Hay productos con cantidad mayor al stock disponible.
                            </p>
                        )}

                        {boletaRequiereCliente && (
                            <p className="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                Las boletas mayores a S/ 700 deben identificar al cliente con DNI
                                o RUC. Busca o registra al cliente antes de cobrar.
                            </p>
                        )}

                        <Button
                            onClick={submit}
                            disabled={
                                cart.length === 0 || processing || hayStockInsuficiente || boletaRequiereCliente
                            }
                            className="mt-4 w-full py-3 text-base"
                        >
                            <ShoppingCart className="size-5" /> Cobrar{' '}
                            {formatCurrency(totalCobrar, moneda)}
                        </Button>
                    </div>
                </div>
            </div>

            {/* Alta rápida de cliente (RUC/DNI) */}
            <Modal show={showNewCust} onClose={() => setShowNewCust(false)} maxWidth="lg">
                <form onSubmit={ncGuardar} className="p-6">
                    <h3 className="mb-4 text-base font-semibold text-slate-800 dark:text-slate-100">
                        Nuevo cliente
                    </h3>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <SelectField
                                label="Tipo doc."
                                value={nc.tipo_documento}
                                onChange={(e) => setNcField('tipo_documento', e.target.value)}
                                options={tiposDoc}
                            />
                            <div className="sm:col-span-2">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Número de documento
                                </label>
                                <div className="flex gap-2">
                                    <input
                                        value={nc.numero_documento}
                                        onChange={(e) => setNcField('numero_documento', e.target.value)}
                                        className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    {ncCanLookup && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={ncConsultar}
                                            disabled={ncLoading}
                                        >
                                            {ncLoading ? (
                                                <Loader2 className="size-4 animate-spin" />
                                            ) : (
                                                <SearchIcon className="size-4" />
                                            )}
                                            Consultar
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>
                        <TextField
                            label="Nombre / Razón social"
                            required
                            value={nc.razon_social}
                            onChange={(e) => setNcField('razon_social', e.target.value)}
                        />
                        <TextField
                            label="Dirección"
                            value={nc.direccion}
                            onChange={(e) => setNcField('direccion', e.target.value)}
                        />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TextField
                                label="Teléfono"
                                value={nc.telefono}
                                onChange={(e) => setNcField('telefono', e.target.value)}
                            />
                            <TextField
                                label="Email"
                                type="email"
                                value={nc.email}
                                onChange={(e) => setNcField('email', e.target.value)}
                            />
                        </div>
                        {ncError && <p className="text-xs text-rose-600">{ncError}</p>}
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setShowNewCust(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={ncLoading || !nc.razon_social}>
                            Guardar y usar
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

function round2(n: number): number {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}
