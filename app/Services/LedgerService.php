<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Note;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Carbon;

/**
 * Genera los libros electrónicos en formato PLE (texto, campos separados por
 * "|"): Registro de Ventas e Ingresos (14.1) y Registro de Compras (8.1).
 *
 * Es una base compatible con la estructura PLE; cubre los campos principales.
 */
class LedgerService
{
    private const TIPO_COMP = [
        'factura' => '01',
        'boleta' => '03',
    ];

    /** @return array{filename:string, content:string, count:int, total:float} */
    public function registroVentas(Company $company, string $periodo): array
    {
        [$desde, $hasta] = $this->rango($periodo);
        $per = $periodo . '00';
        $lineas = [];
        $total = 0.0;
        $i = 0;

        $sales = Sale::where('company_id', $company->id)
            ->whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->where('estado', '!=', 'anulada')
            ->whereBetween('fecha', [$desde, $hasta])
            ->with('customer:id,tipo_documento')
            ->orderBy('fecha')->get();

        foreach ($sales as $s) {
            $i++;
            $total += (float) $s->total;
            $lineas[] = $this->lineaVenta(
                $per, $i, Carbon::parse($s->fecha), self::TIPO_COMP[$s->tipo_comprobante] ?? '03',
                $s->serie, $s->correlativo, $s->customer?->tipo_documento ?? '0', $s->cliente_documento,
                $s->cliente_nombre, (float) $s->subtotal, (float) $s->igv, (float) $s->total,
            );
        }

        // Notas de crédito (07) y débito (08)
        $notes = Note::where('company_id', $company->id)
            ->whereBetween('fecha', [$desde, $hasta])
            ->with('sale:id,serie,correlativo,tipo_comprobante,cliente_documento,cliente_nombre,customer_id', 'sale.customer:id,tipo_documento')
            ->orderBy('fecha')->get();

        foreach ($notes as $n) {
            $i++;
            $signo = $n->tipo === 'credito' ? -1 : 1;
            $total += $signo * (float) $n->total;
            $lineas[] = $this->lineaVenta(
                $per, $i, Carbon::parse($n->fecha), $n->tipo_doc, $n->serie, $n->correlativo,
                $n->sale?->customer?->tipo_documento ?? '0', $n->sale?->cliente_documento, $n->sale?->cliente_nombre ?? '-',
                $signo * (float) $n->subtotal, $signo * (float) $n->igv, $signo * (float) $n->total,
            );
        }

        return [
            'filename' => $this->ple($company->ruc, $periodo, '140100'),
            'content' => implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : ''),
            'count' => count($lineas),
            'total' => round($total, 2),
        ];
    }

    /** @return array{filename:string, content:string, count:int, total:float} */
    public function registroCompras(Company $company, string $periodo): array
    {
        [$desde, $hasta] = $this->rango($periodo);
        $per = $periodo . '00';
        $lineas = [];
        $total = 0.0;
        $i = 0;

        $compras = Purchase::where('company_id', $company->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->with('supplier:id,tipo_documento,numero_documento,razon_social')
            ->orderBy('fecha')->get();

        foreach ($compras as $c) {
            $i++;
            $total += (float) $c->total;
            $base = (float) $c->subtotal;
            $igv = (float) $c->igv;
            $importe = (float) $c->total;
            $tipoComp = match ($c->tipo_comprobante) {
                'factura' => '01', 'boleta' => '03', 'recibo' => '12', default => '00',
            };

            $lineas[] = implode('|', [
                $per,                                       // 1 periodo
                'M' . $i,                                   // 2 CUO
                (string) $i,                                // 3 correlativo
                Carbon::parse($c->fecha)->format('d/m/Y'),  // 4 fecha emisión
                '',                                         // 5 fecha vencimiento
                $tipoComp,                                  // 6 tipo comprobante
                $c->serie ?: '-',                           // 7 serie
                '',                                         // 8 año dua
                $c->numero ?: '-',                          // 9 número
                $c->supplier?->tipo_documento ?? '6',       // 10 tipo doc proveedor
                $c->supplier?->numero_documento ?? '-',     // 11 número doc
                $c->supplier?->razon_social ?? '-',         // 12 razón social
                $this->n($base),                            // 13 base imponible gravada
                $this->n($igv),                             // 14 IGV
                '0.00', '0.00',                             // 15-16 base/igv no gravado
                '0.00', '0.00',                             // 17-18 base/igv adq. no destinadas
                '0.00',                                     // 19 valor adq. no gravadas
                '0.00', '0.00', '0.00',                     // 20-22 ISC/otros
                $this->n($importe),                         // 23 importe total
                '1.000',                                    // 24 tipo cambio
                '', '', '', '',                             // 25-28 doc referencia
                '1',                                        // estado
            ]);
        }

        return [
            'filename' => $this->ple($company->ruc, $periodo, '080100'),
            'content' => implode("\r\n", $lineas) . (count($lineas) ? "\r\n" : ''),
            'count' => count($lineas),
            'total' => round($total, 2),
        ];
    }

    private function lineaVenta(
        string $per, int $i, Carbon $fecha, string $tipoComp, string $serie, int $correlativo,
        string $tipoDocCli, ?string $numDocCli, string $razon, float $base, float $igv, float $importe,
    ): string {
        return implode('|', [
            $per,                          // 1 periodo
            'M' . $i,                      // 2 CUO
            (string) $i,                   // 3 correlativo
            $fecha->format('d/m/Y'),       // 4 fecha emisión
            '',                            // 5 fecha vencimiento
            $tipoComp,                     // 6 tipo comprobante
            $serie,                        // 7 serie
            str_pad((string) $correlativo, 6, '0', STR_PAD_LEFT), // 8 número
            '',                            // 9 número final
            $tipoDocCli ?: '0',            // 10 tipo doc cliente
            $numDocCli ?: '-',             // 11 número doc
            $razon,                        // 12 razón social
            '0.00',                        // 13 export
            $this->n($base),               // 14 base gravada
            '0.00',                        // 15 dscto base
            $this->n($igv),                // 16 IGV
            '0.00',                        // 17 dscto IGV
            '0.00',                        // 18 exonerado
            '0.00',                        // 19 inafecto
            '0.00', '0.00', '0.00', '0.00', // 20-23 ISC/otros
            $this->n($importe),            // 24 importe total
            '1.000',                       // 25 tipo cambio
            '', '', '', '',                // 26-29 doc modificado (notas)
            '1',                           // estado
        ]);
    }

    private function ple(string $ruc, string $periodo, string $libro): string
    {
        return "LE{$ruc}{$periodo}00{$libro}00001111.txt";
    }

    private function n(float $v): string
    {
        return number_format($v, 2, '.', '');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rango(string $periodo): array
    {
        $inicio = Carbon::createFromFormat('Ym', $periodo)->startOfMonth();

        return [$inicio, (clone $inicio)->endOfMonth()];
    }
}
