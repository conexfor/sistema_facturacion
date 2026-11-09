<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Crea el permiso del módulo de transporte en bases ya sembradas y lo
     * asigna a Administrador y Vendedor sin tocar el resto de permisos que
     * se hayan editado desde la pantalla de roles.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permiso = Permission::firstOrCreate(['name' => 'transporte.gestionar', 'guard_name' => 'web']);

        Role::whereIn('name', ['Administrador', 'Vendedor'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permiso));
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('name', 'transporte.gestionar')->where('guard_name', 'web')->delete();
    }
};
