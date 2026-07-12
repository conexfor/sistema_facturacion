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

class SunatTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    private function crearVenta(User $user, string $tipo, ?int $customerId = null): Sale
    {
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => $tipo,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30],
            ],
        ]);

        return Sale::latest('id')->firstOrFail();
    }

    public function test_comprobantes_index_renders(): void
    {
        $user = $this->admin();
        $this->crearVenta($user, 'boleta');

        $this->actingAs($user)->get(route('comprobantes.index'))->assertOk();
    }

    public function test_emitir_boleta_en_simulacion_genera_xml(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $sale = $this->crearVenta($user, 'boleta');

        $this->actingAs($user)
            ->post(route('comprobantes.emitir', $sale->id))
            ->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals('simulado', $sale->sunat_estado);
        $this->assertNotNull($sale->sunat_xml_path);
        $this->assertNotNull($sale->sunat_hash);
        Storage::assertExists($sale->sunat_xml_path);

        // El XML es UBL 2.1 firmado
        $xml = Storage::get($sale->sunat_xml_path);
        $this->assertStringContainsString('<Invoice', $xml);
        $this->assertStringContainsString('ds:Signature', $xml);
    }

    public function test_emitir_factura_con_ruc(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();
        $sale = $this->crearVenta($user, 'factura', $ruc->id);

        $this->actingAs($user)
            ->post(route('comprobantes.emitir', $sale->id))
            ->assertSessionHas('success');

        $this->assertEquals('simulado', $sale->refresh()->sunat_estado);
    }

    public function test_descarga_xml(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $sale = $this->crearVenta($user, 'boleta');
        $this->actingAs($user)->post(route('comprobantes.emitir', $sale->id));

        $this->actingAs($user)
            ->get(route('comprobantes.xml', $sale->id))
            ->assertOk();
    }

    public function test_nota_venta_no_se_emite(): void
    {
        $user = $this->admin();
        $sale = $this->crearVenta($user, 'nota_venta');

        $this->actingAs($user)
            ->post(route('comprobantes.emitir', $sale->id))
            ->assertSessionHas('error');

        $this->assertEquals('no_enviado', $sale->refresh()->sunat_estado);
    }

    public function test_config_sunat_page_renders(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get(route('settings.sunat.edit'))->assertOk();
    }
}
