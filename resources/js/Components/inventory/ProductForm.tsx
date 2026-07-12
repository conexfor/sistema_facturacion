import Card from '@/Components/Card';
import { SelectField, Switch, TextareaField, TextField } from '@/Components/ui/Form';
import { Category, Unit, Warehouse } from '@/types/models';
import { type InertiaFormProps } from '@inertiajs/react';

export interface ProductFormData {
    codigo: string;
    codigo_barras: string;
    nombre: string;
    descripcion: string;
    category_id: string;
    unit_id: string;
    tipo: 'bien' | 'servicio';
    precio_compra: string;
    precio_venta: string;
    tipo_afectacion_igv: string;
    controla_stock: boolean;
    stock_minimo: string;
    is_active: boolean;
    stock_inicial: { warehouse_id: number; cantidad: string; costo: string }[];
}

export default function ProductForm({
    form,
    categories,
    units,
    warehouses,
    mode,
}: {
    form: InertiaFormProps<ProductFormData>;
    categories: Category[];
    units: Unit[];
    warehouses: Warehouse[];
    mode: 'create' | 'edit';
}) {
    const { data, setData, errors } = form;

    const setStockRow = (i: number, field: 'cantidad' | 'costo', value: string) => {
        const next = [...data.stock_inicial];
        next[i] = { ...next[i], [field]: value };
        setData('stock_inicial', next);
    };

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div className="space-y-4 lg:col-span-2">
                <Card title="Información general">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextField
                            label="Código / SKU"
                            required
                            value={data.codigo}
                            onChange={(e) => setData('codigo', e.target.value)}
                            error={errors.codigo}
                        />
                        <TextField
                            label="Código de barras"
                            value={data.codigo_barras}
                            onChange={(e) => setData('codigo_barras', e.target.value)}
                            error={errors.codigo_barras}
                        />
                        <div className="sm:col-span-2">
                            <TextField
                                label="Nombre"
                                required
                                value={data.nombre}
                                onChange={(e) => setData('nombre', e.target.value)}
                                error={errors.nombre}
                            />
                        </div>
                        <SelectField
                            label="Categoría"
                            placeholder="— Sin categoría —"
                            value={data.category_id}
                            onChange={(e) => setData('category_id', e.target.value)}
                            error={errors.category_id}
                            options={categories.map((c) => ({
                                value: c.id,
                                label: c.nombre,
                            }))}
                        />
                        <SelectField
                            label="Unidad de medida"
                            placeholder="— Seleccionar —"
                            value={data.unit_id}
                            onChange={(e) => setData('unit_id', e.target.value)}
                            error={errors.unit_id}
                            options={units.map((u) => ({
                                value: u.id,
                                label: `${u.nombre} (${u.abreviatura})`,
                            }))}
                        />
                        <div className="sm:col-span-2">
                            <TextareaField
                                label="Descripción"
                                rows={3}
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                                error={errors.descripcion}
                            />
                        </div>
                    </div>
                </Card>

                <Card title="Precios">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextField
                            label="Precio de compra (S/)"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={data.precio_compra}
                            onChange={(e) => setData('precio_compra', e.target.value)}
                            error={errors.precio_compra}
                        />
                        <TextField
                            label="Precio de venta (S/)"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={data.precio_venta}
                            onChange={(e) => setData('precio_venta', e.target.value)}
                            error={errors.precio_venta}
                        />
                    </div>
                </Card>

                {mode === 'create' && data.controla_stock && warehouses.length > 0 && (
                    <Card
                        title="Stock inicial"
                        subtitle="Opcional. Genera un movimiento de entrada por almacén."
                    >
                        <div className="space-y-3">
                            {warehouses.map((w, i) => (
                                <div
                                    key={w.id}
                                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-3"
                                >
                                    <div className="text-sm font-medium text-slate-600 dark:text-slate-300">
                                        {w.nombre}
                                    </div>
                                    <TextField
                                        label="Cantidad"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        value={data.stock_inicial[i]?.cantidad ?? ''}
                                        onChange={(e) =>
                                            setStockRow(i, 'cantidad', e.target.value)
                                        }
                                    />
                                    <TextField
                                        label="Costo unit. (S/)"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.stock_inicial[i]?.costo ?? ''}
                                        onChange={(e) =>
                                            setStockRow(i, 'costo', e.target.value)
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                    </Card>
                )}
            </div>

            <div className="space-y-4">
                <Card title="Configuración">
                    <div className="space-y-3">
                        <SelectField
                            label="Tipo"
                            value={data.tipo}
                            onChange={(e) =>
                                setData('tipo', e.target.value as 'bien' | 'servicio')
                            }
                            options={[
                                { value: 'bien', label: 'Bien (controla stock)' },
                                { value: 'servicio', label: 'Servicio' },
                            ]}
                        />
                        <TextField
                            label="Stock mínimo"
                            type="number"
                            step="0.0001"
                            min="0"
                            value={data.stock_minimo}
                            onChange={(e) => setData('stock_minimo', e.target.value)}
                            error={errors.stock_minimo}
                            hint="Para alertas de reposición"
                        />
                        <SelectField
                            label="Afectación IGV"
                            value={data.tipo_afectacion_igv}
                            onChange={(e) =>
                                setData('tipo_afectacion_igv', e.target.value)
                            }
                            options={[
                                { value: '10', label: 'Gravado (IGV 18%)' },
                                { value: '20', label: 'Exonerado' },
                                { value: '30', label: 'Inafecto' },
                            ]}
                        />
                        <Switch
                            label="Controla stock"
                            description="Desactivar para servicios"
                            checked={data.controla_stock}
                            onChange={(v) => setData('controla_stock', v)}
                        />
                        <Switch
                            label="Activo"
                            checked={data.is_active}
                            onChange={(v) => setData('is_active', v)}
                        />
                    </div>
                </Card>
            </div>
        </div>
    );
}
