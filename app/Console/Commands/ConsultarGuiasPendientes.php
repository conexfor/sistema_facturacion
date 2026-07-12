<?php

namespace App\Console\Commands;

use App\Models\Despatch;
use App\Services\DespatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Consulta en SUNAT el estado (CDR) de las guías de remisión que fueron
 * enviadas por GRE 2.0 y quedaron "en proceso". La emisión de GRE es asíncrona:
 * primero se obtiene un ticket y el CDR llega después, por eso conviene
 * consultar automáticamente en lugar de depender de que el usuario lo haga.
 */
class ConsultarGuiasPendientes extends Command
{
    protected $signature = 'guias:consultar-pendientes {--limit=50 : Máximo de guías a procesar por corrida}';

    protected $description = 'Consulta el CDR de las guías de remisión enviadas que siguen en proceso';

    public function handle(DespatchService $despatches): int
    {
        $pendientes = Despatch::query()
            ->withoutGlobalScopes()
            ->where('sunat_estado', 'enviado')
            ->whereNotNull('sunat_ticket')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay guías pendientes de consultar.');

            return self::SUCCESS;
        }

        $ok = 0;
        $err = 0;

        foreach ($pendientes as $despatch) {
            try {
                $despatch = $despatches->consultar($despatch);
                $this->line("Guía {$despatch->numero}: {$despatch->sunat_estado} — {$despatch->sunat_mensaje}");
                $ok++;
            } catch (Throwable $e) {
                $err++;
                Log::warning('Consulta automática de guía falló', [
                    'despatch_id' => $despatch->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Guía {$despatch->numero}: {$e->getMessage()}");
            }
        }

        $this->info("Procesadas: {$pendientes->count()} · actualizadas: {$ok} · con error: {$err}.");

        return self::SUCCESS;
    }
}
