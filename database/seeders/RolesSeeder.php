<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /** Permisos por módulo: nombre => etiqueta legible. */
    public const PERMISOS = [
        'dashboard.ver' => 'Ver dashboard',
        'inventario.gestionar' => 'Gestionar inventario',
        'ventas.gestionar' => 'Gestionar ventas y clientes',
        'compras.gestionar' => 'Gestionar compras y proveedores',
        'caja.gestionar' => 'Gestionar caja y finanzas',
        'reportes.ver' => 'Ver reportes',
        'usuarios.gestionar' => 'Gestionar usuarios y roles',
        'configuracion.gestionar' => 'Gestionar configuración',
    ];

    /** Roles predefinidos con sus permisos. */
    public const ROLES = [
        'Administrador' => ['*'],
        'Vendedor' => ['dashboard.ver', 'ventas.gestionar'],
        'Almacenero' => ['dashboard.ver', 'inventario.gestionar', 'compras.gestionar'],
        'Cajero' => ['dashboard.ver', 'ventas.gestionar', 'caja.gestionar'],
        'Contador' => ['dashboard.ver', 'caja.gestionar', 'compras.gestionar', 'reportes.ver'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISOS) as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $nombre => $permisos) {
            $role = Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
            $asignar = $permisos === ['*']
                ? array_keys(self::PERMISOS)
                : $permisos;
            $role->syncPermissions($asignar);
        }
    }
}
