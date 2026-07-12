import ProductForm, {
    ProductFormData,
} from '@/Components/inventory/ProductForm';
import Button from '@/Components/ui/Button';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, Product, Unit, Warehouse } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { History, Save } from 'lucide-react';
import { FormEvent } from 'react';

export default function Edit({
    product,
    categories,
    units,
    warehouses,
    stockTotal,
}: {
    product: Product;
    categories: Category[];
    units: Unit[];
    warehouses: Warehouse[];
    stockTotal: number;
}) {
    const form = useForm<ProductFormData>({
        codigo: product.codigo,
        codigo_barras: product.codigo_barras ?? '',
        nombre: product.nombre,
        descripcion: product.descripcion ?? '',
        category_id: product.category_id ? String(product.category_id) : '',
        unit_id: product.unit_id ? String(product.unit_id) : '',
        tipo: product.tipo,
        precio_compra: String(product.precio_compra),
        precio_venta: String(product.precio_venta),
        tipo_afectacion_igv: product.tipo_afectacion_igv ?? '10',
        controla_stock: product.controla_stock,
        stock_minimo: String(product.stock_minimo),
        is_active: product.is_active,
        stock_inicial: [],
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(route('inventario.productos.update', product.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Editar: ${product.nombre}`} />
            <form onSubmit={submit} className="space-y-5">
                <PageHeader
                    title={product.nombre}
                    description={`Código ${product.codigo} · Stock actual: ${stockTotal}`}
                    breadcrumbs={[
                        { label: 'Inventario' },
                        {
                            label: 'Productos',
                            href: route('inventario.productos.index'),
                        },
                        { label: 'Editar' },
                    ]}
                    actions={
                        <>
                            <Link
                                href={route('inventario.kardex.show', product.id)}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-600"
                            >
                                <History className="size-4" /> Ver kardex
                            </Link>
                            <Button type="submit" disabled={form.processing}>
                                <Save className="size-4" /> Guardar cambios
                            </Button>
                        </>
                    }
                />

                <ProductForm
                    form={form}
                    categories={categories}
                    units={units}
                    warehouses={warehouses}
                    mode="edit"
                />
            </form>
        </AuthenticatedLayout>
    );
}
