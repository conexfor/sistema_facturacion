<?php

namespace App\Services;

use App\Models\BoletaSummary;
use App\Models\Sale;
use App\Services\Sunat\SeeFactory;
use App\Services\Sunat\SummaryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Resumen Diario de Boletas (RC). En producción SUNAT no acepta boletas
 * enviadas individualmente: deben comunicarse mediante este resumen
 * (envío asíncrono que devuelve un ticket y luego un CDR).
 */
class SummaryService
{
    public function __construct(private readonly SummaryBuilder $builder)
    {
    }

    /** Boletas pendientes de incluir en un resumen, para una fecha dada. */
    public function boletasPendientes(Carbon $fecha)
    {
        return Sale::where('tipo_comprobante', 'boleta')
            ->whereDate('fecha', $fecha->toDateString())
            ->whereNull('boleta_summary_id')
            ->where('estado', '!=', 'anulada');
    }

    /** Agrupa las boletas de una fecha en un nuevo resumen. */
    public function generar(Carbon $fecha): BoletaSummary
    {
        return DB::transaction(function () use ($fecha) {
            $boletas = $this->boletasPendientes($fecha)->lockForUpdate()->get();

            if ($boletas->isEmpty()) {
                throw new RuntimeException('No hay boletas pendientes de resumen para esa fecha.');
            }

            $hoy = Carbon::today();
            $correlativo = BoletaSummary::whereDate('fecha_envio', $hoy->toDateString())->count() + 1;

            $summary = BoletaSummary::create([
                'user_id' => Auth::id(),
                'fecha_referencia' => $fecha->toDateString(),
                'fecha_envio' => $hoy->toDateString(),
                'correlativo' => $correlativo,
                'identificador' => 'RC-' . $hoy->format('Ymd') . '-' . $correlativo,
                'estado' => 'pendiente',
                'total_boletas' => $boletas->count(),
            ]);

            Sale::whereIn('id', $boletas->pluck('id'))
                ->update(['boleta_summary_id' => $summary->id]);

            return $summary;
        });
    }

    /** Firma y envía el resumen a SUNAT (o lo simula si no hay credenciales). */
    public function enviar(BoletaSummary $summary): BoletaSummary
    {
        if (! in_array($summary->estado, ['pendiente', 'rechazado'], true)) {
            throw new RuntimeException('Este resumen ya fue enviado a SUNAT.');
        }

        $company = $summary->company;

        if (! $company->sunatConfigurado()) {
            $summary->update([
                'estado' => 'simulado',
                'ticket' => 'SIM-' . now()->timestamp,
                'mensaje' => 'Generado en modo simulación (sin credenciales SUNAT).',
                'sent_at' => now(),
            ]);
            $summary->sales()->update(['sunat_estado' => 'simulado', 'sunat_enviado_at' => now()]);

            return $summary->refresh();
        }

        $doc = $this->builder->build($summary, $company);
        $see = SeeFactory::make($company);

        $xml = $see->getXmlSigned($doc);
        $xmlPath = "sunat/{$company->ruc}/rc/{$doc->getName()}.xml";
        Storage::put($xmlPath, $xml);

        $result = $see->send($doc);

        if (! $result || ! $result->isSuccess()) {
            $error = $result?->getError();
            $summary->update([
                'estado' => 'rechazado',
                'xml_path' => $xmlPath,
                'mensaje' => $error
                    ? trim($error->getCode() . ' - ' . $error->getMessage())
                    : 'SUNAT no aceptó el resumen.',
                'sent_at' => now(),
            ]);

            return $summary->refresh();
        }

        $summary->update([
            'estado' => 'enviado',
            'ticket' => $result->getTicket(),
            'xml_path' => $xmlPath,
            'mensaje' => 'Enviado a SUNAT. Consulta el estado para obtener el CDR.',
            'sent_at' => now(),
        ]);

        return $summary->refresh();
    }

    /** Consulta el estado del resumen en SUNAT usando el ticket. */
    public function consultar(BoletaSummary $summary): BoletaSummary
    {
        if ($summary->estado === 'aceptado') {
            throw new RuntimeException('El resumen ya fue aceptado por SUNAT.');
        }
        if ($summary->estado === 'simulado') {
            return $summary;
        }
        if (! $summary->ticket) {
            throw new RuntimeException('El resumen no tiene ticket; envíalo primero.');
        }

        $company = $summary->company;
        $result = SeeFactory::make($company)->getStatus($summary->ticket);

        $cdr = $result?->getCdrResponse();

        if (! $cdr) {
            $error = $result?->getError();
            $summary->update([
                'mensaje' => $error
                    ? trim($error->getCode() . ' - ' . $error->getMessage())
                    : 'El resumen aún está en proceso en SUNAT. Intenta más tarde.',
            ]);

            return $summary->refresh();
        }

        $cdrPath = null;
        if ($result->getCdrZip()) {
            $cdrPath = "sunat/{$company->ruc}/rc/R-{$summary->identificador}.zip";
            Storage::put($cdrPath, $result->getCdrZip());
        }

        $aceptado = $cdr->getCode() === '0';

        $summary->update([
            'estado' => $aceptado ? 'aceptado' : 'observado',
            'cdr_path' => $cdrPath,
            'mensaje' => $cdr->getDescription() ?? 'Procesado por SUNAT.',
        ]);

        if ($aceptado) {
            $summary->sales()->update([
                'sunat_estado' => 'aceptado',
                'sunat_enviado_at' => now(),
                'sunat_mensaje' => 'Aceptada en resumen ' . $summary->identificador,
            ]);
        }

        return $summary->refresh();
    }
}
