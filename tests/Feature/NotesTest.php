<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Note;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    private function venta(User $user, string $tipo, ?int $customerId = null): Sale
    {
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => $tipo,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customerId,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30]],
        ]);

        return Sale::latest('id')->firstOrFail();
    }

    public function test_notes_index_renders(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('notas.index'))->assertOk();
    }

    public function test_credit_note_anula_venta_y_devuelve_stock(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $this->actingAs($user);
        app(CashService::class)->abrir(CashRegister::firstOrFail(), 0);
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $sale = $this->venta($user, 'boleta'); // stock 120 -> 118

        $this->assertEquals(118.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        $this->actingAs($user)->post(route('notas.store'), [
            'sale_id' => $sale->id,
            'tipo' => 'credito',
            'cod_motivo' => '01',
            'des_motivo' => 'Anulación de la operación',
        ])->assertSessionHas('success');

        $note = Note::firstOrFail();
        $this->assertEquals('credito', $note->tipo);
        $this->assertEquals('07', $note->tipo_doc);
        $this->assertEquals('BC01', $note->serie);
        $this->assertEquals('simulado', $note->sunat_estado);

        // Venta anulada y stock devuelto (118 -> 120)
        $this->assertEquals('anulada', $sale->refresh()->estado);
        $this->assertEquals(120.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));
    }

    public function test_debit_note_genera_documento_08(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $ruc = Customer::where('tipo_documento', '6')->firstOrFail();
        $sale = $this->venta($user, 'factura', $ruc->id);

        $this->actingAs($user)->post(route('notas.store'), [
            'sale_id' => $sale->id,
            'tipo' => 'debito',
            'cod_motivo' => '01',
            'des_motivo' => 'Intereses por mora',
            'concepto' => 'Interés por pago tardío',
            'monto' => 11.80,
            'afecto_igv' => true,
        ])->assertSessionHas('success');

        $note = Note::where('tipo', 'debito')->firstOrFail();
        $this->assertEquals('08', $note->tipo_doc);
        $this->assertEquals('FD01', $note->serie);
        $this->assertEquals(11.80, (float) $note->total);
        $this->assertEqualsWithDelta(1.80, (float) $note->igv, 0.02);
        // La venta NO se anula por una nota de débito
        $this->assertEquals('pagada', $sale->refresh()->estado);
    }

    public function test_credit_note_reembolsa_efectivo_en_caja(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $this->actingAs($user);
        app(CashService::class)->abrir(CashRegister::firstOrFail(), 0);

        $sale = $this->venta($user, 'boleta'); // efectivo, total 60 -> ingreso en caja

        $this->post(route('notas.store'), [
            'sale_id' => $sale->id,
            'tipo' => 'credito',
            'cod_motivo' => '01',
            'des_motivo' => 'Anulación de la operación',
        ])->assertSessionHas('success');

        $this->assertEquals('anulada', $sale->refresh()->estado);
        $this->assertDatabaseHas('cash_movements', [
            'sale_id' => $sale->id,
            'tipo' => 'egreso',
            'categoria' => 'reembolso',
            'monto' => 60,
        ]);
    }

    public function test_credit_note_avisa_si_no_hay_caja_abierta_para_reembolsar(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $sale = $this->venta($user, 'boleta'); // efectivo, sin caja abierta

        $this->actingAs($user)->post(route('notas.store'), [
            'sale_id' => $sale->id,
            'tipo' => 'credito',
            'cod_motivo' => '01',
            'des_motivo' => 'Anulación de la operación',
        ])->assertSessionHas('warning');

        // La nota y la anulación se aplican igual; solo el reembolso de caja queda pendiente.
        $this->assertEquals('anulada', $sale->refresh()->estado);
        $this->assertDatabaseMissing('cash_movements', ['sale_id' => $sale->id, 'categoria' => 'reembolso']);
    }

    public function test_credit_note_parcial_devuelve_solo_lo_indicado(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $this->actingAs($user);
        app(CashService::class)->abrir(CashRegister::firstOrFail(), 0);
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $sale = $this->venta($user, 'boleta'); // 2 und, total 60, stock 120 -> 118
        $itemId = $sale->items()->first()->id;

        // Devolver 1 de 2
        $this->post(route('notas.store'), [
            'sale_id' => $sale->id,
            'tipo' => 'credito',
            'cod_motivo' => '07',
            'des_motivo' => 'Devolución por ítem',
            'items' => [['sale_item_id' => $itemId, 'cantidad' => 1]],
        ])->assertSessionHas('success');

        $note = Note::firstOrFail();
        $this->assertEquals(30.0, (float) $note->total);
        $this->assertEquals('07', $note->tipo_doc);
        // Venta NO anulada (queda 1 por devolver) y stock 118 -> 119
        $this->assertEquals('pagada', $sale->refresh()->estado);
        $this->assertEquals(119.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));

        // No se puede devolver más de lo disponible (queda 1, pido 5)
        $this->post(route('notas.store'), [
            'sale_id' => $sale->id, 'tipo' => 'credito', 'cod_motivo' => '07',
            'des_motivo' => 'Devolución', 'items' => [['sale_item_id' => $itemId, 'cantidad' => 5]],
        ])->assertSessionHas('error');

        // Devolver el último 1 -> venta anulada, stock 120
        $this->post(route('notas.store'), [
            'sale_id' => $sale->id, 'tipo' => 'credito', 'cod_motivo' => '07',
            'des_motivo' => 'Devolución', 'items' => [['sale_item_id' => $itemId, 'cantidad' => 1]],
        ])->assertSessionHas('success');

        $this->assertEquals('anulada', $sale->refresh()->estado);
        $this->assertEquals(120.0, (float) $product->stocks()->where('warehouse_id', $warehouse->id)->sum('cantidad'));
    }

    public function test_no_se_puede_anular_dos_veces(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $sale = $this->venta($user, 'boleta');

        $this->actingAs($user)->post(route('notas.store'), [
            'sale_id' => $sale->id, 'tipo' => 'credito', 'cod_motivo' => '01', 'des_motivo' => 'Anulación',
        ]);

        $this->actingAs($user)->post(route('notas.store'), [
            'sale_id' => $sale->id, 'tipo' => 'credito', 'cod_motivo' => '01', 'des_motivo' => 'Anulación',
        ])->assertSessionHas('error');

        $this->assertEquals(1, Note::where('tipo', 'credito')->count());
    }
}
