<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CashSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        $mainBranch = $company->branches()->where('is_main', true)->first();

        CashRegister::firstOrCreate(
            ['company_id' => $company->id, 'nombre' => 'Caja Principal'],
            ['company_id' => $company->id, 'branch_id' => $mainBranch?->id],
        );
    }
}
