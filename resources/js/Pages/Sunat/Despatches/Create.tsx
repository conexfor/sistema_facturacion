import Card from '@/Components/Card';
import Button from '@/Components/ui/Button';
import { SelectField, TextField } from '@/Components/ui/Form';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';
import { AlertTriangle, ArrowLeftRight, Search, Trash2, Truck, X } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface VentaOpt {
    id: number;
    numero: string;
    dest_tipo_doc: string;
    dest_num_doc: string | null;
    dest_nombre: string;
    llegada_ubigeo: string | null;
    llegada_direccion: string | null;
    items: { product_id: number | null; descripcion: string; cantidad: number; unidad: string }[];
}
interface VehiculoOpt {
    id: number;
    placa: string;
    marca: string | null;
    modelo: string | null;
    capacidad_kg: string | null;
}
interface ConductorOpt {
    id: number;
    tipo_doc: string;
    num_doc: string;
    nombres: string;
    apellidos: string;
    licencia: string;
    licencia_vence: string | null;
}
interface TransportistaOpt {
    id: number;
    ruc: string;
    razon_social: string;
    nro_mtc: string | null;
}
interface ItemLine {
    product_id: number | null;
    descripcion: string;
    cantidad: number;
    unidad: string;
}
interface Prefill {
    transfer_id: number;
    referencia: string;
    cod_traslado: string;
    des_traslado: string;
    dest_tipo_doc: string;
    dest_num_doc: string;
    dest_nombre: string;
    partida_ubigeo: string;
    partida_direccion: string;
    llegada_ubigeo: string;
    llegada_direccion: string;
    items: ItemLine[];
}
interface DespatchForm {
    sale_id: number | null;
    transfer_id: number | null;
    vehicle_id: number | null;
    driver_id: number | null;
    carrier_id: number | null;
    chofer_tipo_doc: string;
    fecha_emision: string;
    fecha_traslado: string;
    cod_traslado: string;
    des_traslado: string;
    mod_traslado: string;
    peso_total: string;
    num_bultos: number;
    dest_tipo_doc: string;
    dest_num_doc: string;
    dest_nombre: string;
    partida_ubigeo: string;
    partida_direccion: string;
    llegada_ubigeo: string;
    llegada_direccion: string;
    transportista_doc: string;
    transportista_nombre: string;
    transportista_mtc: string;
    vehiculo_placa: string;
    chofer_num_doc: string;
    chofer_licencia: string;
    chofer_nombres: string;
    chofer_apellidos: string;
    observacion: string;
    items: ItemLine[];
}

function today() {
    return new Date().toISOString().slice(0, 10);
}

export default function Create({
    motivos,
    modalidades,
    empresa,
    prefill,
    vehiculos,
    conductores,
    transportistas,
}: {
    motivos: Record<string, string>;
    modalidades: Record<string, string>;
    empresa: { ubigeo: string | null; direccion: string | null };
    prefill: Prefill | null;
    vehiculos: VehiculoOpt[];
    conductores: ConductorOpt[];
    transportistas: TransportistaOpt[];
}) {
    const form = useForm<DespatchForm>({
        sale_id: null,
        transfer_id: prefill?.transfer_id ?? null,
        vehicle_id: null,
        driver_id: null,
        carrier_id: null,
        chofer_tipo_doc: '1',
        fecha_emision: today(),
        fecha_traslado: today(),
        cod_traslado: prefill?.cod_traslado ?? '01',
        des_traslado: prefill?.des_traslado ?? motivos['01'] ?? 'Venta',
        mod_traslado: '02',
        peso_total: '1',
        num_bultos: 1,
        dest_tipo_doc: prefill?.dest_tipo_doc ?? '6',
        dest_num_doc: prefill?.dest_num_doc ?? '',
        dest_nombre: prefill?.dest_nombre ?? '',
        partida_ubigeo: prefill?.partida_ubigeo ?? empresa.ubigeo ?? '',
        partida_direccion: prefill?.partida_direccion ?? empresa.direccion ?? '',
        llegada_ubigeo: prefill?.llegada_ubigeo ?? '',
        llegada_direccion: prefill?.llegada_direccion ?? '',
        transportista_doc: '',
        transportista_nombre: '',
        transportista_mtc: '',
        vehiculo_placa: '',
        chofer_num_doc: '',
        chofer_licencia: '',
        chofer_nombres: '',
        chofer_apellidos: '',
        observacion: '',
        items: prefill?.items ?? ([] as ItemLine[]),
    });
    const d = form.data;
    const err = form.errors as Record<string, string>;
    const items = d.items;

    const [venta, setVenta] = useState<VentaOpt | null>(null);
    const [vQuery, setVQuery] = useState('');
    const [vResults, setVResults] = useState<VentaOpt[]>([]);
    const [showV, setShowV] = useState(false);

    useEffect(() => {
        if (!showV) return;
        const t = setTimeout(() => {
            axios
                .get(route('guias.buscar-ventas'), { params: { q: vQuery } })
                .then((r) => setVResults(r.data))
                .catch(() => setVResults([]));
        }, 250);
        return () => clearTimeout(t);
    }, [vQuery, showV]);

    const seleccionarVenta = (v: VentaOpt) => {
        setVenta(v);
        setShowV(false);
        form.setData((prev: any) => ({
            ...prev,
            sale_id: v.id,
            dest_tipo_doc: v.dest_tipo_doc || '6',
            dest_num_doc: v.dest_num_doc ?? '',
            dest_nombre: v.dest_nombre,
            llegada_ubigeo: v.llegada_ubigeo ?? prev.llegada_ubigeo,
            llegada_direccion: v.llegada_direccion ?? prev.llegada_direccion,
            items: v.items,
        }));
    };

    const setItem = (i: number, field: keyof ItemLine, value: string | number) => {
        const next = [...items];
        next[i] = { ...next[i], [field]: value } as ItemLine;
        form.setData('items', next);
    };
    const addItem = () =>
        form.setData('items', [...items, { product_id: null, descripcion: '', cantidad: 1, unidad: 'NIU' }]);
    const removeItem = (i: number) =>
        form.setData('items', items.filter((_, idx) => idx !== i));

    const publico = d.mod_traslado === '01';

    const vehiculo = vehiculos.find((v) => v.id === d.vehicle_id) ?? null;
    const conductor = conductores.find((c) => c.id === d.driver_id) ?? null;
    const excedeCapacidad =
        !publico && vehiculo?.capacidad_kg != null && Number(d.peso_total) > Number(vehiculo.capacidad_kg);
    const licenciaVence = conductor?.licencia_vence?.slice(0, 10) ?? null;
    const licenciaVencida = !publico && !!licenciaVence && licenciaVence < d.fecha_traslado;

    const elegirVehiculo = (id: string) => {
        const v = vehiculos.find((x) => String(x.id) === id);
        form.setData((prev: any) => ({
            ...prev,
            vehicle_id: v?.id ?? null,
            vehiculo_placa: v?.placa ?? '',
        }));
    };
    const elegirConductor = (id: string) => {
        const c = conductores.find((x) => String(x.id) === id);
        form.setData((prev: any) => ({
            ...prev,
            driver_id: c?.id ?? null,
            chofer_tipo_doc: c?.tipo_doc ?? '1',
            chofer_num_doc: c?.num_doc ?? '',
            chofer_licencia: c?.licencia ?? '',
            chofer_nombres: c?.nombres ?? '',
            chofer_apellidos: c?.apellidos ?? '',
        }));
    };
    const elegirTransportista = (id: string) => {
        const t = transportistas.find((x) => String(x.id) === id);
        form.setData((prev: any) => ({
            ...prev,
            carrier_id: t?.id ?? null,
            transportista_doc: t?.ruc ?? '',
            transportista_nombre: t?.razon_social ?? '',
            transportista_mtc: t?.nro_mtc ?? '',
        }));
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('guias.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Nueva guía de remisión" />
            <form onSubmit={submit} className="space-y-5">
                <PageHeader
                    title="Nueva guía de remisión"
                    breadcrumbs={[
                        { label: 'Facturación SUNAT' },
                        { label: 'Guías', href: route('guias.index') },
                        { label: 'Nueva' },
                    ]}
                    actions={
                        <Button type="submit" disabled={form.processing}>
                            <Truck className="size-4" /> Emitir guía
                        </Button>
                    }
                />

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        {prefill ? (
                            <Card title="Transferencia de referencia">
                                <div className="flex items-center gap-2 rounded-lg bg-brand-50 px-3 py-2 text-sm text-slate-800 dark:bg-brand-900/20 dark:text-slate-100">
                                    <ArrowLeftRight className="size-4 text-brand-600" />
                                    {prefill.referencia}
                                </div>
                            </Card>
                        ) : (
                        <Card title="Comprobante de referencia (opcional)">
                            {venta ? (
                                <div className="flex items-center justify-between gap-2 rounded-lg bg-brand-50 px-3 py-2 dark:bg-brand-900/20">
                                    <span className="font-mono text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {venta.numero}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setVenta(null);
                                            form.setData('sale_id', null);
                                        }}
                                        className="rounded p-1 text-slate-400 hover:text-rose-600"
                                    >
                                        <X className="size-4" />
                                    </button>
                                </div>
                            ) : (
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        value={vQuery}
                                        onFocus={() => setShowV(true)}
                                        onChange={(e) => setVQuery(e.target.value)}
                                        placeholder="Buscar venta para prellenar destinatario e ítems…"
                                        className="w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800"
                                    />
                                    {showV && vResults.length > 0 && (
                                        <div className="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                                            {vResults.map((v) => (
                                                <button
                                                    key={v.id}
                                                    type="button"
                                                    onClick={() => seleccionarVenta(v)}
                                                    className="block w-full px-3 py-2 text-left text-sm hover:bg-brand-50 dark:hover:bg-slate-700"
                                                >
                                                    <span className="font-mono font-medium text-slate-800 dark:text-slate-100">
                                                        {v.numero}
                                                    </span>
                                                    <span className="ml-2 text-xs text-slate-400">{v.dest_nombre}</span>
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </Card>
                        )}

                        <Card title="Datos del traslado">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField
                                    label="Motivo"
                                    value={d.cod_traslado}
                                    onChange={(e) =>
                                        form.setData((prev: any) => ({
                                            ...prev,
                                            cod_traslado: e.target.value,
                                            des_traslado: motivos[e.target.value] ?? prev.des_traslado,
                                        }))
                                    }
                                    options={Object.entries(motivos).map(([value, label]) => ({ value, label }))}
                                />
                                <TextField
                                    label="Descripción del motivo"
                                    value={d.des_traslado}
                                    onChange={(e) => form.setData('des_traslado', e.target.value)}
                                    error={err.des_traslado}
                                />
                                <TextField
                                    label="Fecha de emisión"
                                    type="date"
                                    value={d.fecha_emision}
                                    onChange={(e) => form.setData('fecha_emision', e.target.value)}
                                />
                                <TextField
                                    label="Fecha de traslado"
                                    type="date"
                                    value={d.fecha_traslado}
                                    onChange={(e) => form.setData('fecha_traslado', e.target.value)}
                                />
                                <TextField
                                    label="Peso total (KG)"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    value={d.peso_total}
                                    onChange={(e) => form.setData('peso_total', e.target.value)}
                                    error={err.peso_total}
                                />
                                <TextField
                                    label="N° de bultos"
                                    type="number"
                                    min="1"
                                    value={d.num_bultos}
                                    onChange={(e) => form.setData('num_bultos', Number(e.target.value))}
                                />
                            </div>
                        </Card>

                        <Card title="Puntos de partida y llegada">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TextField
                                    label="Ubigeo partida"
                                    value={d.partida_ubigeo}
                                    onChange={(e) => form.setData('partida_ubigeo', e.target.value)}
                                    error={err.partida_ubigeo}
                                    hint="6 dígitos"
                                />
                                <TextField
                                    label="Dirección de partida"
                                    value={d.partida_direccion}
                                    onChange={(e) => form.setData('partida_direccion', e.target.value)}
                                    error={err.partida_direccion}
                                />
                                <TextField
                                    label="Ubigeo llegada"
                                    value={d.llegada_ubigeo}
                                    onChange={(e) => form.setData('llegada_ubigeo', e.target.value)}
                                    error={err.llegada_ubigeo}
                                />
                                <TextField
                                    label="Dirección de llegada"
                                    value={d.llegada_direccion}
                                    onChange={(e) => form.setData('llegada_direccion', e.target.value)}
                                    error={err.llegada_direccion}
                                />
                            </div>
                        </Card>

                        <Card
                            title="Productos a trasladar"
                            action={
                                <button
                                    type="button"
                                    onClick={addItem}
                                    className="text-sm font-medium text-brand-600 hover:underline"
                                >
                                    + Agregar
                                </button>
                            }
                        >
                            {items.length === 0 ? (
                                <p className="py-4 text-center text-sm text-slate-400">
                                    Selecciona una venta o agrega ítems manualmente.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {items.map((it, i) => (
                                        <div key={i} className="grid grid-cols-12 items-center gap-2">
                                            <input
                                                value={it.descripcion}
                                                onChange={(e) => setItem(i, 'descripcion', e.target.value)}
                                                placeholder="Descripción"
                                                className="col-span-7 rounded-lg border-slate-300 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800"
                                            />
                                            <input
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                value={it.cantidad}
                                                onChange={(e) => setItem(i, 'cantidad', Number(e.target.value))}
                                                className="col-span-2 rounded-lg border-slate-300 py-1.5 text-center text-sm dark:border-slate-700 dark:bg-slate-800"
                                            />
                                            <input
                                                value={it.unidad}
                                                onChange={(e) => setItem(i, 'unidad', e.target.value)}
                                                className="col-span-2 rounded-lg border-slate-300 py-1.5 text-center text-sm dark:border-slate-700 dark:bg-slate-800"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeItem(i)}
                                                className="col-span-1 flex justify-center text-slate-400 hover:text-rose-600"
                                            >
                                                <Trash2 className="size-4" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                            {err.items && <p className="mt-2 text-xs text-rose-600">{err.items}</p>}
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card title="Destinatario">
                            <div className="space-y-3">
                                <SelectField
                                    label="Tipo doc."
                                    value={d.dest_tipo_doc}
                                    onChange={(e) => form.setData('dest_tipo_doc', e.target.value)}
                                    options={[
                                        { value: '6', label: 'RUC' },
                                        { value: '1', label: 'DNI' },
                                        { value: '0', label: 'Otros' },
                                    ]}
                                />
                                <TextField
                                    label="N° documento"
                                    value={d.dest_num_doc}
                                    onChange={(e) => form.setData('dest_num_doc', e.target.value)}
                                    error={err.dest_num_doc}
                                />
                                <TextField
                                    label="Nombre / Razón social"
                                    value={d.dest_nombre}
                                    onChange={(e) => form.setData('dest_nombre', e.target.value)}
                                    error={err.dest_nombre}
                                />
                            </div>
                        </Card>

                        <Card title="Transporte">
                            <SelectField
                                label="Modalidad"
                                value={d.mod_traslado}
                                onChange={(e) => form.setData('mod_traslado', e.target.value)}
                                options={Object.entries(modalidades).map(([value, label]) => ({ value, label }))}
                            />
                            {publico ? (
                                <div className="mt-3 space-y-3">
                                    <SelectField
                                        label="Transportista"
                                        placeholder="— Ingresar manualmente —"
                                        value={d.carrier_id ?? ''}
                                        onChange={(e) => elegirTransportista(e.target.value)}
                                        error={err.carrier_id}
                                        options={transportistas.map((t) => ({
                                            value: t.id,
                                            label: `${t.razon_social} (${t.ruc})`,
                                        }))}
                                    />
                                    <TextField
                                        label="RUC transportista"
                                        value={d.transportista_doc}
                                        onChange={(e) => form.setData('transportista_doc', e.target.value)}
                                        error={err.transportista_doc}
                                        disabled={!!d.carrier_id}
                                    />
                                    <TextField
                                        label="Razón social transportista"
                                        value={d.transportista_nombre}
                                        onChange={(e) => form.setData('transportista_nombre', e.target.value)}
                                        error={err.transportista_nombre}
                                        disabled={!!d.carrier_id}
                                    />
                                    <TextField
                                        label="N° registro MTC"
                                        value={d.transportista_mtc}
                                        onChange={(e) => form.setData('transportista_mtc', e.target.value)}
                                        disabled={!!d.carrier_id}
                                    />
                                </div>
                            ) : (
                                <div className="mt-3 space-y-3">
                                    <SelectField
                                        label="Vehículo"
                                        placeholder="— Ingresar manualmente —"
                                        value={d.vehicle_id ?? ''}
                                        onChange={(e) => elegirVehiculo(e.target.value)}
                                        error={err.vehicle_id}
                                        options={vehiculos.map((v) => ({
                                            value: v.id,
                                            label: [v.placa, v.marca, v.modelo].filter(Boolean).join(' · '),
                                        }))}
                                    />
                                    <TextField
                                        label="Placa del vehículo"
                                        value={d.vehiculo_placa}
                                        onChange={(e) => form.setData('vehiculo_placa', e.target.value)}
                                        error={err.vehiculo_placa}
                                        disabled={!!d.vehicle_id}
                                    />
                                    {excedeCapacidad && (
                                        <p className="flex items-center gap-1.5 text-xs text-amber-600">
                                            <AlertTriangle className="size-3.5" />
                                            El peso supera la capacidad del vehículo ({Number(vehiculo?.capacidad_kg)} kg).
                                        </p>
                                    )}
                                    <SelectField
                                        label="Conductor"
                                        placeholder="— Ingresar manualmente —"
                                        value={d.driver_id ?? ''}
                                        onChange={(e) => elegirConductor(e.target.value)}
                                        error={err.driver_id}
                                        options={conductores.map((c) => ({
                                            value: c.id,
                                            label: `${c.apellidos}, ${c.nombres} (${c.num_doc})`,
                                        }))}
                                    />
                                    {licenciaVencida && licenciaVence && (
                                        <p className="flex items-center gap-1.5 text-xs text-rose-600">
                                            <AlertTriangle className="size-3.5" />
                                            Licencia vencida el {licenciaVence.split('-').reverse().join('/')}.
                                        </p>
                                    )}
                                    <TextField
                                        label="DNI del conductor"
                                        value={d.chofer_num_doc}
                                        onChange={(e) => form.setData('chofer_num_doc', e.target.value)}
                                        error={err.chofer_num_doc}
                                        disabled={!!d.driver_id}
                                    />
                                    <TextField
                                        label="Licencia"
                                        value={d.chofer_licencia}
                                        onChange={(e) => form.setData('chofer_licencia', e.target.value)}
                                        error={err.chofer_licencia}
                                        disabled={!!d.driver_id}
                                    />
                                    <TextField
                                        label="Nombres del conductor"
                                        value={d.chofer_nombres}
                                        onChange={(e) => form.setData('chofer_nombres', e.target.value)}
                                        error={err.chofer_nombres}
                                        disabled={!!d.driver_id}
                                    />
                                    <TextField
                                        label="Apellidos del conductor"
                                        value={d.chofer_apellidos}
                                        onChange={(e) => form.setData('chofer_apellidos', e.target.value)}
                                        disabled={!!d.driver_id}
                                    />
                                </div>
                            )}
                        </Card>
                    </div>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
