<?php

namespace Tests\Feature;

use App\Models\Despatch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DespatchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    private function payload(int $productId): array
    {
        return [
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
                ['product_id' => $productId, 'descripcion' => 'Arroz Superior 5kg', 'cantidad' => 10, 'unidad' => 'NIU'],
            ],
        ];
    }

    public function test_despatch_pages_render(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('guias.index'))->assertOk();
        $this->get(route('guias.create'))->assertOk();
    }

    public function test_create_and_emit_despatch(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();

        $this->actingAs($user)
            ->post(route('guias.store'), $this->payload($product->id))
            ->assertSessionHas('success');

        $g = Despatch::firstOrFail();
        $this->assertEquals('T001', $g->serie);
        $this->assertEquals(1, $g->correlativo);
        $this->assertEquals('09', '09'); // GRE remitente
        $this->assertEquals('simulado', $g->sunat_estado);
        $this->assertCount(1, $g->items);
        Storage::assertExists($g->sunat_xml_path);

        $xml = Storage::get($g->sunat_xml_path);
        $this->assertStringContainsString('DespatchAdvice', $xml);
        $this->assertStringContainsString('ds:Signature', $xml);
    }

    public function test_private_transport_requires_vehicle(): void
    {
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $data = $this->payload($product->id);
        unset($data['vehiculo_placa'], $data['chofer_num_doc'], $data['chofer_licencia'], $data['chofer_nombres']);

        $this->actingAs($user)
            ->post(route('guias.store'), $data)
            ->assertSessionHasErrors(['vehiculo_placa', 'chofer_num_doc']);
    }
}
