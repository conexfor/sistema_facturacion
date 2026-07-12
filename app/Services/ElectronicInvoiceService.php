<?php

namespace App\Services;

use App\Models\Sale;
use App\Services\Sunat\GreenterSender;
use App\Services\Sunat\InvoiceBuilder;
use App\Services\Sunat\SimulatedSender;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ElectronicInvoiceService
{
    public function __construct(private readonly InvoiceBuilder $builder)
    {
    }

    /**
     * Emite (o reintenta) el comprobante electrónico de una venta.
     */
    public function emitir(Sale $sale): Sale
    {
        if (! in_array($sale->tipo_comprobante, ['boleta', 'factura'], true)) {
            throw new RuntimeException('Solo las boletas y facturas se emiten ante SUNAT.');
        }
        if ($sale->sunat_estado === 'aceptado') {
            throw new RuntimeException('El comprobante ya fue aceptado por SUNAT.');
        }

        $company = $sale->company;
        $this->validar($sale, $company);
        $invoice = $this->builder->build($sale, $company);

        $sender = $company->sunatConfigurado()
            ? new GreenterSender($company)
            : new SimulatedSender($company);

        $res = $sender->emitir($invoice);

        $name = $invoice->getName(); // p.ej. 20123456789-01-F001-1
        $ruc = $company->ruc;

        $xmlPath = "sunat/{$ruc}/xml/{$name}.xml";
        Storage::put($xmlPath, $res['xml']);

        $cdrPath = null;
        if (! empty($res['cdrZip'])) {
            $cdrPath = "sunat/{$ruc}/cdr/R-{$name}.zip";
            Storage::put($cdrPath, $res['cdrZip']);
        }

        $sale->update([
            'sunat_estado' => $res['estado'],
            'sunat_xml_path' => $xmlPath,
            'sunat_cdr_path' => $cdrPath,
            'sunat_hash' => $res['hash'],
            'sunat_mensaje' => $res['mensaje'],
            'sunat_enviado_at' => now(),
        ]);

        return $sale->refresh();
    }

    /**
     * Validaciones que evitan rechazos de SUNAT antes de emitir.
     */
    private function validar(Sale $sale, \App\Models\Company $company): void
    {
        if ($company->sunat_modo === 'produccion' && (empty($company->ubigeo) || empty($company->direccion))) {
            throw new RuntimeException('Completa el ubigeo y la dirección de la empresa en Configuración SUNAT antes de emitir en producción.');
        }

        $sale->loadMissing('customer');
        $tipoDocCliente = $sale->customer?->tipo_documento;
        $identificado = ! empty($sale->cliente_documento)
            && $tipoDocCliente
            && $tipoDocCliente !== '0';

        if ($sale->tipo_comprobante === 'factura' && $tipoDocCliente !== '6') {
            throw new RuntimeException('Las facturas requieren un cliente con RUC.');
        }

        if ($sale->tipo_comprobante === 'boleta' && (float) $sale->total > 700 && ! $identificado) {
            throw new RuntimeException('Las boletas mayores a S/ 700 deben identificar al cliente con DNI o RUC.');
        }
    }
}
