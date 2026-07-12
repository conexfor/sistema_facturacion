import Card from '@/Components/Card';
import Button from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeftRight, Search, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

interface WarehouseOpt {
    id: number;
    nombre: string;
}
interface ProductResult {
    id: number;
    codigo: string;
    nombre: string;
    stock: number | null;
    unit?: { abreviatura: string } | null;
}
interface Line {
    product_id: number;
    codigo: string;
    nombre: string;
    cantidad: number;
    stock: number;
    unidad: string;
}

export default function Create({ warehouses }: { warehouses: WarehouseOpt[] }) {
    const [originId, setOriginId] = useState(warehouses[0] ? String(warehouses[0].id) : '');
    const [destId, setDestId] = useState(warehouses[1] ? String(warehouses[1].id) : '');
    const [observacion, setObservacion] = useState('');
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ProductResult[]>([]);
    const [lines, setLines] = useState<Line[]>([]);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!originId) return;
        const t = setTimeout(() => {
            axios
                .get(route('inventario.transferencias.buscar-productos'), {
                    params: { q: query, warehouse_id: originId },
                })
                .then((r) => setResults(r.data))
                .catch(() => setResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [query, originId]);

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
                    stock: Number(p.stock ?? 0),
                    unidad: p.unit?.abreviatura ?? '',
                },
            ];
        });
        setQuery('');
        setResults([]);
    };

    const updateLine = (i: number, value: number) =>
        setLines((prev) => {
            const next = [...prev];
            next[i] = { ...next[i], cantidad: value };
            return next;
        });

    const removeLine = (i: number) => setLines((prev) => prev.filter((_, idx) => idx !== i));

    const hayExceso = lines.some((l) => l.cantidad > l.stock);

    const submit = () => {
        setError(null);
        if (originId === destId) {
            setError('El almacén de origen y destino deben ser distintos.');
            return;
        }
        if (lines.length === 0 || processing) return;
        setProcessing(true);
        router.post(
            route('inventario.transferencias.store'),
            {
                origin_warehouse_id: Number(originId),
                dest_warehouse_id: Number(destId),
                observacion: observacion || null,
                items: lines.map((l) => ({ product_id: l.product_id, cantidad: l.cantidad })),
            },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Nueva transferencia" />
            <PageHeader
                title="Nueva transferencia"
                description="Mueve stock entre almacenes"
                breadcrumbs={[
                    { label: 'Inventario' },
                    { label: 'Transferencias', href: route('inventario.transferencias.index') },
                    { label: 'Nueva' },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    <Card title="Almacenes">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <SelectField
                                label="Desde (origen)"
                                value={originId}
                                onChange={(e) => setOriginId(e.target.value)}
                                options={warehouses.map((w) => ({ value: w.id, label: w.nombre }))}
                            />
                            <SelectField
                                label="Hacia (destino)"
                                value={destId}
                                onChange={(e) => setDestId(e.target.value)}
                                options={warehouses.map((w) => ({ value: w.id, label: w.nombre }))}
                            />
                        </div>
                        {error && <p className="mt-2 text-sm text-rose-600">{error}</p>}
                    </Card>

                    <Card title="Productos">
                        <div className="relative mb-4">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Buscar producto a transferir…"
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
                                                <span className="font-mono text-xs text-slate-400">{p.codigo}</span>
                                            </span>
                                            <span className="shrink-0 text-xs text-slate-400">
                                                stock {formatNumber(Number(p.stock ?? 0))}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {lines.length === 0 ? (
                            <p className="py-8 text-center text-sm text-slate-400">
                                Agrega productos para transferir
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                                            <th className="p-2 font-semibold">Producto</th>
                                            <th className="p-2 text-right font-semibold">Stock origen</th>
                                            <th className="p-2 text-center font-semibold">Cantidad</th>
                                            <th className="p-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {lines.map((l, i) => {
                                            const exceso = l.cantidad > l.stock;
                                            return (
                                                <tr key={l.product_id}>
                                                    <td className="p-2">
                                                        <div className="font-medium text-slate-800 dark:text-slate-100">
                                                            {l.nombre}
                                                        </div>
                                                        <div className="font-mono text-xs text-slate-400">{l.codigo}</div>
                                                    </td>
                                                    <td className="p-2 text-right text-slate-500">
                                                        {formatNumber(l.stock)} {l.unidad}
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.0001"
                                                            value={l.cantidad}
                                                            onChange={(e) => updateLine(i, Number(e.target.value))}
                                                            className={`w-24 rounded-lg border-slate-300 py-1 text-center text-sm dark:border-slate-700 dark:bg-slate-800 ${
                                                                exceso ? 'border-rose-400 text-rose-600' : ''
                                                            }`}
                                                        />
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

                <div className="space-y-4">
                    <Card title="Confirmar">
                        <TextField
                            label="Observación"
                            value={observacion}
                            onChange={(e) => setObservacion(e.target.value)}
                        />
                        {hayExceso && (
                            <p className="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-600 dark:bg-rose-900/20">
                                Hay cantidades mayores al stock disponible en el origen.
                            </p>
                        )}
                        <Button
                            onClick={submit}
                            disabled={lines.length === 0 || processing || hayExceso}
                            className="mt-4 w-full py-3 text-base"
                        >
                            <ArrowLeftRight className="size-5" /> Registrar transferencia
                        </Button>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
