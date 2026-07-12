<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SaleService;
use App\Services\Sunat\InvoiceBuilder;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\FormaPagos\FormaPagoCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function setup_(): User
    {
        $this->seed();
        $user = User::where('email', 'admin@erp.test')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_factura_al_credito_usa_forma_pago_credito_con_cuota(): void
    {
        $user = $this->setup_();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();

        $factura = app(SaleService::class)->registrar([
            'tipo_comprobante' => 'factura',
            'warehouse_id' => $warehouse->id,
            'customer_id' => $ruc->id,
            'metodo_pago' => 'credito',
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ]);

        $invoice = app(InvoiceBuilder::class)->build($factura->fresh(), $user->company);

        $this->assertInstanceOf(FormaPagoCredito::class, $invoice->getFormaPago());
        $this->assertEquals('Credito', $invoice->getFormaPago()->getTipo());
        $this->assertCount(1, $invoice->getCuotas());
        $this->assertEquals(
            round((float) $factura->total, 2),
            (float) $invoice->getCuotas()[0]->getMonto(),
        );
    }

    public function test_venta_al_contado_usa_forma_pago_contado(): void
    {
        $user = $this->setup_();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $boleta = app(SaleService::class)->registrar([
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ]);

        $invoice = app(InvoiceBuilder::class)->build($boleta->fresh(), $user->company);

        $this->assertInstanceOf(FormaPagoContado::class, $invoice->getFormaPago());
        $this->assertEmpty($invoice->getCuotas());
    }

    public function test_producto_exonerado_se_clasifica_y_no_lleva_igv(): void
    {
        $user = $this->setup_();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $exonerado = \App\Models\Product::create([
            'company_id' => $user->company_id,
            'codigo' => 'EXO1',
            'nombre' => 'Producto exonerado',
            'tipo' => 'bien',
            'precio_compra' => 10,
            'precio_venta' => 20,
            'tipo_afectacion_igv' => '20',
            'stock_minimo' => 0,
            'controla_stock' => false,
        ]);

        // afecto_igv se sincroniza automáticamente desde el tipo
        $this->assertFalse($exonerado->fresh()->afecto_igv);

        $boleta = app(SaleService::class)->registrar([
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 50,
            'items' => [['product_id' => $exonerado->id, 'cantidad' => 1, 'precio_unitario' => 20]],
        ]);

        $this->assertEquals(0.0, (float) $boleta->igv);
        $this->assertEquals('20', $boleta->items()->first()->tipo_afectacion);

        $invoice = app(InvoiceBuilder::class)->build($boleta->fresh(), $user->company);
        $this->assertEquals('20', $invoice->getDetails()[0]->getTipAfeIgv());
        $this->assertEquals(20.0, (float) $invoice->getMtoOperExoneradas());
        $this->assertEquals(0.0, (float) $invoice->getMtoIGV());
    }
}
