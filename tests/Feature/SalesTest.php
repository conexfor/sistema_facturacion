<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_sales_and_customer_pages_render(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('ventas.pos'))->assertOk();
        $this->get(route('ventas.index'))->assertOk();
        $this->get(route('clientes.index'))->assertOk();
    }

    public function test_pos_sale_reduces_stock_and_creates_movement(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail(); // 120 en ALM01
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)
            ->post(route('ventas.pos.store'), [
                'tipo_comprobante' => 'boleta',
                'warehouse_id' => $warehouse->id,
                'metodo_pago' => 'efectivo',
                'monto_recibido' => 100,
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30, 'descuento' => 0],
                ],
            ])
            ->assertRedirect();

        $sale = Sale::firstOrFail();
        $this->assertEquals(60.0, (float) $sale->total);
        $this->assertEquals(9.15, (float) $sale->igv);
        $this->assertEquals('B001', $sale->serie);
        $this->assertEquals(1, $sale->correlativo);
        $this->assertCount(1, $sale->items);

        // Stock: 120 - 2 = 118
        $this->assertEquals(118.0, (float) $product->stocks()
            ->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo' => 'salida',
            'cantidad' => 2,
        ]);
    }

    public function test_factura_requires_ruc_customer(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        // Sin cliente -> factura inválida
        $this->actingAs($user)
            ->post(route('ventas.pos.store'), [
                'tipo_comprobante' => 'factura',
                'warehouse_id' => $warehouse->id,
                'metodo_pago' => 'efectivo',
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertEquals(0, Sale::count());

        // Con cliente RUC -> ok
        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();
        $this->actingAs($user)
            ->post(route('ventas.pos.store'), [
                'tipo_comprobante' => 'factura',
                'warehouse_id' => $warehouse->id,
                'customer_id' => $ruc->id,
                'metodo_pago' => 'efectivo',
                'monto_recibido' => 50,
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30],
                ],
            ])
            ->assertRedirect();

        $this->assertEquals(1, Sale::count());
        $this->assertEquals('F001', Sale::first()->serie);
    }

    public function test_boleta_mayor_a_700_requiere_cliente_identificado(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        // Sin cliente identificado -> rechazada antes de crear la venta
        $this->actingAs($user)
            ->post(route('ventas.pos.store'), [
                'tipo_comprobante' => 'boleta',
                'warehouse_id' => $warehouse->id,
                'metodo_pago' => 'efectivo',
                'monto_recibido' => 800,
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 800],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertEquals(0, Sale::count());

        // Con cliente identificado (DNI) -> ok
        $dni = Customer::where('tipo_documento', '1')->firstOrFail();
        $this->actingAs($user)
            ->post(route('ventas.pos.store'), [
                'tipo_comprobante' => 'boleta',
                'warehouse_id' => $warehouse->id,
                'customer_id' => $dni->id,
                'metodo_pago' => 'efectivo',
                'monto_recibido' => 800,
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 800],
                ],
            ])
            ->assertRedirect();

        $this->assertEquals(1, Sale::count());
    }

    public function test_sale_fails_when_stock_insufficient(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0004')->firstOrFail(); // stock 8
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)
            ->post(route('ventas.pos.store'), [
                'tipo_comprobante' => 'boleta',
                'warehouse_id' => $warehouse->id,
                'metodo_pago' => 'efectivo',
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 999, 'precio_unitario' => 20],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertEquals(0, Sale::count());
        // El stock no debe cambiar
        $this->assertEquals(8.0, (float) $product->stocks()
            ->where('warehouse_id', $warehouse->id)->sum('cantidad'));
    }
}
