<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductLot;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_lots_index_renders(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('inventario.lotes.index'))->assertOk();
    }

    public function test_ingresar_lote_suma_stock(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail(); // 120 en ALM01
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('inventario.lotes.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'lote' => 'L-001',
            'fecha_vencimiento' => now()->addDays(20)->toDateString(),
            'cantidad' => 50,
            'costo' => 22,
        ])->assertSessionHas('success');

        $lot = ProductLot::firstOrFail();
        $this->assertEquals('L-001', $lot->lote);
        $this->assertEquals(50.0, (float) $lot->cantidad);
        $this->assertEquals('por_vencer', $lot->estado);
        $this->assertTrue($product->fresh()->controla_lotes);
        $this->assertEquals(170.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));
    }

    public function test_merma_reduce_lote_y_stock(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('inventario.lotes.store'), [
            'product_id' => $product->id, 'warehouse_id' => $warehouse->id,
            'lote' => 'L-002', 'fecha_vencimiento' => now()->subDays(1)->toDateString(),
            'cantidad' => 30, 'costo' => 22,
        ]);
        $lot = ProductLot::where('lote', 'L-002')->firstOrFail();
        $this->assertEquals('vencido', $lot->estado);

        $this->actingAs($user)->post(route('inventario.lotes.merma', $lot->id), [
            'cantidad' => 10, 'motivo' => 'Vencido',
        ])->assertSessionHas('success');

        $this->assertEquals(20.0, (float) $lot->refresh()->cantidad);
        // Stock: 120 + 30 - 10 = 140
        $this->assertEquals(140.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));
    }

    public function test_merma_no_excede_saldo(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('inventario.lotes.store'), [
            'product_id' => $product->id, 'warehouse_id' => $warehouse->id,
            'lote' => 'L-003', 'cantidad' => 5, 'costo' => 22,
        ]);
        $lot = ProductLot::where('lote', 'L-003')->firstOrFail();

        $this->actingAs($user)->post(route('inventario.lotes.merma', $lot->id), [
            'cantidad' => 999,
        ])->assertSessionHas('error');

        $this->assertEquals(5.0, (float) $lot->refresh()->cantidad);
    }
}
