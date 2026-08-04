<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['ruc' => '20123456789'],
            [
                'razon_social' => 'EMPRESA DEMO COMERCIAL S.A.C.',
                'nombre_comercial' => 'Demo Comercial',
                'direccion' => 'Av. Principal 123, Lima',
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'LIMA',
                'telefono' => '01-7000000',
                'email' => 'ventas@democomercial.pe',
                'moneda' => 'PEN',
                'igv_porcentaje' => 18.00,
                'sunat_modo' => 'beta',
            ],
        );

        $mainBranch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'codigo' => '0000'],
            [
                'nombre' => 'Tienda Principal',
                'direccion' => 'Av. Principal 123, Lima',
                'telefono' => '01-7000000',
                'is_main' => true,
            ],
        );

        Branch::firstOrCreate(
            ['company_id' => $company->id, 'codigo' => '0001'],
            [
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Av. Túpac Amaru 456, Comas',
                'is_main' => false,
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@erp.test'],
            [
                'company_id' => $company->id,
                'branch_id' => $mainBranch->id,
                'name' => 'Administrador',
                'position' => 'Gerente General',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $this->call(RolesSeeder::class);
        $admin->syncRoles('Administrador');

        $this->call(InventorySeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(CashSeeder::class);
        $this->call(DemoDataSeeder::class);
    }
}
