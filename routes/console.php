<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Consulta automática del CDR de guías de remisión en proceso (GRE es asíncrono).
Schedule::command('guias:consultar-pendientes')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();
