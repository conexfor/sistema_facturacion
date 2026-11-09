<?php

namespace App\Services;

use App\Models\Carrier;
use App\Models\Despatch;
use App\Models\Driver;
use App\Models\Transfer;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Services\Sunat\DespatchBuilder;
use App\Services\Sunat\GreApiSender;
use App\Services\Sunat\SimulatedSender;
use Illuminate\Support\Carbon;
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

        $data = $this->aplicarTransporte($data);
        $this->validarTransferencia($data['transfer_id'] ?? null);

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
     * Copia a la guía los datos del vehículo, conductor o transportista
     * elegidos del catálogo (snapshot) y valida que estén en regla para la
     * fecha de traslado. Deja solo los datos de la modalidad elegida.
     */
    private function aplicarTransporte(array $data): array
    {
        $fechaTraslado = Carbon::parse($data['fecha_traslado'] ?? 'today');

        if (($data['mod_traslado'] ?? '02') === '01') {
            $data['vehicle_id'] = null;
            $data['driver_id'] = null;

            if (! empty($data['carrier_id'])) {
                $carrier = Carrier::findOrFail($data['carrier_id']);
                $data['transportista_doc'] = $carrier->ruc;
                $data['transportista_nombre'] = $carrier->razon_social;
                $data['transportista_mtc'] = $carrier->nro_mtc;
            }

            return $data;
        }

        $data['carrier_id'] = null;

        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            $data['vehiculo_placa'] = $vehicle->placa;

            if ($vehicle->capacidad_kg !== null && (float) ($data['peso_total'] ?? 0) > (float) $vehicle->capacidad_kg) {
                throw new RuntimeException(sprintf(
                    'El peso total (%s kg) supera la capacidad del vehículo %s (%s kg).',
                    (float) $data['peso_total'], $vehicle->placa, (float) $vehicle->capacidad_kg,
                ));
            }
        }

        if (! empty($data['driver_id'])) {
            $driver = Driver::findOrFail($data['driver_id']);

            if ($driver->licenciaVencida($fechaTraslado)) {
                throw new RuntimeException(
                    "La licencia de {$driver->nombre_completo} venció el {$driver->licencia_vence->format('d/m/Y')}.",
                );
            }

            $data['chofer_tipo_doc'] = $driver->tipo_doc;
            $data['chofer_num_doc'] = $driver->num_doc;
            $data['chofer_licencia'] = $driver->licencia;
            $data['chofer_nombres'] = $driver->nombres;
            $data['chofer_apellidos'] = $driver->apellidos;
        }

        return $data;
    }

    /** Una transferencia se ampara con una sola guía. */
    private function validarTransferencia(?int $transferId): void
    {
        if (! $transferId) {
            return;
        }

        $existente = Despatch::where('transfer_id', $transferId)->first();
        if ($existente) {
            throw new RuntimeException("La transferencia ya tiene la guía {$existente->numero}.");
        }
    }

    /**
     * Datos para prellenar una guía de traslado entre establecimientos
     * (motivo 04) a partir de una transferencia de almacén.
     */
    public function prefillDesdeTransferencia(Transfer $transfer): array
    {
        $transfer->loadMissing([
            'origin.branch', 'dest.branch', 'items.product.unit:id,codigo',
        ]);
        $company = $transfer->company;

        $punto = fn (?Warehouse $w) => [
            'ubigeo' => $w?->ubigeo ?: ($w?->branch?->ubigeo ?: $company->ubigeo),
            'direccion' => $w?->direccion ?: ($w?->branch?->direccion ?: $company->direccion),
        ];
        $partida = $punto($transfer->origin);
        $llegada = $punto($transfer->dest);

        return [
            'transfer_id' => $transfer->id,
            'referencia' => "Transferencia #{$transfer->id}: {$transfer->origin?->nombre} → {$transfer->dest?->nombre}",
            'cod_traslado' => '04',
            'des_traslado' => self::MOTIVOS['04'],
            // En el traslado entre establecimientos el destinatario es la propia empresa.
            'dest_tipo_doc' => '6',
            'dest_num_doc' => $company->ruc,
            'dest_nombre' => $company->razon_social,
            'partida_ubigeo' => $partida['ubigeo'] ?? '',
            'partida_direccion' => $partida['direccion'] ?? '',
            'llegada_ubigeo' => $llegada['ubigeo'] ?? '',
            'llegada_direccion' => $llegada['direccion'] ?? '',
            'items' => $transfer->items->map(fn ($it) => [
                'product_id' => $it->product_id,
                'descripcion' => $it->descripcion,
                'cantidad' => (float) $it->cantidad,
                'unidad' => $it->product?->unit?->codigo ?: 'NIU',
            ])->values()->all(),
        ];
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
