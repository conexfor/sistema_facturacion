<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TopbarFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@erp.test')->firstOrFail();
    }

    public function test_settings_hub_renders(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Settings/Index'));
    }

    public function test_low_stock_shared_as_notificaciones(): void
    {
        // El seed deja "Detergente 2kg" (P0004) por debajo de su mínimo.
        $this->actingAs($this->admin());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notificaciones.stock_bajo')
                ->where('notificaciones.count', fn ($count) => $count >= 1));
    }
}
