<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /** Hub de configuración: la página muestra las secciones según permisos. */
    public function index(): Response
    {
        return Inertia::render('Settings/Index');
    }
}
