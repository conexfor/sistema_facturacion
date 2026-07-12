<?php

namespace App\Services;

use App\Models\Despatch;
use App\Services\Sunat\DespatchBuilder;
use App\Services\Sunat\GreApiSender;
use App\Services\Sunat\SimulatedSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DespatchService
{
    /** Catálogo 20 SUNAT (motivos de traslado). */
    public const MOTIVOS = [
        '01' => 'Venta',
        '02' => 'Compra',
        '04' => 'Traslado entre establecimientos de la misma empresa',
        '08' => 'Importación',
        '09' => 'Exportación',
        '13' => 'Otros',
        '14' => 'Venta sujeta a confirmación del comprador',
        '18' => 'Traslado emisor itinerante de comprobantes',
    ];

    /** Catálogo 18 SUNAT (modalidad de transporte). */
    public const MODALIDADES = [
        '01' => 'Transporte público',
        '02' => 'Transporte privado',
    ];

    public function __construct(private readonly DespatchBuilder $builder)
    {
    }

    public function registrar(array $data): Despatch
    {
        if (empty($data['items'])) {
            throw new RuntimeException('La guía debe tener al menos un producto.');
        }

        $user = Auth::user();

        return DB::transaction(function () use ($data, $user) {
            $correlativo = (int) Despatch::where('serie', $data['serie'] ?? 'T001')
                ->lockForUpdate()
                ->max('correlativo');
            $correlativo++;

            $despatch = Despatch::create(array_merge(
                collect($data)->except('items')->all(),
                [
                    'company_id' => $user->company_id,
                    'branch_id' => $user->branch_id,
                    'user_id' => $user->id,
                    'serie' => $data['serie'] ?? 'T001',
                    'correlativo' => $correlativo,
                ],
            ));

            foreach ($data['items'] as $item) {
                $despatch->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'unidad' => $item['unidad'] ?? 'NIU',
                ]);
            }

            return $despatch;
        });
    }

    /**
     * Envía la guía a SUNAT por GRE 2.0 (REST/OAuth, asíncrono). Con certificado
     * usa el canal real (simulador en beta); sin certificado, simula el envío.
     * El resultado es un ticket; el CDR se obtiene después con consultar().
     */
    public function emitir(Despatch $despatch): Despatch
    {
        $company = $despatch->company;
        $doc = $this->builder->build($despatch, $company);

        $sender = $company->certificate_path
            ? new GreApiSender($company)
            : new SimulatedSender($company);

        $res = $sender->emitir($doc);

        $name = $doc->getName();
        $ruc = $company->ruc;
        $xmlPath = "sunat/{$ruc}/xml/{$name}.xml";
        Storage::put($xmlPath, $res['xml']);

        $cdrPath = null;
        if (! empty($res['cdrZip'])) {
            $cdrPath = "sunat/{$ruc}/cdr/R-{$name}.zip";
            Storage::put($cdrPath, $res['cdrZip']);
        }

        $despatch->update([
            'sunat_estado' => $res['estado'],
            'sunat_ticket' => $res['ticket'] ?? null,
            'sunat_xml_path' => $xmlPath,
            'sunat_cdr_path' => $cdrPath,
            'sunat_hash' => $res['hash'],
            'sunat_mensaje' => $res['mensaje'],
            'sunat_enviado_at' => now(),
        ]);

        return $despatch->refresh();
    }

    /**
     * Consulta el estado del ticket en SUNAT y, si ya terminó de procesarse,
     * descarga el CDR y actualiza el estado de la guía.
     */
    public function consultar(Despatch $despatch): Despatch
    {
        if (empty($despatch->sunat_ticket)) {
            throw new RuntimeException('La guía no tiene un ticket de envío para consultar.');
        }

        $company = $despatch->company;
        $res = (new GreApiSender($company))->consultar($despatch->sunat_ticket);

        $cdrPath = $despatch->sunat_cdr_path;
        if (! empty($res['cdrZip'])) {
            $name = pathinfo((string) $despatch->sunat_xml_path, PATHINFO_FILENAME)
                ?: ($company->ruc . '-09-' . $despatch->serie . '-' . $despatch->correlativo);
            $cdrPath = "sunat/{$company->ruc}/cdr/R-{$name}.zip";
            Storage::put($cdrPath, $res['cdrZip']);
        }

        $despatch->update([
            'sunat_estado' => $res['estado'],
            'sunat_cdr_path' => $cdrPath,
            'sunat_mensaje' => $res['mensaje'],
        ]);

        return $despatch->refresh();
    }
}
