<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_libros_index_renders(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('libros.index'))->assertOk();
    }

    public function test_descarga_registro_ventas_ple(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30]],
        ]);

        $periodo = now()->format('Ym');

        $resp = $this->actingAs($user)->get(route('libros.ventas', ['periodo' => $periodo]));
        $resp->assertOk();
        $resp->assertHeader('content-disposition');
        $this->assertStringContainsString('B001', $resp->getContent());
        // Estructura PLE: campos separados por "|"
        $this->assertStringContainsString('|', $resp->getContent());
    }

    public function test_descarga_registro_compras_ple(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get(route('libros.compras', ['periodo' => now()->format('Ym')]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
