<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(TestSeeder::class);

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_inventory_pages_render(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        $this->get(route('inventario.productos.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Inventory/Products/Index')
                ->has('products.data'));

        $this->get(route('inventario.categorias.index'))->assertOk();
        $this->get(route('inventario.almacenes.index'))->assertOk();
        $this->get(route('inventario.kardex.index'))->assertOk();
        $this->get(route('inventario.ajustes.index'))->assertOk();
        $this->get(route('inventario.productos.create'))->assertOk();

        $product = Product::firstOrFail();
        $this->get(route('inventario.productos.edit', $product))->assertOk();
        $this->get(route('inventario.kardex.show', $product))->assertOk();
    }

    public function test_create_product_with_initial_stock(): void
    {
        $user = $this->admin();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)
            ->post(route('inventario.productos.store'), [
                'codigo' => 'TST01',
                'nombre' => 'Producto de prueba',
                'tipo' => 'bien',
                'precio_compra' => 10,
                'precio_venta' => 15,
                'stock_minimo' => 5,
                'afecto_igv' => true,
                'controla_stock' => true,
                'is_active' => true,
                'stock_inicial' => [
                    ['warehouse_id' => $warehouse->id, 'cantidad' => 40, 'costo' => 10],
                ],
            ])
            ->assertRedirect(route('inventario.productos.index'));

        $product = Product::where('codigo', 'TST01')->firstOrFail();

        $this->assertEquals(40, (float) $product->stocks()->sum('cantidad'));
        $this->assertEquals(10, (float) $product->fresh()->costo_promedio);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo' => 'entrada',
            'saldo_cantidad' => 40,
        ]);
    }

    public function test_stock_adjustment_creates_movement(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        // P0001 arranca con 120 en ALM01; ajustamos a 50 => salida de 70
        $this->actingAs($user)
            ->post(route('inventario.ajustes.store'), [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'cantidad_objetivo' => 50,
                'motivo' => 'Inventario físico',
            ])
            ->assertSessionHas('success');

        $this->assertEquals(50, (float) $product->stocks()
            ->where('warehouse_id', $warehouse->id)->sum('cantidad'));
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo' => 'ajuste_salida',
            'cantidad' => 70,
        ]);
    }

    public function test_company_scoping_isolates_data(): void
    {
        $user = $this->admin();

        // El admin (empresa 1) sí ve los productos sembrados.
        $this->actingAs($user);
        $this->assertGreaterThan(0, Product::count());

        // Un usuario de OTRA empresa no debe ver esos productos (scope multiempresa).
        $otraEmpresa = Company::create([
            'ruc' => '20999999999',
            'razon_social' => 'OTRA EMPRESA S.A.C.',
        ]);
        $otro = User::factory()->create(['company_id' => $otraEmpresa->id]);
        $this->actingAs($otro);
        $this->assertEquals(0, Product::count());
    }
}
