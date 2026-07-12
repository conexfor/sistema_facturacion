<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        $cid = $company->id;
        $inventory = app(InventoryService::class);

        // Unidades de medida (catálogo 03 SUNAT)
        $units = [
            ['codigo' => 'NIU', 'nombre' => 'Unidad', 'abreviatura' => 'UND'],
            ['codigo' => 'KGM', 'nombre' => 'Kilogramo', 'abreviatura' => 'KG'],
            ['codigo' => 'LTR', 'nombre' => 'Litro', 'abreviatura' => 'L'],
            ['codigo' => 'BX', 'nombre' => 'Caja', 'abreviatura' => 'CJA'],
            ['codigo' => 'PK', 'nombre' => 'Paquete', 'abreviatura' => 'PQT'],
            ['codigo' => 'DZN', 'nombre' => 'Docena', 'abreviatura' => 'DOC'],
        ];
        foreach ($units as $u) {
            Unit::firstOrCreate(['company_id' => $cid, 'codigo' => $u['codigo']], $u + ['company_id' => $cid]);
        }
        $und = Unit::where('company_id', $cid)->where('codigo', 'NIU')->first();

        // Categorías
        $catNames = ['Abarrotes', 'Bebidas', 'Limpieza', 'Cuidado personal', 'Lácteos'];
        $cats = [];
        foreach ($catNames as $n) {
            $cats[$n] = Category::firstOrCreate(
                ['company_id' => $cid, 'nombre' => $n],
                ['company_id' => $cid],
            );
        }

        // Almacenes
        $mainBranch = $company->branches()->where('is_main', true)->first();
        $alm = Warehouse::firstOrCreate(
            ['company_id' => $cid, 'codigo' => 'ALM01'],
            [
                'company_id' => $cid,
                'branch_id' => $mainBranch?->id,
                'nombre' => 'Almacén Principal',
                'is_main' => true,
            ],
        );
        Warehouse::firstOrCreate(
            ['company_id' => $cid, 'codigo' => 'ALM02'],
            ['company_id' => $cid, 'nombre' => 'Almacén Secundario'],
        );

        // Productos con stock inicial
        $productos = [
            ['codigo' => 'P0001', 'nombre' => 'Arroz Superior 5kg', 'cat' => 'Abarrotes', 'compra' => 22.00, 'venta' => 30.00, 'min' => 20, 'stock' => 120],
            ['codigo' => 'P0002', 'nombre' => 'Aceite Vegetal 1L', 'cat' => 'Abarrotes', 'compra' => 12.50, 'venta' => 18.00, 'min' => 15, 'stock' => 80],
            ['codigo' => 'P0003', 'nombre' => 'Gaseosa 3L', 'cat' => 'Bebidas', 'compra' => 14.00, 'venta' => 23.00, 'min' => 24, 'stock' => 60],
            ['codigo' => 'P0004', 'nombre' => 'Detergente 2kg', 'cat' => 'Limpieza', 'compra' => 13.00, 'venta' => 20.00, 'min' => 10, 'stock' => 8],
            ['codigo' => 'P0005', 'nombre' => 'Leche Evaporada 400g', 'cat' => 'Lácteos', 'compra' => 9.00, 'venta' => 12.50, 'min' => 30, 'stock' => 150],
            ['codigo' => 'P0006', 'nombre' => 'Jabón de tocador', 'cat' => 'Cuidado personal', 'compra' => 2.20, 'venta' => 3.50, 'min' => 40, 'stock' => 25],
            ['codigo' => 'P0007', 'nombre' => 'Agua mineral 625ml', 'cat' => 'Bebidas', 'compra' => 0.90, 'venta' => 1.50, 'min' => 50, 'stock' => 200],
            ['codigo' => 'P0008', 'nombre' => 'Fideos spaghetti 500g', 'cat' => 'Abarrotes', 'compra' => 2.80, 'venta' => 4.20, 'min' => 30, 'stock' => 90],
        ];

        foreach ($productos as $p) {
            $product = Product::firstOrCreate(
                ['company_id' => $cid, 'codigo' => $p['codigo']],
                [
                    'company_id' => $cid,
                    'category_id' => $cats[$p['cat']]->id,
                    'unit_id' => $und?->id,
                    'nombre' => $p['nombre'],
                    'precio_compra' => $p['compra'],
                    'precio_venta' => $p['venta'],
                    'stock_minimo' => $p['min'],
                ],
            );

            // Solo cargar stock inicial si aún no tiene movimientos
            if ($product->movements()->count() === 0) {
                $inventory->entrada($product, $alm, $p['stock'], $p['compra'], 'Stock inicial', 'Seed');
            }
        }
    }
}
