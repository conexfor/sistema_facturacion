<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFinanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    private function abrirCaja(User $user, float $monto = 0): CashSession
    {
        $register = CashRegister::firstOrFail();

        return app(CashService::class)->abrir($register, $monto);
    }

    public function test_cash_pages_render(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('caja.index'))->assertOk();
        $this->get(route('caja.flujo'))->assertOk();
        $this->get(route('cxc.index'))->assertOk();
    }

    public function test_open_movement_and_close_with_arqueo(): void
    {
        $user = $this->admin();
        $register = CashRegister::firstOrFail();

        $this->actingAs($user)
            ->post(route('caja.abrir'), ['cash_register_id' => $register->id, 'monto_apertura' => 100])
            ->assertRedirect();

        $session = CashSession::firstOrFail();
        $this->assertEquals('abierta', $session->estado);

        $this->actingAs($user)->post(route('caja.movimiento', $session->id), [
            'tipo' => 'egreso',
            'categoria' => 'gasto',
            'concepto' => 'Compra de útiles',
            'monto' => 30,
        ])->assertSessionHas('success');

        // Esperado = 100 - 30 = 70
        $this->actingAs($user)->post(route('caja.cerrar', $session->id), [
            'monto_contado' => 70,
        ])->assertSessionHas('success');

        $session->refresh();
        $this->assertEquals('cerrada', $session->estado);
        $this->assertEquals(70.0, (float) $session->monto_esperado);
        $this->assertEquals(0.0, (float) $session->diferencia);
    }

    public function test_cash_sale_feeds_open_session(): void
    {
        $user = $this->admin();
        $this->actingAs($user);
        $session = $this->abrirCaja($user, 0);
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 30,
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->id,
            'tipo' => 'ingreso',
            'categoria' => 'venta',
            'monto' => 30,
        ]);
        $this->assertEquals(30.0, $session->refresh()->saldoEsperado());
    }

    public function test_credit_sale_receivable_and_collection(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'credito',
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30],
            ],
        ])->assertRedirect();

        $sale = Sale::firstOrFail();
        $this->assertEquals('pendiente', $sale->estado);
        $this->assertEquals(0.0, (float) $sale->monto_cobrado);

        // Cobro parcial
        $this->actingAs($user)->post(route('cxc.cobrar', $sale->id), [
            'monto' => 10,
            'metodo_pago' => 'efectivo',
        ])->assertSessionHas('success');
        $this->assertEquals('pendiente', $sale->refresh()->estado);
        $this->assertEquals(10.0, (float) $sale->monto_cobrado);

        // Cobro del saldo
        $this->actingAs($user)->post(route('cxc.cobrar', $sale->id), [
            'monto' => 20,
            'metodo_pago' => 'efectivo',
        ])->assertSessionHas('success');
        $this->assertEquals('pagada', $sale->refresh()->estado);

        // Sobrecobro rechazado
        $this->actingAs($user)->post(route('cxc.cobrar', $sale->id), [
            'monto' => 5,
            'metodo_pago' => 'efectivo',
        ])->assertSessionHas('error');
    }

    public function test_cannot_open_two_sessions_for_same_register(): void
    {
        $user = $this->admin();
        $this->actingAs($user);
        $this->abrirCaja($user, 0);
        $register = CashRegister::firstOrFail();

        $this->post(route('caja.abrir'), ['cash_register_id' => $register->id, 'monto_apertura' => 0])
            ->assertSessionHas('error');

        $this->assertEquals(1, CashSession::count());
    }
}
