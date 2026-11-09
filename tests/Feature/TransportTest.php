<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Despatch;
use App\Models\Driver;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(TestSeeder::class);

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    private function vehiculo(User $user, array $attrs = []): Vehicle
    {
        return Vehicle::create($attrs + [
            'company_id' => $user->company_id,
            'placa' => 'ABC123',
            'marca' => 'Hino',
            'capacidad_kg' => 5000,
        ]);
    }

    private function conductor(User $user, array $attrs = []): Driver
    {
        return Driver::create($attrs + [
            'company_id' => $user->company_id,
            'tipo_doc' => '1',
            'num_doc' => '45678912',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez Soto',
            'licencia' => 'Q45678912',
            'licencia_vence' => now()->addYear()->toDateString(),
        ]);
    }

    /** Guía mínima de transporte privado; los datos de transporte se pasan aparte. */
    private function guiaPayload(array $transporte, float $peso = 120): array
    {
        $product = Product::where('codigo', 'P0001')->firstOrFail();

        return $transporte + [
            'fecha_emision' => now()->toDateString(),
            'fecha_traslado' => now()->toDateString(),
            'cod_traslado' => '01',
            'des_traslado' => 'Venta',
            'mod_traslado' => '02',
            'peso_total' => $peso,
            'num_bultos' => 2,
            'dest_tipo_doc' => '6',
            'dest_num_doc' => '20512345678',
            'dest_nombre' => 'COMERCIAL RAMIREZ SAC',
            'partida_ubigeo' => '150101',
            'partida_direccion' => 'Av. Principal 123',
            'llegada_ubigeo' => '150103',
            'llegada_direccion' => 'Jr. Destino 456',
            'items' => [
                ['product_id' => $product->id, 'descripcion' => 'Arroz Superior 5kg', 'cantidad' => 10, 'unidad' => 'NIU'],
            ],
        ];
    }

    public function test_transport_pages_render(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('transporte.vehiculos.index'))->assertOk();
        $this->get(route('transporte.conductores.index'))->assertOk();
        $this->get(route('transporte.transportistas.index'))->assertOk();
        $this->get(route('transporte.viajes.index'))->assertOk();
    }

    public function test_permission_is_required(): void
    {
        $admin = $this->admin();

        $almacenero = User::factory()->create(['company_id' => $admin->company_id]);
        $almacenero->assignRole('Almacenero');
        $this->actingAs($almacenero)->get(route('transporte.vehiculos.index'))->assertForbidden();

        $vendedor = User::factory()->create(['company_id' => $admin->company_id]);
        $vendedor->assignRole('Vendedor');
        $this->actingAs($vendedor)->get(route('transporte.viajes.index'))->assertOk();
    }

    public function test_vehicle_plate_is_normalized_and_unique(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('transporte.vehiculos.store'), [
            'placa' => 'abc-123',
            'capacidad_kg' => 3000,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehicles', ['placa' => 'ABC123', 'company_id' => $user->company_id]);

        $this->actingAs($user)->post(route('transporte.vehiculos.store'), [
            'placa' => 'ABC 123',
            'is_active' => true,
        ])->assertSessionHasErrors('placa');
    }

    public function test_despatch_takes_snapshot_from_catalog(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $vehicle = $this->vehiculo($user);
        $driver = $this->conductor($user);

        $this->actingAs($user)
            ->post(route('guias.store'), $this->guiaPayload([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
            ]))
            ->assertSessionHas('success');

        $g = Despatch::firstOrFail();
        $this->assertEquals($vehicle->id, $g->vehicle_id);
        $this->assertEquals($driver->id, $g->driver_id);
        $this->assertEquals('ABC123', $g->vehiculo_placa);
        $this->assertEquals('45678912', $g->chofer_num_doc);
        $this->assertEquals('Q45678912', $g->chofer_licencia);
        $this->assertEquals('Pérez Soto', $g->chofer_apellidos);

        // Editar el catálogo no altera la guía ya emitida.
        $driver->update(['licencia' => 'Q99999999']);
        $this->assertEquals('Q45678912', $g->fresh()->chofer_licencia);
    }

    public function test_despatch_with_carrier_for_public_transport(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $carrier = Carrier::create([
            'company_id' => $user->company_id,
            'ruc' => '20100070970',
            'razon_social' => 'TRANSPORTES DEL SUR SAC',
            'nro_mtc' => '1534567CNG',
        ]);

        $this->actingAs($user)
            ->post(route('guias.store'), $this->guiaPayload([
                'mod_traslado' => '01',
                'carrier_id' => $carrier->id,
            ]))
            ->assertSessionHas('success');

        $g = Despatch::firstOrFail();
        $this->assertEquals($carrier->id, $g->carrier_id);
        $this->assertEquals('20100070970', $g->transportista_doc);
        $this->assertEquals('1534567CNG', $g->transportista_mtc);
        $this->assertNull($g->vehicle_id);
    }

    public function test_despatch_rejects_expired_license_and_overweight(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $vehicle = $this->vehiculo($user, ['capacidad_kg' => 100]);
        $vencido = $this->conductor($user, ['licencia_vence' => now()->subDay()->toDateString()]);

        $this->actingAs($user)
            ->post(route('guias.store'), $this->guiaPayload(['vehicle_id' => $vehicle->id, 'driver_id' => $vencido->id], 50))
            ->assertSessionHas('error');

        $vencido->update(['licencia_vence' => now()->addYear()->toDateString()]);

        $this->actingAs($user)
            ->post(route('guias.store'), $this->guiaPayload(['vehicle_id' => $vehicle->id, 'driver_id' => $vencido->id], 150))
            ->assertSessionHas('error');

        $this->assertEquals(0, Despatch::count());
    }

    public function test_despatch_from_transfer(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $product = Product::where('codigo', 'P0001')->firstOrFail();
        $alm1 = Warehouse::where('codigo', 'ALM01')->firstOrFail();
        $alm2 = Warehouse::where('codigo', 'ALM02')->firstOrFail();
        $alm2->update(['direccion' => 'Av. Almacén Norte 500', 'ubigeo' => '150135']);

        $this->actingAs($user)->post(route('inventario.transferencias.store'), [
            'origin_warehouse_id' => $alm1->id,
            'dest_warehouse_id' => $alm2->id,
            'items' => [['product_id' => $product->id, 'cantidad' => 30]],
        ]);
        $transfer = Transfer::firstOrFail();

        $this->actingAs($user)
            ->get(route('guias.create', ['transfer' => $transfer->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sunat/Despatches/Create')
                ->where('prefill.transfer_id', $transfer->id)
                ->where('prefill.cod_traslado', '04')
                ->where('prefill.dest_num_doc', $user->company->ruc)
                ->where('prefill.llegada_direccion', 'Av. Almacén Norte 500')
                ->where('prefill.llegada_ubigeo', '150135')
                ->has('prefill.items', 1));

        $vehicle = $this->vehiculo($user);
        $driver = $this->conductor($user);
        $payload = $this->guiaPayload([
            'transfer_id' => $transfer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'cod_traslado' => '04',
            'des_traslado' => 'Traslado entre establecimientos de la misma empresa',
        ]);

        $this->actingAs($user)->post(route('guias.store'), $payload)->assertSessionHas('success');
        $this->assertEquals($transfer->id, Despatch::firstOrFail()->transfer_id);

        // Una sola guía por transferencia.
        $this->actingAs($user)->post(route('guias.store'), $payload)->assertSessionHas('error');
        $this->actingAs($user)
            ->get(route('guias.create', ['transfer' => $transfer->id]))
            ->assertRedirect();
        $this->assertEquals(1, Despatch::count());
    }

    public function test_trip_lifecycle(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $vehicle = $this->vehiculo($user);
        $driver = $this->conductor($user);

        $this->actingAs($user)->post(route('guias.store'), $this->guiaPayload([
            'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]));
        $guia = Despatch::firstOrFail();

        $this->actingAs($user)->post(route('transporte.viajes.store'), [
            'fecha' => now()->toDateString(),
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'destino' => 'Lima Norte',
        ])->assertRedirect();
        $trip = Trip::firstOrFail();
        $this->assertEquals('programado', $trip->estado);

        // Sin guías no puede salir.
        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'en_ruta'])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->get(route('transporte.viajes.buscar-guias'))
            ->assertOk()
            ->assertJsonFragment(['id' => $guia->id]);

        $this->actingAs($user)
            ->post(route('transporte.viajes.guias.store', $trip), ['despatch_ids' => [$guia->id]])
            ->assertSessionHas('success');
        $this->assertEquals($trip->id, $guia->fresh()->trip_id);

        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'en_ruta', 'km_inicial' => 1000])
            ->assertSessionHas('success');
        $this->assertNotNull($trip->fresh()->salida_at);

        // Transición inválida.
        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'cancelado'])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'incidencia'])
            ->assertSessionHas('error');
        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'incidencia', 'incidencia' => 'Llanta pinchada'])
            ->assertSessionHas('success');

        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'entregado', 'km_final' => 1085.5])
            ->assertSessionHas('success');

        $this->actingAs($user)->put(route('transporte.viajes.update', $trip), [
            'fecha' => now()->toDateString(),
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'costo_combustible' => 150,
            'costo_peajes' => 25.5,
        ])->assertSessionHas('success');

        $trip->refresh();
        $this->assertEquals('entregado', $trip->estado);
        $this->assertEquals(85.5, $trip->km_recorridos);
        $this->assertEquals(175.5, $trip->costo_total);
        $this->assertStringContainsString('Llanta pinchada', $trip->incidencia);

        // Cerrado: no se quitan guías.
        $this->actingAs($user)
            ->delete(route('transporte.viajes.guias.destroy', [$trip, $guia]))
            ->assertSessionHas('error');

        // Reporte de costos de transporte.
        $this->actingAs($user)
            ->get(route('reportes.show', 'transporte'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('report.rows', 1));

        // No se elimina un vehículo con historial.
        $this->actingAs($user)
            ->delete(route('transporte.vehiculos.destroy', $vehicle))
            ->assertSessionHas('error');
        $this->assertNotNull($vehicle->fresh());
    }

    public function test_trip_rejects_public_transport_guides_and_expired_license(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $vehicle = $this->vehiculo($user);
        $driver = $this->conductor($user);

        $this->actingAs($user)->post(route('guias.store'), $this->guiaPayload([
            'mod_traslado' => '01',
            'transportista_doc' => '20100070970',
            'transportista_nombre' => 'TRANSPORTES DEL SUR SAC',
        ]));
        $publica = Despatch::firstOrFail();

        $this->actingAs($user)->post(route('transporte.viajes.store'), [
            'fecha' => now()->toDateString(),
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);
        $trip = Trip::firstOrFail();

        $this->actingAs($user)
            ->post(route('transporte.viajes.guias.store', $trip), ['despatch_ids' => [$publica->id]])
            ->assertSessionHas('error');
        $this->assertNull($publica->fresh()->trip_id);

        $this->actingAs($user)->post(route('guias.store'), $this->guiaPayload([
            'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]));
        $privada = Despatch::where('mod_traslado', '02')->firstOrFail();
        $this->actingAs($user)->post(route('transporte.viajes.guias.store', $trip), ['despatch_ids' => [$privada->id]]);

        $driver->update(['licencia_vence' => now()->subDay()->toDateString()]);
        $this->actingAs($user)
            ->patch(route('transporte.viajes.estado', $trip), ['estado' => 'en_ruta'])
            ->assertSessionHas('error');
        $this->assertEquals('programado', $trip->fresh()->estado);
    }
}
