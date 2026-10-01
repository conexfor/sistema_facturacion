<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Empresa, sucursales, usuario admin y catálogo base, sin los datos
 * transaccionales de DemoDataSeeder (ventas, compras, cajas abiertas, etc.)
 * Los tests necesitan una base predecible; los montos y estados de
 * DemoDataSeeder están pensados para verse bien en una demo, no para que un
 * test los pueda predecir.
 */
class TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BaseSeeder::class);
    }
}
