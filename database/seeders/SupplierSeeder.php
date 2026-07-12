<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        $cid = $company->id;

        $proveedores = [
            [
                'tipo_documento' => '6',
                'numero_documento' => '20100050678',
                'razon_social' => 'DISTRIBUIDORA MAYORISTA ANDINA S.A.C.',
                'nombre_comercial' => 'Mayorista Andina',
                'direccion' => 'Av. Argentina 2300, Cercado de Lima',
                'contacto' => 'Jorge Quispe',
                'telefono' => '01-3334455',
                'email' => 'ventas@mayoristaandina.pe',
            ],
            [
                'tipo_documento' => '6',
                'numero_documento' => '20557788990',
                'razon_social' => 'IMPORTACIONES DEL PACÍFICO E.I.R.L.',
                'direccion' => 'Jr. Cusco 145, Lima',
                'contacto' => 'Ana Torres',
                'telefono' => '01-7778899',
            ],
            [
                'tipo_documento' => '6',
                'numero_documento' => '20602233445',
                'razon_social' => 'COMERCIALIZADORA LIMA NORTE S.A.',
                'direccion' => 'Av. Túpac Amaru 1800, Independencia',
            ],
        ];

        foreach ($proveedores as $p) {
            Supplier::firstOrCreate(
                [
                    'company_id' => $cid,
                    'tipo_documento' => $p['tipo_documento'],
                    'numero_documento' => $p['numero_documento'],
                ],
                $p + ['company_id' => $cid],
            );
        }
    }
}
