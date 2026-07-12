<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchesAndPdfTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_branches_crud(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        $this->get(route('settings.sucursales.index'))->assertOk();

        $this->post(route('settings.sucursales.store'), [
            'codigo' => '0009',
            'nombre' => 'Sucursal Sur',
            'direccion' => 'Av. Sur 100',
            'is_active' => true,
        ])->assertSessionHas('success');

        $branch = Branch::where('codigo', '0009')->firstOrFail();
        $this->assertEquals('Sucursal Sur', $branch->nombre);

        $this->put(route('settings.sucursales.update', $branch->id), [
            'codigo' => '0009',
            'nombre' => 'Sucursal Sur Renovada',
            'is_active' => true,
        ])->assertSessionHas('success');
        $this->assertEquals('Sucursal Sur Renovada', $branch->refresh()->nombre);

        $this->delete(route('settings.sucursales.destroy', $branch->id))->assertSessionHas('success');
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_cannot_delete_main_branch(): void
    {
        $user = $this->admin();
        $main = Branch::where('company_id', $user->company_id)->where('is_main', true)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('settings.sucursales.destroy', $main->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $main->id]);
    }

    public function test_sale_pdf_downloads(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ]);
        $sale = Sale::latest('id')->firstOrFail();

        $res = $this->actingAs($user)->get(route('ventas.pdf', $sale->id));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
    }
}
