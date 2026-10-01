<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ElectronicInvoiceService;
use App\Services\SaleService;
use App\Services\VoidedService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidedTest extends TestCase
{
    use RefreshDatabase;

    public function test_baja_factura_simulada_anula_y_devuelve_stock(): void
    {
        $this->seed(TestSeeder::class);
        $user = User::where('email', 'admin@erp.test')->firstOrFail();
        $this->actingAs($user);

        $product = Product::where('codigo', 'P0001')->firstOrFail(); // 120 en ALM01
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();

        $factura = app(SaleService::class)->registrar([
            'tipo_comprobante' => 'factura',
            'warehouse_id' => $warehouse->id,
            'customer_id' => $ruc->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 200,
            'items' => [['product_id' => $product->id, 'cantidad' => 5, 'precio_unitario' => 30]],
        ]);

        // 120 - 5 = 115
        $this->assertEquals(115.0, (float) $product->stocks()
            ->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        // Emitir (simulación) para tener un comprobante "enviado"
        app(ElectronicInvoiceService::class)->emitir($factura);
        $this->assertEquals('simulado', $factura->fresh()->sunat_estado);

        // Comunicación de baja (simulada → se aplica de inmediato)
        $voided = app(VoidedService::class)->anular($factura->fresh(), 'Error en los datos del cliente');
        $this->assertEquals('simulado', $voided->estado);

        $factura->refresh();
        $this->assertEquals('anulada', $factura->estado);
        $this->assertEquals('anulado', $factura->sunat_estado);

        // Stock devuelto: 115 + 5 = 120
        $this->assertEquals(120.0, (float) $product->stocks()
            ->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        // No se puede dar de baja dos veces
        $this->expectException(\RuntimeException::class);
        app(VoidedService::class)->anular($factura->fresh(), 'otra vez');
    }

    public function test_boletas_no_usan_comunicacion_de_baja(): void
    {
        $this->seed(TestSeeder::class);
        $user = User::where('email', 'admin@erp.test')->firstOrFail();
        $this->actingAs($user);

        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $boleta = app(SaleService::class)->registrar([
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ]);

        $this->expectException(\RuntimeException::class);
        app(VoidedService::class)->anular($boleta, 'x');
    }
}
