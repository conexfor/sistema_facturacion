<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_real_metrics(): void
    {
        $this->seed(TestSeeder::class);
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
                ->where('stats.0.key', 'ventas_hoy')
                ->where('stats.0.value', 60)
                ->where('stats.2.key', 'facturas_mes')
                ->where('stats.2.value', 1)
                ->where('stats.3.key', 'clientes_activos')
                ->where('stats.3.value', 1)
                ->has('salesByMonth.categories', 12)
                ->has('salesByMonth.ingresos', 12)
                ->has('salesByMonth.meta', 12)
                ->where('salesByMonth.ingresos.11', 60)
                ->has('salesByDay', 7)
                ->where('salesByDay.0.dia', 'Lun')
                ->has('topProducts', 1)
                ->where('recentSales.0.total', 60)
                ->where('recentSales.0.tipo', 'boleta')
                ->where('recentSales.0.estado', 'pagada')
                ->has('recentSales', 1));
    }

    public function test_pending_sale_older_than_30_days_is_shown_as_overdue(): void
    {
        $this->seed(TestSeeder::class);
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

        Sale::query()->update(['estado' => 'pendiente', 'fecha' => now()->subDays(31)]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('recentSales.0.estado', 'vencida'));
    }

    public function test_dashboard_works_with_no_data(): void
    {
        // Empresa y usuario, pero sin ventas/compras
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::where('email', 'admin@erp.test')->firstOrFail();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
