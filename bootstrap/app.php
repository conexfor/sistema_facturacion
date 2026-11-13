<?php

use App\Support\DatabaseUnavailable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sin conexión a la base de datos: página amigable en vez del error
        // técnico. No se redirige directo al login porque las sesiones viven
        // en la base de datos y el login también fallaría.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! DatabaseUnavailable::matches($e)) {
                return null;
            }

            // Dentro del sistema (Inertia) o en peticiones AJAX: 503 con un
            // mensaje. El frontend muestra un aviso sin salir de la pantalla,
            // así no se pierde el carrito ni lo escrito en formularios
            // (ver app.tsx, evento "invalid").
            if ($request->header('X-Inertia') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Sin conexión con la base de datos. La operación no se completó; '
                        . 'lo que escribiste se mantiene. Intenta de nuevo en unos segundos.',
                ], 503)->header('X-Database-Unavailable', '1');
            }

            return response()->view('errors.database', [
                'codigo' => config('app.debug') ? DatabaseUnavailable::codigo($e) : null,
            ], 503);
        });
    })->create();
