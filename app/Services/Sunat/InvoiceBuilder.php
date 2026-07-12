<?php

namespace App\Services\Sunat;

use App\Models\Company as CompanyModel;
use App\Models\Sale;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Cuota;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\FormaPagos\FormaPagoCredito;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;

class InvoiceBuilder
{
    private const TIPO_DOC = [
        'factura' => '01',
        'boleta' => '03',
    ];

    public function build(Sale $sale, CompanyModel $companyModel): Invoice
    {
        $sale->loadMissing('items.product.unit', 'customer');

        $igvPct = (float) ($companyModel->igv_porcentaje ?? 18);

        $details = [];
        $gravadas = 0.0;
        $exoneradas = 0.0;
        $inafectas = 0.0;

        foreach ($sale->items as $item) {
            $cantidad = (float) $item->cantidad;
            $base = (float) $item->base;
            $igvLinea = (float) $item->igv;
            $total = (float) $item->total;
            // Catálogo 07: 10=gravado, 20=exonerado, 30=inafecto
            $tipo = $item->tipo_afectacion ?? ((bool) $item->afecto_igv ? '10' : '30');
            $gravado = $tipo === '10';

            $valorUnit = $cantidad > 0 ? round($base / $cantidad, 6) : 0;
            $precioUnit = $cantidad > 0 ? round($total / $cantidad, 6) : 0;

            match ($tipo) {
                '20' => $exoneradas += $base,
                '30' => $inafectas += $base,
                default => $gravadas += $base,
            };

            $details[] = (new SaleDetail())
                ->setCodProducto((string) ($item->product_id ?? 'S/C'))
                ->setUnidad($item->product?->unit?->codigo ?: 'NIU')
                ->setCantidad($cantidad)
                ->setDescripcion($item->descripcion)
                ->setMtoBaseIgv($base)
                ->setPorcentajeIgv($gravado ? $igvPct : 0)
                ->setIgv($igvLinea)
                ->setTipAfeIgv($tipo)
                ->setTotalImpuestos($igvLinea)
                ->setMtoValorUnitario($valorUnit)
                ->setMtoValorVenta(round($base, 2))
                ->setMtoPrecioUnitario($precioUnit);
        }

        $igv = round((float) $sale->igv, 2);
        $valorVenta = round($gravadas + $exoneradas + $inafectas, 2);
        $total = round((float) $sale->total, 2);

        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setTipoDoc(self::TIPO_DOC[$sale->tipo_comprobante])
            ->setSerie($sale->serie)
            ->setCorrelativo((string) $sale->correlativo)
            ->setFechaEmision(new \DateTime((string) $sale->fecha))
            ->setTipoMoneda($sale->moneda ?? 'PEN')
            ->setCompany($this->company($companyModel))
            ->setClient($this->client($sale))
            ->setMtoOperGravadas(round($gravadas, 2))
            ->setMtoOperExoneradas(round($exoneradas, 2))
            ->setMtoOperInafectas(round($inafectas, 2))
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setValorVenta($valorVenta)
            ->setSubTotal($total)
            ->setMtoImpVenta($total)
            ->setDetails($details)
            ->setLegends([
                (new Legend())->setCode('1000')->setValue(
                    NumberToWords::montoEnLetras($total, $sale->moneda === 'USD' ? 'DÓLARES' : 'SOLES'),
                ),
            ]);

        $this->aplicarFormaPago($invoice, $sale);

        // Percepción del IGV (Régimen de Percepciones) — solo si aplica
        if ($sale->aplica_percepcion && (float) $sale->percepcion_monto > 0) {
            $perc = round((float) $sale->percepcion_monto, 2);
            $invoice->setTipoOperacion('2001') // Operación sujeta a percepción (cat. 51)
                ->setPerception(
                    (new \Greenter\Model\Sale\SalePerception())
                        ->setCodReg($companyModel->percepcion_regimen ?: '01')
                        ->setPorcentaje((float) $companyModel->percepcion_tasa)
                        ->setMtoBase($total)
                        ->setMto($perc)
                        ->setMtoTotal(round($total + $perc, 2)),
                );
        }

        return $invoice;
    }

    /**
     * Forma de pago: contado, o crédito con cuotas (requerido por SUNAT
     * para facturas a crédito, RS 193-2020). Por defecto una sola cuota
     * con el saldo a 30 días si no hay un cronograma definido.
     */
    private function aplicarFormaPago(Invoice $invoice, Sale $sale): void
    {
        $moneda = $sale->moneda ?? 'PEN';

        if ($sale->metodo_pago !== 'credito') {
            $invoice->setFormaPago(new FormaPagoContado());

            return;
        }

        $pendiente = round((float) $sale->total - (float) $sale->monto_cobrado, 2);
        if ($pendiente <= 0) {
            $pendiente = round((float) $sale->total, 2);
        }

        $vencimiento = (new \DateTime((string) $sale->fecha))->modify('+30 days');

        $invoice->setFormaPago(new FormaPagoCredito($pendiente, $moneda));
        $invoice->setCuotas([
            (new Cuota())
                ->setMoneda($moneda)
                ->setMonto($pendiente)
                ->setFechaPago($vencimiento),
        ]);
    }

    private function company(CompanyModel $c): Company
    {
        $address = (new Address())
            ->setUbigueo($c->ubigeo ?? '150101')
            ->setDepartamento($c->departamento ?? 'LIMA')
            ->setProvincia($c->provincia ?? 'LIMA')
            ->setDistrito($c->distrito ?? 'LIMA')
            ->setDireccion($c->direccion ?? '-')
            ->setCodLocal('0000');

        return (new Company())
            ->setRuc($c->ruc)
            ->setRazonSocial($c->razon_social)
            ->setNombreComercial($c->nombre_comercial ?? $c->razon_social)
            ->setAddress($address);
    }

    private function client(Sale $sale): Client
    {
        $tipoDoc = $sale->customer?->tipo_documento ?? '0';
        $numDoc = $sale->cliente_documento ?: '00000000';

        return (new Client())
            ->setTipoDoc($tipoDoc)
            ->setNumDoc($numDoc)
            ->setRznSocial($sale->cliente_nombre ?: 'CLIENTES VARIOS');
    }
}
