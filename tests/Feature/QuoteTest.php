<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(TestSeeder::class);

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('cotizaciones.index'))->assertOk();
        $this->get(route('pedidos.index'))->assertOk();
        $this->get(route('cotizaciones.create'))->assertOk();
    }

    public function test_crear_cotizacion(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();

        $this->actingAs($user)->post(route('documentos.store'), [
            'tipo' => 'cotizacion',
            'fecha' => '2026-06-26',
            'items' => [['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30]],
        ])->assertRedirect();

        $quote = Quote::firstOrFail();
        $this->assertEquals('cotizacion', $quote->tipo);
        $this->assertEquals('COT1', $quote->serie);
        $this->assertEquals(60.0, (float) $quote->total);
        $this->assertEquals(9.15, (float) $quote->igv);
    }

    public function test_crear_pedido_usa_serie_ped1(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();

        $this->actingAs($user)->post(route('documentos.store'), [
            'tipo' => 'pedido',
            'fecha' => '2026-06-26',
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ])->assertRedirect();

        $this->assertEquals('PED1', Quote::firstOrFail()->serie);
    }

    public function test_convertir_en_venta_descuenta_stock(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('documentos.store'), [
            'tipo' => 'cotizacion',
            'fecha' => '2026-06-26',
            'items' => [['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30]],
        ]);
        $quote = Quote::firstOrFail();

        $this->actingAs($user)->post(route('documentos.convertir', $quote->id), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
        ])->assertRedirect();

        $quote->refresh();
        $this->assertEquals('convertida', $quote->estado);
        $this->assertNotNull($quote->sale_id);

        $sale = Sale::findOrFail($quote->sale_id);
        $this->assertEquals(60.0, (float) $sale->total);
        $this->assertEquals(118.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        // No se puede convertir dos veces
        $this->actingAs($user)->post(route('documentos.convertir', $quote->id), [
            'tipo_comprobante' => 'boleta', 'warehouse_id' => $warehouse->id, 'metodo_pago' => 'efectivo', 'monto_recibido' => 100,
        ])->assertSessionHas('error');
    }
}
