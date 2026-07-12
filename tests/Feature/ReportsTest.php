<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function adminConVenta(): User
    {
        $this->seed();
        $user = User::where('email', 'admin@erp.test')->firstOrFail();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();

        $this->actingAs($user)->post(route('ventas.pos.store'), [
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 60,
            'items' => [
                ['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30],
            ],
        ]);

        return $user;
    }

    public function test_reports_index_and_views_render(): void
    {
        $user = $this->adminConVenta();

        $this->actingAs($user)->get(route('reportes.index'))->assertOk();

        $this->actingAs($user)
            ->get(route('reportes.show', 'ventas'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('Reports/View')
                ->where('report.key', 'ventas')
                ->has('report.rows', 1)
                ->has('report.columns')
                ->has('report.summary'));

        $this->actingAs($user)->get(route('reportes.show', 'stock-valorizado'))->assertOk();
        $this->actingAs($user)->get(route('reportes.show', 'rentabilidad'))->assertOk();
    }

    public function test_unknown_report_is_404(): void
    {
        $user = $this->adminConVenta();
        $this->actingAs($user)->get(route('reportes.show', 'inexistente'))->assertNotFound();
    }

    public function test_excel_export_downloads(): void
    {
        $user = $this->adminConVenta();

        $this->actingAs($user)
            ->get(route('reportes.export', ['key' => 'ventas', 'formato' => 'excel']))
            ->assertDownload();
    }

    public function test_pdf_export_downloads(): void
    {
        $user = $this->adminConVenta();

        $response = $this->actingAs($user)
            ->get(route('reportes.export', ['key' => 'ventas', 'formato' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }
}
