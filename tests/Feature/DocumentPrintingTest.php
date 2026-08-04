<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Despatch;
use App\Models\Note;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verifica que la representación impresa (A4 y ticket 80 mm) se genere
 * correctamente para venta, nota de crédito y guía de remisión.
 */
class DocumentPrintingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    private function assertPdf($response): void
    {
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    private function venta(User $user, string $tipo = 'boleta', ?int $customerId = null): Sale
    {
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => $tipo,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 200,
            'items' => [['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30]],
        ]);

        return Sale::latest('id')->firstOrFail();
    }

    public function test_sale_ticket_and_a4_render(): void
    {
        $user = $this->admin();
        $sale = $this->venta($user, 'boleta');

        $this->assertPdf($this->actingAs($user)->get(route('ventas.pdf', $sale->id)));
        $this->assertPdf($this->actingAs($user)->get(route('ventas.pdf', ['sale' => $sale->id, 'formato' => 'ticket'])));
    }

    public function test_sale_snapshots_customer_address(): void
    {
        $user = $this->admin();
        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();
        $ruc->update(['direccion' => 'Av. Domicilio Fiscal 999 - Lima']);

        $sale = $this->venta($user, 'factura', $ruc->id);

        $this->assertEquals('Av. Domicilio Fiscal 999 - Lima', $sale->cliente_direccion);
    }

    public function test_credit_note_ticket_and_a4_render(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $sale = $this->venta($user, 'boleta');

        $this->actingAs($user)->post(route('notas.store'), [
            'sale_id' => $sale->id,
            'tipo' => 'credito',
            'cod_motivo' => '01',
            'des_motivo' => 'Anulación de la operación',
        ]);

        $note = Note::firstOrFail();

        $this->assertPdf($this->actingAs($user)->get(route('notas.pdf', $note->id)));
        $this->assertPdf($this->actingAs($user)->get(route('notas.pdf', ['note' => $note->id, 'formato' => 'ticket'])));
    }

    public function test_despatch_ticket_and_a4_render(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();

        $this->actingAs($user)->post(route('guias.store'), [
            'fecha_emision' => '2026-06-26',
            'fecha_traslado' => '2026-06-27',
            'cod_traslado' => '01',
            'des_traslado' => 'Venta',
            'mod_traslado' => '02',
            'peso_total' => 12.5,
            'num_bultos' => 3,
            'dest_tipo_doc' => '6',
            'dest_num_doc' => '20512345678',
            'dest_nombre' => 'COMERCIAL RAMIREZ SAC',
            'partida_ubigeo' => '150101',
            'partida_direccion' => 'Av. Principal 123',
            'llegada_ubigeo' => '150103',
            'llegada_direccion' => 'Jr. Destino 456',
            'vehiculo_placa' => 'ABC-123',
            'chofer_num_doc' => '45678912',
            'chofer_licencia' => 'Q45678912',
            'chofer_nombres' => 'Juan',
            'chofer_apellidos' => 'Pérez',
            'items' => [
                ['product_id' => $product->id, 'descripcion' => 'Arroz Superior 5kg', 'cantidad' => 10, 'unidad' => 'NIU'],
            ],
        ])->assertSessionHas('success');

        $guia = Despatch::firstOrFail();

        $this->assertPdf($this->actingAs($user)->get(route('guias.pdf', $guia->id)));
        $this->assertPdf($this->actingAs($user)->get(route('guias.pdf', ['despatch' => $guia->id, 'formato' => 'ticket'])));
    }
}
