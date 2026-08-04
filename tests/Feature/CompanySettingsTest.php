<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_company_edit_page_renders(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->get(route('settings.empresa.edit'))->assertOk();
    }

    public function test_update_ruc_y_razon_social(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('settings.empresa.update'), [
            'ruc' => '20600123456',
            'razon_social' => 'MI EMPRESA REAL S.A.C.',
            'nombre_comercial' => 'Mi Empresa',
            'moneda' => 'PEN',
            'igv_porcentaje' => 18,
        ])->assertSessionHas('success');

        $company = $user->fresh()->company;
        $this->assertEquals('20600123456', $company->ruc);
        $this->assertEquals('MI EMPRESA REAL S.A.C.', $company->razon_social);
    }

    public function test_ruc_debe_tener_11_digitos(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('settings.empresa.update'), [
            'ruc' => '123',
            'razon_social' => 'X',
            'moneda' => 'PEN',
            'igv_porcentaje' => 18,
        ])->assertSessionHasErrors('ruc');
    }
}
