<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_admin_can_access_everything(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->get(route('inventario.productos.index'))->assertOk();
        $this->get(route('ventas.pos'))->assertOk();
        $this->get(route('compras.index'))->assertOk();
        $this->get(route('caja.index'))->assertOk();
        $this->get(route('settings.usuarios.index'))->assertOk();
        $this->get(route('settings.roles.index'))->assertOk();
    }

    public function test_seller_is_restricted_by_permissions(): void
    {
        $admin = $this->admin();
        $vendedor = User::factory()->create(['company_id' => $admin->company_id]);
        $vendedor->assignRole('Vendedor');

        $this->actingAs($vendedor);

        // Permitido: ventas
        $this->get(route('ventas.pos'))->assertOk();
        // Denegado: inventario, caja, configuración
        $this->get(route('inventario.productos.index'))->assertForbidden();
        $this->get(route('caja.index'))->assertForbidden();
        $this->get(route('settings.usuarios.index'))->assertForbidden();
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('settings.usuarios.store'), [
            'name' => 'Juan Vendedor',
            'email' => 'juan@erp.test',
            'role' => 'Vendedor',
            'is_active' => true,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'juan@erp.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Vendedor'));
        $this->assertEquals($admin->company_id, $user->company_id);
    }

    public function test_admin_can_create_role_with_permissions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('settings.roles.store'), [
            'name' => 'Supervisor',
            'permissions' => ['dashboard.ver', 'reportes.ver'],
        ])->assertSessionHasNoErrors();

        $role = Role::where('name', 'Supervisor')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('reportes.ver'));
        $this->assertFalse($role->hasPermissionTo('ventas.gestionar'));
    }
}
