<?php

namespace App\Services;

use App\Models\Despatch;
use App\Models\Note;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Sunat\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Genera la representación impresa de los comprobantes —en A4 (PDF) y en
 * ticket térmico (80 mm)— de ventas (factura/boleta), notas de crédito/débito,
 * guías de remisión y registros de compra, con QR SUNAT, firma digital (hash),
 * datos completos de emisor/receptor/operación, desglose de IGV y percepción.
 *
 * El parámetro $formato ('a4' | 'ticket') selecciona la plantilla y el tamaño
 * de papel; el resto del mapeo de datos es compartido para que ambos formatos
 * muestren exactamente la misma información obligatoria.
 */
class DocumentPdfService
{
    private const TIPO_VENTA = ['boleta' => 'BOLETA DE VENTA', 'factura' => 'FACTURA', 'nota_venta' => 'NOTA DE VENTA'];
    private const TIPO_CODE = ['boleta' => '03', 'factura' => '01'];
    private const DOC_LABEL = ['1' => 'DNI', '6' => 'RUC', '7' => 'Pasaporte', '4' => 'C.E.', '0' => 'Sin documento'];
    private const FORMA_PAGO = ['credito' => 'Crédito', 'efectivo' => 'Contado (efectivo)'];

    /** Catálogo 20 SUNAT (motivos de traslado). */
    private const MOTIVOS = [
        '01' => 'Venta', '02' => 'Compra',
        '04' => 'Traslado entre establecimientos de la misma empresa',
        '08' => 'Importación', '09' => 'Exportación', '13' => 'Otros',
        '14' => 'Venta sujeta a confirmación del comprador',
        '18' => 'Traslado emisor itinerante de comprobantes',
    ];

    public function sale(Sale $sale, string $formato = 'a4'): Response
    {
        $sale->loadMissing(['items.product:id,codigo,unit_id', 'items.product.unit:id,abreviatura', 'customer', 'company', 'user']);
        $company = $sale->company;
        $electronico = in_array($sale->sunat_estado, ['aceptado', 'simulado', 'observado'], true);
        $tipoCode = self::TIPO_CODE[$sale->tipo_comprobante] ?? '';
        $tipoDocReceptor = $sale->customer?->tipo_documento ?? '0';

        $qr = $electronico ? $this->qr([
            $company->ruc, $tipoCode, $sale->serie, $sale->correlativo,
            number_format((float) $sale->igv, 2, '.', ''),
            number_format((float) $sale->total, 2, '.', ''),
            Carbon::parse($sale->fecha)->format('Y-m-d'),
            $tipoDocReceptor, $sale->cliente_documento ?: '-', $sale->sunat_hash ?: '-',
        ]) : null;

        return $this->render([
            'layout' => 'comprobante',
            'titulo' => (self::TIPO_VENTA[$sale->tipo_comprobante] ?? 'COMPROBANTE') . ($electronico ? ' ELECTRÓNICA' : ''),
            'numero' => $sale->numero,
            'emisor' => $this->emisor($company),
            'receptorLabel' => 'Cliente',
            'receptor' => [
                'tipoLabel' => self::DOC_LABEL[$tipoDocReceptor] ?? 'Doc.',
                'doc' => $sale->cliente_documento,
                'nombre' => $sale->cliente_nombre,
                'direccion' => $sale->cliente_direccion ?: $sale->customer?->direccion,
            ],
            'operacion' => [
                'fecha' => Carbon::parse($sale->fecha)->format('d/m/Y H:i'),
                'moneda' => $sale->moneda === 'USD' ? 'Dólares (USD)' : 'Soles (PEN)',
                'formaPago' => self::FORMA_PAGO[$sale->metodo_pago] ?? ('Contado (' . $sale->metodo_pago . ')'),
                'vendedor' => $sale->user->name ?? '—',
            ],
            'moneda' => $this->simbolo($sale->moneda),
            'items' => $this->items($sale->items, 'precio_unitario'),
            'montos' => $this->montos($sale->items, (float) $sale->subtotal),
            'igvPct' => (float) ($company->igv_porcentaje ?? 18),
            'subtotal' => (float) $sale->subtotal,
            'igv' => (float) $sale->igv,
            'descuento' => (float) $sale->descuento_total,
            'total' => (float) $sale->total,
            'percepcion' => (float) ($sale->percepcion_monto ?? 0),
            'percepcionTasa' => (float) ($sale->percepcion_tasa ?? 0),
            'enLetras' => NumberToWords::montoEnLetras((float) $sale->total, $this->palabraMoneda($sale->moneda)),
            'hash' => $electronico ? $sale->sunat_hash : null,
            'estado' => $electronico ? ucfirst($sale->sunat_estado) : null,
            'qr' => $qr,
            'anulado' => $sale->estado === 'anulada',
        ], $formato);
    }

    public function note(Note $note, string $formato = 'a4'): Response
    {
        $note->loadMissing(['items.product:id,codigo,unit_id', 'items.product.unit:id,abreviatura', 'company', 'sale.customer']);
        $company = $note->company;
        $sale = $note->sale;
        $tipoDocReceptor = $sale?->customer?->tipo_documento ?? '0';
        $electronico = in_array($note->sunat_estado, ['aceptado', 'simulado', 'observado'], true);

        $qr = $electronico ? $this->qr([
            $company->ruc, $note->tipo_doc, $note->serie, $note->correlativo,
            number_format((float) $note->igv, 2, '.', ''),
            number_format((float) $note->total, 2, '.', ''),
            Carbon::parse($note->fecha)->format('Y-m-d'),
            $tipoDocReceptor, $sale?->cliente_documento ?: '-', $note->sunat_hash ?: '-',
        ]) : null;

        return $this->render([
            'layout' => 'comprobante',
            'titulo' => ($note->tipo === 'credito' ? 'NOTA DE CRÉDITO' : 'NOTA DE DÉBITO') . ' ELECTRÓNICA',
            'numero' => $note->numero,
            'emisor' => $this->emisor($company),
            'receptorLabel' => 'Cliente',
            'receptor' => [
                'tipoLabel' => self::DOC_LABEL[$tipoDocReceptor] ?? 'Doc.',
                'doc' => $sale?->cliente_documento,
                'nombre' => $sale?->cliente_nombre ?? 'VARIOS',
                'direccion' => $sale?->cliente_direccion ?: $sale?->customer?->direccion,
            ],
            'operacion' => [
                'fecha' => Carbon::parse($note->fecha)->format('d/m/Y H:i'),
                'moneda' => $note->moneda === 'USD' ? 'Dólares (USD)' : 'Soles (PEN)',
                'documentoAfectado' => $sale ? strtoupper($sale->tipo_comprobante) . ' ' . $sale->serie . '-' . $sale->correlativo : '—',
                'motivo' => $note->des_motivo,
            ],
            'moneda' => $this->simbolo($note->moneda),
            'items' => $this->items($note->items, 'precio_unitario'),
            'montos' => $this->montos($note->items, (float) $note->subtotal),
            'igvPct' => (float) ($company->igv_porcentaje ?? 18),
            'subtotal' => (float) $note->subtotal,
            'igv' => (float) $note->igv,
            'descuento' => 0.0,
            'total' => (float) $note->total,
            'percepcion' => 0.0,
            'percepcionTasa' => 0.0,
            'enLetras' => NumberToWords::montoEnLetras((float) $note->total, $this->palabraMoneda($note->moneda)),
            'hash' => $electronico ? $note->sunat_hash : null,
            'estado' => $electronico ? ucfirst($note->sunat_estado) : null,
            'qr' => $qr,
            'anulado' => false,
        ], $formato);
    }

    public function purchase(Purchase $purchase, string $formato = 'a4'): Response
    {
        $purchase->loadMissing(['items.product:id,codigo,unit_id', 'items.product.unit:id,abreviatura', 'supplier', 'company']);
        $company = $purchase->company;

        return $this->render([
            'layout' => 'comprobante',
            'titulo' => 'REGISTRO DE COMPRA',
            'numero' => $purchase->numero_completo ?: ('#' . $purchase->id),
            'emisor' => $this->emisor($company),
            'receptorLabel' => 'Proveedor',
            'receptor' => [
                'tipoLabel' => 'RUC',
                'doc' => $purchase->supplier?->numero_documento,
                'nombre' => $purchase->supplier?->razon_social ?? 'Sin proveedor',
                'direccion' => $purchase->supplier?->direccion,
            ],
            'operacion' => [
                'fecha' => Carbon::parse($purchase->fecha)->format('d/m/Y'),
                'moneda' => $purchase->moneda === 'USD' ? 'Dólares (USD)' : 'Soles (PEN)',
                'comprobante' => strtoupper($purchase->tipo_comprobante) . ' ' . ($purchase->numero_completo ?: '—'),
                'estadoPago' => ucfirst($purchase->estado_pago),
            ],
            'moneda' => $this->simbolo($purchase->moneda),
            'items' => $this->items($purchase->items, 'costo_unitario'),
            'montos' => $this->montos($purchase->items, (float) $purchase->subtotal),
            'igvPct' => (float) ($company->igv_porcentaje ?? 18),
            'subtotal' => (float) $purchase->subtotal,
            'igv' => (float) $purchase->igv,
            'descuento' => 0.0,
            'total' => (float) $purchase->total,
            'percepcion' => (float) ($purchase->percepcion_monto ?? 0),
            'percepcionTasa' => (float) ($purchase->percepcion_tasa ?? 0),
            'enLetras' => NumberToWords::montoEnLetras((float) $purchase->total, $this->palabraMoneda($purchase->moneda)),
            'hash' => null,
            'estado' => null,
            'qr' => null,
            'anulado' => false,
        ], $formato);
    }

    /**
     * Representación impresa de la Guía de Remisión Electrónica (remitente).
     * A diferencia del comprobante, no lleva montos: muestra motivo/modalidad
     * de traslado, puntos de partida y llegada, peso/bultos, datos de transporte
     * (transportista o vehículo+chofer) y el documento relacionado.
     */
    public function despatch(Despatch $despatch, string $formato = 'a4'): Response
    {
        $despatch->loadMissing([
            'items.product:id,codigo,unit_id', 'items.product.unit:id,abreviatura', 'company', 'user', 'sale',
        ]);
        $company = $despatch->company;
        $electronico = in_array($despatch->sunat_estado, ['aceptado', 'simulado', 'observado', 'enviado'], true);

        // Estructura de representación impresa SUNAT (Anexo VIII): 10 campos.
        // En la guía de remisión (tipo 09) el IGV y el total van en 0.00.
        // QR de la Guía de Remisión Electrónica (GRE). Estructura documentada por
        // SUNAT para la representación impresa (sin montos): RUC | tipo(09) | serie |
        // correlativo | fecha emisión | tipo doc. destinatario | núm. doc. destinatario.
        $qr = $electronico ? $this->qr([
            $company->ruc, '09', $despatch->serie, str_pad((string) $despatch->correlativo, 8, '0', STR_PAD_LEFT),
            Carbon::parse($despatch->fecha_emision)->format('Y-m-d'),
            $despatch->dest_tipo_doc, $despatch->dest_num_doc ?: '-',
        ]) : null;

        $publico = $despatch->mod_traslado === '01';
        $transporte = $publico
            ? [
                'Modalidad' => 'Transporte público',
                'Transportista' => $despatch->transportista_nombre,
                'RUC transportista' => $despatch->transportista_doc,
                'Reg. MTC' => $despatch->transportista_mtc,
            ]
            : [
                'Modalidad' => 'Transporte privado',
                'Placa del vehículo' => $despatch->vehiculo_placa,
                'Conductor' => trim(($despatch->chofer_nombres ?? '') . ' ' . ($despatch->chofer_apellidos ?? '')) ?: null,
                'DNI conductor' => $despatch->chofer_num_doc,
                'Licencia' => $despatch->chofer_licencia,
            ];

        return $this->render([
            'layout' => 'guia',
            'titulo' => 'GUÍA DE REMISIÓN ELECTRÓNICA',
            'subtitulo' => 'REMITENTE',
            'numero' => $despatch->numero,
            'emisor' => $this->emisor($company),
            'receptorLabel' => 'Destinatario',
            'receptor' => [
                'tipoLabel' => self::DOC_LABEL[$despatch->dest_tipo_doc] ?? 'Doc.',
                'doc' => $despatch->dest_num_doc,
                'nombre' => $despatch->dest_nombre,
                'direccion' => $despatch->llegada_direccion,
            ],
            'traslado' => array_filter([
                'Motivo' => self::MOTIVOS[$despatch->cod_traslado] ?? $despatch->des_traslado,
                'Descripción' => $despatch->des_traslado,
                'F. emisión' => Carbon::parse($despatch->fecha_emision)->format('d/m/Y'),
                'F. traslado' => Carbon::parse($despatch->fecha_traslado)->format('d/m/Y'),
                'Peso bruto' => rtrim(rtrim(number_format((float) $despatch->peso_total, 3), '0'), '.') . ' ' . ($despatch->und_peso ?: 'KGM'),
                'N.° de bultos' => (string) $despatch->num_bultos,
                'Doc. relacionado' => $despatch->sale ? strtoupper($despatch->sale->tipo_comprobante) . ' ' . $despatch->sale->numero : null,
            ], fn ($v) => $v !== null && $v !== ''),
            'partida' => trim(($despatch->partida_ubigeo ? '(' . $despatch->partida_ubigeo . ') ' : '') . $despatch->partida_direccion),
            'llegada' => trim(($despatch->llegada_ubigeo ? '(' . $despatch->llegada_ubigeo . ') ' : '') . $despatch->llegada_direccion),
            'transporte' => array_filter($transporte, fn ($v) => $v !== null && $v !== ''),
            'items' => $this->despatchItems($despatch->items),
            'observacion' => $despatch->observacion,
            'hash' => $electronico ? $despatch->sunat_hash : null,
            'estado' => $electronico ? ucfirst($despatch->sunat_estado) : null,
            'qr' => $qr,
            'anulado' => false,
        ], $formato);
    }

    /**
     * Desglosa las bases imponibles por tipo de afectación del IGV
     * (Catálogo 07 SUNAT: 10=Gravado, 20=Exonerado, 30=Inafecto) para
     * mostrarlas por separado en la representación impresa. Si los ítems
     * no traen el desglose (p. ej. compras), cae al subtotal como gravado.
     */
    private function montos($items, float $subtotalFallback): array
    {
        $gravadas = 0.0;
        $exoneradas = 0.0;
        $inafectas = 0.0;

        foreach ($items as $i) {
            $base = (float) ($i->base ?? 0);
            $tipo = $i->tipo_afectacion ?? (($i->afecto_igv ?? true) ? '10' : '30');

            match ($tipo) {
                '20' => $exoneradas += $base,
                '30' => $inafectas += $base,
                default => $gravadas += $base,
            };
        }

        // Sin desglose disponible: todo se asume gravado.
        if ($gravadas + $exoneradas + $inafectas <= 0) {
            $gravadas = $subtotalFallback;
        }

        return [
            'gravadas' => round($gravadas, 2),
            'exoneradas' => round($exoneradas, 2),
            'inafectas' => round($inafectas, 2),
        ];
    }

    private function items($items, string $precioField): array
    {
        return $items->map(fn ($i) => [
            'codigo' => $i->product?->codigo ?? ($i->product_id ?? '—'),
            'descripcion' => $i->descripcion,
            'cantidad' => (float) $i->cantidad,
            'unidad' => $i->product?->unit?->abreviatura ?: 'UND',
            'precio' => (float) $i->{$precioField},
            'total' => (float) $i->total,
        ])->all();
    }

    private function despatchItems($items): array
    {
        return $items->map(fn ($i) => [
            'codigo' => $i->product?->codigo ?? ($i->product_id ?? '—'),
            'descripcion' => $i->descripcion,
            'cantidad' => (float) $i->cantidad,
            'unidad' => $i->unidad ?: ($i->product?->unit?->abreviatura ?: 'NIU'),
        ])->all();
    }

    private function emisor($company): array
    {
        return [
            'ruc' => $company->ruc,
            'razon_social' => $company->razon_social,
            'nombre_comercial' => $company->nombre_comercial,
            'direccion' => $company->direccion,
            'ubicacion' => trim(implode(' - ', array_filter([$company->distrito, $company->provincia, $company->departamento]))),
            'telefono' => $company->telefono,
            'email' => $company->email,
            'logo' => $this->logoDataUri($company),
        ];
    }

    /**
     * Devuelve el logo de la empresa como data URI para incrustarlo en el PDF
     * (DomPDF bloquea imágenes remotas). Busca el archivo en el disco de
     * almacenamiento y luego en public/. Ante cualquier problema devuelve null.
     */
    private function logoDataUri($company): ?string
    {
        $path = $company->logo_path ?? null;
        if (empty($path)) {
            return null;
        }

        try {
            $binario = null;

            if (\Illuminate\Support\Facades\Storage::exists($path)) {
                $binario = \Illuminate\Support\Facades\Storage::get($path);
            } elseif (is_file(public_path($path))) {
                $binario = file_get_contents(public_path($path));
            } elseif (is_file($path)) {
                $binario = file_get_contents($path);
            }

            if ($binario === null || $binario === false || $binario === '') {
                return null;
            }

            $tipo = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:' . $tipo . ';base64,' . base64_encode($binario);
        } catch (Throwable) {
            return null;
        }
    }

    private function qr(array $parts): ?string
    {
        try {
            return (new Builder(
                data: implode('|', $parts),
                size: 150,
                margin: 2,
            ))->build()->getDataUri();
        } catch (Throwable) {
            return null;
        }
    }

    private function simbolo(?string $moneda): string
    {
        return $moneda === 'USD' ? '$' : 'S/';
    }

    private function palabraMoneda(?string $moneda): string
    {
        return $moneda === 'USD' ? 'DÓLARES' : 'SOLES';
    }

    private function render(array $data, string $formato = 'a4'): Response
    {
        $data['formato'] = $formato;
        $data += ['subtitulo' => null];
        $numero = str_replace(['/', ' '], '-', (string) $data['numero']);

        if ($formato === 'ticket') {
            $itemCount = is_countable($data['items'] ?? null) ? count($data['items']) : 0;
            $extra = ($data['layout'] ?? 'comprobante') === 'guia' ? 560 : 500;
            $height = $extra + ($itemCount * 26);

            return Pdf::loadView('documents.ticket', $data)
                ->setPaper([0, 0, 226.772, $height]) // 80 mm de ancho, alto estimado por contenido
                ->stream($numero . '-ticket.pdf');
        }

        $view = ($data['layout'] ?? 'comprobante') === 'guia' ? 'documents.guia' : 'documents.comprobante';

        return Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->stream($numero . '.pdf');
    }
}
