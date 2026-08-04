<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        $cid = $company->id;

        $clientes = [
            [
                'tipo_documento' => '6',
                'numero_documento' => '20512345678',
                'razon_social' => 'COMERCIAL RAMÍREZ S.A.C.',
                'direccion' => 'Av. Industrial 456, Lima',
                'email' => 'ventas@ramirez.pe',
                'telefono' => '01-5551234',
            ],
            [
                'tipo_documento' => '6',
                'numero_documento' => '20487654321',
                'razon_social' => 'DISTRIBUIDORA SUR E.I.R.L.',
                'direccion' => 'Jr. Comercio 789, Arequipa',
            ],
            [
                'tipo_documento' => '1',
                'numero_documento' => '45678912',
                'razon_social' => 'María López Gutiérrez',
                'telefono' => '987654321',
            ],
            [
                'tipo_documento' => '1',
                'numero_documento' => '70123456',
                'razon_social' => 'Carlos Fernández Ríos',
            ],
            [
                'tipo_documento' => '1',
                'numero_documento' => '10234567',
                'razon_social' => 'Pedro Alvarado Reyes',
                'telefono' => '912345678',
            ],
        ];

        foreach ($clientes as $c) {
            Customer::firstOrCreate(
                [
                    'company_id' => $cid,
                    'tipo_documento' => $c['tipo_documento'],
                    'numero_documento' => $c['numero_documento'],
                ],
                $c + ['company_id' => $cid],
            );
        }
    }
}
