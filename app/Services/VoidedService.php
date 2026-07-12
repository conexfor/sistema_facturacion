<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\VoidedDocument;
use App\Models\Warehouse;
use App\Services\Sunat\SeeFactory;
use App\Services\Sunat\VoidedBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Comunicación de Baja (RA): anula ante SUNAT una factura emitida por error,
 * dentro de los 7 días. Envío asíncrono (ticket) como el resumen diario.
 */
class VoidedService
{
    public function __construct(
        private readonly VoidedBuilder $builder,
        private readonly InventoryService $inventory,
    ) {
    }

    /** Crea la comunicación de baja de una factura y la envía a SUNAT. */
    public function anular(Sale $sale, string $motivo): VoidedDocument
    {
        if ($sale->tipo_comprobante !== 'factura') {
            throw new RuntimeException('La comunicación de baja aplica a facturas. Las boletas se anulan por resumen.');
        }
        if (! in_array($sale->sunat_estado, ['aceptado', 'simulado', 'observado'], true)) {
            throw new RuntimeException('Solo se pueden dar de baja comprobantes ya emitidos a SUNAT.');
        }
        if ($sale->estado === 'anulada') {
            throw new RuntimeException('La venta ya está anulada.');
        }
        if (Carbon::parse($sale->fecha)->lt(Carbon::today()->subDays(7))) {
            throw new RuntimeException('La comunicación de baja solo procede dentro de los 7 días de emitido el comprobante.');
        }
        $existe = VoidedDocument::where('sale_id', $sale->id)
            ->whereIn('estado', ['pendiente', 'enviado', 'aceptado', 'simulado'])
            ->exists();
        if ($existe) {
            throw new RuntimeException('Ya existe una comunicación de baja para este comprobante.');
        }

        $voided = DB::transaction(function () use ($sale, $motivo) {
            $hoy = Carbon::today();
            $correlativo = VoidedDocument::whereDate('fecha_comunicacion', $hoy->toDateString())->count() + 1;

            return VoidedDocument::create([
                'user_id' => Auth::id(),
                'sale_id' => $sale->id,
                'fecha_referencia' => Carbon::parse($sale->fecha)->toDateString(),
                'fecha_comunicacion' => $hoy->toDateString(),
                'correlativo' => $correlativo,
                'identificador' => 'RA-' . $hoy->format('Ymd') . '-' . $correlativo,
                'motivo' => $motivo,
                'estado' => 'pendiente',
            ]);
        });

        return $this->enviar($voided);
    }

    public function enviar(VoidedDocument $voided): VoidedDocument
    {
        if (! in_array($voided->estado, ['pendiente', 'rechazado'], true)) {
            throw new RuntimeException('Esta comunicación ya fue enviada.');
        }

        $company = $voided->company;

        if (! $company->sunatConfigurado()) {
            $voided->update([
                'estado' => 'simulado',
                'ticket' => 'SIM-' . now()->timestamp,
                'mensaje' => 'Baja generada en modo simulación.',
                'sent_at' => now(),
            ]);
            $this->aplicarAnulacion($voided->sale, $voided->identificador);

            return $voided->refresh();
        }

        $doc = $this->builder->build($voided, $company);
        $see = SeeFactory::make($company);

        $xml = $see->getXmlSigned($doc);
        $xmlPath = "sunat/{$company->ruc}/ra/{$doc->getName()}.xml";
        Storage::put($xmlPath, $xml);

        $result = $see->send($doc);

        if (! $result || ! $result->isSuccess()) {
            $error = $result?->getError();
            $voided->update([
                'estado' => 'rechazado',
                'xml_path' => $xmlPath,
                'mensaje' => $error ? trim($error->getCode() . ' - ' . $error->getMessage()) : 'SUNAT rechazó la baja.',
                'sent_at' => now(),
            ]);

            return $voided->refresh();
        }

        $voided->update([
            'estado' => 'enviado',
            'ticket' => $result->getTicket(),
            'xml_path' => $xmlPath,
            'mensaje' => 'Enviada a SUNAT. Consulta el estado para obtener el CDR.',
            'sent_at' => now(),
        ]);

        return $voided->refresh();
    }

    public function consultar(VoidedDocument $voided): VoidedDocument
    {
        if ($voided->estado === 'aceptado') {
            throw new RuntimeException('La baja ya fue aceptada por SUNAT.');
        }
        if ($voided->estado === 'simulado') {
            return $voided;
        }
        if (! $voided->ticket) {
            throw new RuntimeException('La comunicación no tiene ticket; envíala primero.');
        }

        $company = $voided->company;
        $result = SeeFactory::make($company)->getStatus($voided->ticket);
        $cdr = $result?->getCdrResponse();

        if (! $cdr) {
            $error = $result?->getError();
            $voided->update([
                'mensaje' => $error ? trim($error->getCode() . ' - ' . $error->getMessage()) : 'En proceso en SUNAT.',
            ]);

            return $voided->refresh();
        }

        $cdrPath = null;
        if ($result->getCdrZip()) {
            $cdrPath = "sunat/{$company->ruc}/ra/R-{$voided->identificador}.zip";
            Storage::put($cdrPath, $result->getCdrZip());
        }

        $aceptado = $cdr->getCode() === '0';
        $voided->update([
            'estado' => $aceptado ? 'aceptado' : 'observado',
            'cdr_path' => $cdrPath,
            'mensaje' => $cdr->getDescription() ?? 'Procesado por SUNAT.',
        ]);

        if ($aceptado) {
            $this->aplicarAnulacion($voided->sale, $voided->identificador);
        }

        return $voided->refresh();
    }

    /** Anula la venta y devuelve el stock (una sola vez). */
    private function aplicarAnulacion(Sale $sale, string $referencia): void
    {
        $sale = Sale::lockForUpdate()->findOrFail($sale->id);
        if ($sale->estado === 'anulada') {
            return;
        }

        $sale->loadMissing('items');
        $warehouse = $sale->warehouse_id ? Warehouse::find($sale->warehouse_id) : null;

        if ($warehouse) {
            foreach ($sale->items as $item) {
                if (! $item->product_id) {
                    continue;
                }
                $product = \App\Models\Product::find($item->product_id);
                if ($product && $product->controla_stock) {
                    $this->inventory->entrada(
                        $product,
                        $warehouse,
                        (float) $item->cantidad,
                        (float) $item->costo_unitario,
                        'Baja factura ' . $sale->numero,
                        $referencia,
                    );
                }
            }
        }

        $sale->update(['estado' => 'anulada', 'sunat_estado' => 'anulado']);
    }
}
