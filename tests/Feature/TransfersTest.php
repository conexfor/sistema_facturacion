<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransfersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_transfer_pages_render(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('inventario.transferencias.index'))->assertOk();
        $this->get(route('inventario.transferencias.create'))->assertOk();
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail(); // 120 en ALM01
        $alm1 = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $alm2 = Warehouse::where('codigo', 'ALM02')->firstOrFail();
        $costoAntes = (float) $product->costo_promedio;

        $this->actingAs($user)->post(route('inventario.transferencias.store'), [
            'origin_warehouse_id' => $alm1->id,
            'dest_warehouse_id' => $alm2->id,
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 30],
            ],
        ])->assertRedirect();

        $this->assertEquals(90.0, (float) $product->stocks()->where('warehouse_id', $alm1->id)->sum('cantidad'));
        $this->assertEquals(30.0, (float) $product->stocks()->where('warehouse_id', $alm2->id)->sum('cantidad'));
        // El costo promedio no cambia en una transferencia
        $this->assertEquals($costoAntes, (float) $product->fresh()->costo_promedio);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id, 'warehouse_id' => $alm1->id, 'tipo' => 'transferencia_salida',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id, 'warehouse_id' => $alm2->id, 'tipo' => 'transferencia_entrada',
        ]);
    }

    public function test_cannot_transfer_to_same_warehouse(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $alm1 = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('inventario.transferencias.store'), [
            'origin_warehouse_id' => $alm1->id,
            'dest_warehouse_id' => $alm1->id,
            'items' => [['product_id' => $product->id, 'cantidad' => 5]],
        ])->assertSessionHasErrors('dest_warehouse_id');
    }

    public function test_transfer_fails_with_insufficient_stock(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $alm1 = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $alm2 = Warehouse::where('codigo', 'ALM02')->firstOrFail();

        $this->actingAs($user)->post(route('inventario.transferencias.store'), [
            'origin_warehouse_id' => $alm1->id,
            'dest_warehouse_id' => $alm2->id,
            'items' => [['product_id' => $product->id, 'cantidad' => 99999]],
        ])->assertSessionHas('error');

        // Sin cambios
        $this->assertEquals(120.0, (float) $product->stocks()->where('warehouse_id', $alm1->id)->sum('cantidad'));
    }
}
