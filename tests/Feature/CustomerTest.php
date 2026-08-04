<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_ruc_debe_tener_11_digitos(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('clientes.store'), [
            'tipo_documento' => '6',
            'numero_documento' => '123',
            'razon_social' => 'Empresa Prueba',
        ])->assertSessionHasErrors('numero_documento');

        $this->assertEquals(0, Customer::where('razon_social', 'Empresa Prueba')->count());
    }

    public function test_dni_debe_tener_8_digitos(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('clientes.store'), [
            'tipo_documento' => '1',
            'numero_documento' => '999',
            'razon_social' => 'Juan Perez',
        ])->assertSessionHasErrors('numero_documento');

        $this->assertEquals(0, Customer::where('razon_social', 'Juan Perez')->count());
    }

    public function test_ruc_y_dni_validos_se_guardan(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('clientes.store'), [
            'tipo_documento' => '6',
            'numero_documento' => '20600123456',
            'razon_social' => 'Empresa Real S.A.C.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'numero_documento' => '20600123456',
            'razon_social' => 'Empresa Real S.A.C.',
        ]);
    }

    public function test_destroy_desactiva_en_vez_de_borrar(): void
    {
        $user = $this->admin();
        $customer = Customer::where('tipo_documento', '1')->firstOrFail();

        $this->actingAs($user)->delete(route('clientes.destroy', $customer->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);
    }
}
