<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(TestSeeder::class);

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_purchase_pages_render(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('compras.index'))->assertOk();
        $this->get(route('compras.create'))->assertOk();
        $this->get(route('compras.proveedores.index'))->assertOk();
        $this->get(route('compras.payables'))->assertOk();
    }

    public function test_credit_purchase_increases_stock_and_creates_payable(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail(); // 120 en ALM01
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $supplier = Supplier::firstOrFail();

        $this->actingAs($user)
            ->post(route('compras.store'), [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'tipo_comprobante' => 'factura',
                'serie' => 'F001',
                'numero' => '000123',
                'fecha' => '2026-06-25',
                'fecha_vencimiento' => '2026-07-25',
                'condicion_pago' => 'credito',
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 20, 'afecto_igv' => true],
                ],
            ])
            ->assertRedirect();

        $purchase = Purchase::firstOrFail();
        $this->assertEquals(200.0, (float) $purchase->subtotal);
        $this->assertEquals(36.0, (float) $purchase->igv);
        $this->assertEquals(236.0, (float) $purchase->total);
        $this->assertEquals('pendiente', $purchase->estado_pago);
        $this->assertEquals(0.0, (float) $purchase->monto_pagado);

        // Stock: 120 + 10 = 130
        $this->assertEquals(130.0, (float) $product->stocks()
            ->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo' => 'entrada',
            'cantidad' => 10,
        ]);
    }

    public function test_cash_purchase_is_paid_and_records_payment(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0002')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)
            ->post(route('compras.store'), [
                'warehouse_id' => $warehouse->id,
                'tipo_comprobante' => 'boleta',
                'fecha' => '2026-06-25',
                'condicion_pago' => 'contado',
                'metodo_pago' => 'efectivo',
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 10],
                ],
            ])
            ->assertRedirect();

        $purchase = Purchase::firstOrFail();
        $this->assertEquals('pagada', $purchase->estado_pago);
        $this->assertEquals((float) $purchase->total, (float) $purchase->monto_pagado);
        $this->assertCount(1, $purchase->payments);
    }

    public function test_registering_payment_updates_state(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('compras.store'), [
            'warehouse_id' => $warehouse->id,
            'tipo_comprobante' => 'factura',
            'fecha' => '2026-06-25',
            'condicion_pago' => 'credito',
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 20],
            ],
        ]);

        $purchase = Purchase::firstOrFail(); // total 236

        // Pago parcial
        $this->actingAs($user)->post(route('compras.pagar', $purchase->id), [
            'monto' => 100,
            'metodo_pago' => 'transferencia',
        ])->assertSessionHas('success');
        $this->assertEquals('parcial', $purchase->refresh()->estado_pago);

        // Pago del saldo
        $this->actingAs($user)->post(route('compras.pagar', $purchase->id), [
            'monto' => 136,
            'metodo_pago' => 'efectivo',
        ])->assertSessionHas('success');
        $this->assertEquals('pagada', $purchase->refresh()->estado_pago);

        // No se puede sobrepagar
        $this->actingAs($user)->post(route('compras.pagar', $purchase->id), [
            'monto' => 10,
            'metodo_pago' => 'efectivo',
        ])->assertSessionHas('error');
    }
}
