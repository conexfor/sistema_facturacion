<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing(['company', 'branch']);

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $user,
                'company' => $user?->company,
                'branch' => $user?->branch,
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notificaciones' => $this->notificaciones($user),
        ];
    }

    /**
     * Alertas de stock bajo para la campana del topbar. Solo para usuarios
     * con permiso de inventario; el resto no ve la alerta.
     *
     * @return array{count:int, stock_bajo:array<int, array<string, mixed>>}
     */
    private function notificaciones(?\App\Models\User $user): array
    {
        if (! $user || ! $user->can('inventario.gestionar')) {
            return ['count' => 0, 'stock_bajo' => []];
        }

        $bajos = \App\Models\Product::query()
            ->where('controla_stock', true)
            ->where('is_active', true)
            ->withSum('stocks as stock_total', 'cantidad')
            ->get(['id', 'codigo', 'nombre', 'stock_minimo'])
            ->filter(fn ($p) => (float) ($p->stock_total ?? 0) <= (float) $p->stock_minimo)
            ->values();

        return [
            'count' => $bajos->count(),
            'stock_bajo' => $bajos->take(8)->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'stock' => (float) ($p->stock_total ?? 0),
                'minimo' => (float) $p->stock_minimo,
            ])->values()->all(),
        ];
    }
}
