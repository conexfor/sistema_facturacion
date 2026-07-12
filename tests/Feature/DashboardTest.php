<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_real_metrics(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@erp.test')->firstOrFail();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 60,
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('stats', 4)
                ->has('salesByMonth')
                ->has('topProducts', 1)
                ->where('recentSales.0.total', 60)
                ->has('recentSales', 1));
    }

    public function test_dashboard_works_with_no_data(): void
    {
        // Empresa y usuario, pero sin ventas/compras
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::where('email', 'admin@erp.test')->firstOrFail();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
