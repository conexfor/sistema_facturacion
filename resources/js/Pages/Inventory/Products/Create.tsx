import ProductForm, {
    ProductFormData,
} from '@/Components/inventory/ProductForm';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, Unit, Warehouse } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent } from 'react';

export default function Create({
    categories,
    units,
    warehouses,
}: {
    categories: Category[];
    units: Unit[];
    warehouses: Warehouse[];
}) {
    const form = useForm<ProductFormData>({
        codigo: '',
        codigo_barras: '',
        nombre: '',
        descripcion: '',
        category_id: '',
        unit_id: '',
        tipo: 'bien',
        precio_compra: '0',
        precio_venta: '0',
        tipo_afectacion_igv: '10',
        controla_stock: true,
        stock_minimo: '0',
        is_active: true,
        stock_inicial: warehouses.map((w) => ({
            warehouse_id: w.id,
            cantidad: '',
            costo: '',
        })),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(route('inventario.productos.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Nuevo producto" />
            <form onSubmit={submit} className="space-y-5">
                <PageHeader
                    title="Nuevo producto"
                    breadcrumbs={[
                        { label: 'Inventario' },
                        {
                            label: 'Productos',
                            href: route('inventario.productos.index'),
                        },
                        { label: 'Nuevo' },
                    ]}
                    actions={
                        <>
                            <Link
                                href={route('inventario.productos.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                            >
                                Cancelar
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                <Save className="size-4" /> Guardar producto
                            </Button>
                        </>
                    }
                />

                <ProductForm
                    form={form}
                    categories={categories}
                    units={units}
                    warehouses={warehouses}
                    mode="create"
                />
            </form>
        </AuthenticatedLayout>
    );
}
