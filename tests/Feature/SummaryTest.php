<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SaleService;
use App\Services\SummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumen_diario_agrupa_y_envia_en_simulacion(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@erp.test')->firstOrFail();
        $this->actingAs($user);

        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $warehouse = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $sales = app(SaleService::class);

        $b1 = $sales->registrar([
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => 30]],
        ]);
        $sales->registrar([
            'tipo_comprobante' => 'boleta',
            'warehouse_id' => $warehouse->id,
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 100,
            'items' => [['product_id' => $product->id, 'cantidad' => 2, 'precio_unitario' => 30]],
        ]);

        $service = app(SummaryService::class);
        $fecha = Carbon::today();

        // Hay 2 boletas pendientes de resumen
        $this->assertEquals(2, $service->boletasPendientes($fecha)->count());

        // Generar agrupa las boletas
        $summary = $service->generar($fecha);
        $this->assertEquals(2, $summary->total_boletas);
        $this->assertStringStartsWith('RC-', $summary->identificador);
        $this->assertEquals(2, $summary->sales()->count());
        $this->assertEquals(0, $service->boletasPendientes($fecha)->count());

        // Enviar en modo simulación (empresa sin credenciales SUNAT)
        $summary = $service->enviar($summary);
        $this->assertEquals('simulado', $summary->estado);
        $this->assertNotNull($summary->ticket);
        $this->assertEquals('simulado', $b1->refresh()->sunat_estado);

        // No se puede reenviar un resumen ya procesado
        $this->expectException(\RuntimeException::class);
        $service->enviar($summary);
    }

    public function test_no_genera_resumen_sin_boletas(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'admin@erp.test')->firstOrFail());

        $this->expectException(\RuntimeException::class);
        app(SummaryService::class)->generar(Carbon::today()->subDay());
    }
}
