<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PercepcionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_percepcion_se_calcula_y_va_en_el_xml(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $user->company->update(['agente_percepcion' => true, 'percepcion_tasa' => 2]);

        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'factura',
            'warehouse_id' => $warehouse->id,
            'customer_id' => $ruc->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 200,
            'aplica_percepcion' => true,
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 118]],
        ])->assertRedirect();

        $sale = Sale::latest('id')->firstOrFail();
        $this->assertTrue((bool) $sale->aplica_percepcion);
        $this->assertEquals(118.0, (float) $sale->total);
        $this->assertEquals(2.36, (float) $sale->percepcion_monto); // 2% de 118

        // Emitir → el XML debe incluir la percepción
        $this->actingAs($user)->post(route('comprobantes.emitir', $sale->id))->assertSessionHas('success');
        $sale->refresh();
        $xml = Storage::get($sale->sunat_xml_path);
        $this->assertStringContainsString('Percepcion', $xml);      // bloque de percepción UBL
        $this->assertStringContainsString('2.36', $xml);            // monto percibido
        $this->assertStringContainsString('listID="2001"', $xml);   // operación sujeta a percepción
    }

    public function test_sin_agente_no_hay_percepcion(): void
    {
        Storage::fake('local');
        $user = $this->admin(); // company no es agente por defecto
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'aplica_percepcion' => true, // aunque lo pidan, la empresa no es agente
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ])->assertRedirect();

        $sale = Sale::latest('id')->firstOrFail();
        $this->assertFalse((bool) $sale->aplica_percepcion);
        $this->assertEquals(0.0, (float) $sale->percepcion_monto);
    }

    public function test_percepcion_en_compra_aumenta_el_total_a_pagar(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('compras.store'), [
            'warehouse_id' => $warehouse->id,
            'tipo_comprobante' => 'factura',
            'fecha' => '2026-07-05',
            'condicion_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'percepcion_tasa' => 2,
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 1, 'costo_unitario' => 100, 'afecto_igv' => true],
            ],
        ])->assertRedirect();

        $purchase = \App\Models\Purchase::latest('id')->firstOrFail();
        $this->assertEquals(118.0, (float) $purchase->total);       // 100 + 18 IGV
        $this->assertEquals(2.0, (float) $purchase->percepcion_tasa);
        $this->assertEquals(2.36, (float) $purchase->percepcion_monto); // 2% de 118
        // Total a pagar = 120.36; al contado queda pagado y sin saldo
        $this->assertEquals(120.36, (float) $purchase->monto_pagado);
        $this->assertEquals('pagada', $purchase->estado_pago);
        $this->assertEquals(0.0, $purchase->saldo);
    }
}
