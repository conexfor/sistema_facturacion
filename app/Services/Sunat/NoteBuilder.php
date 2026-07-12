<?php

namespace App\Services\Sunat;

use App\Models\Company as CompanyModel;
use App\Models\Note as NoteModel;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\SaleDetail;

class NoteBuilder
{
    private const TIPO_DOC_AFECTADO = [
        'factura' => '01',
        'boleta' => '03',
    ];

    public function build(NoteModel $note, CompanyModel $companyModel): Note
    {
        $note->loadMissing('items.product.unit', 'sale.customer');
        $sale = $note->sale;
        $igvPct = (float) ($companyModel->igv_porcentaje ?? 18);

        $details = [];
        $gravadas = 0.0;
        $inafectas = 0.0;

        foreach ($note->items as $item) {
            $cantidad = (float) $item->cantidad;
            $base = (float) $item->base;
            $igvLinea = (float) $item->igv;
            $total = (float) $item->total;
            $afecto = (bool) $item->afecto_igv;

            $valorUnit = $cantidad > 0 ? round($base / $cantidad, 6) : 0;
            $precioUnit = $cantidad > 0 ? round($total / $cantidad, 6) : 0;

            $afecto ? $gravadas += $base : $inafectas += $base;

            $details[] = (new SaleDetail())
                ->setCodProducto((string) ($item->product_id ?? 'S/C'))
                ->setUnidad($item->product?->unit?->codigo ?: 'NIU')
                ->setCantidad($cantidad)
                ->setDescripcion($item->descripcion)
                ->setMtoBaseIgv($base)
                ->setPorcentajeIgv($afecto ? $igvPct : 0)
                ->setIgv($igvLinea)
                ->setTipAfeIgv($afecto ? '10' : '30')
                ->setTotalImpuestos($igvLinea)
                ->setMtoValorUnitario($valorUnit)
                ->setMtoValorVenta(round($base, 2))
                ->setMtoPrecioUnitario($precioUnit);
        }

        $igv = round((float) $note->igv, 2);
        $valorVenta = round($gravadas + $inafectas, 2);
        $total = round((float) $note->total, 2);

        return (new Note())
            ->setUblVersion('2.1')
            ->setTipoDoc($note->tipo_doc)
            ->setSerie($note->serie)
            ->setCorrelativo((string) $note->correlativo)
            ->setFechaEmision(new \DateTime($note->fecha))
            ->setTipDocAfectado(self::TIPO_DOC_AFECTADO[$sale->tipo_comprobante] ?? '01')
            // El número del doc afectado debe ir sin ceros de relleno (B001-6), tal como
            // fue registrado en SUNAT, no con el formato visual B001-000006.
            ->setNumDocfectado($sale->serie . '-' . $sale->correlativo)
            ->setCodMotivo($note->cod_motivo)
            ->setDesMotivo($note->des_motivo)
            ->setTipoMoneda($note->moneda ?? 'PEN')
            ->setCompany($this->company($companyModel))
            ->setClient($this->client($note))
            ->setMtoOperGravadas(round($gravadas, 2))
            ->setMtoOperInafectas(round($inafectas, 2))
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setValorVenta($valorVenta)
            ->setSubTotal($total)
            ->setMtoImpVenta($total)
            ->setDetails($details)
            ->setLegends([
                (new Legend())->setCode('1000')->setValue(
                    NumberToWords::montoEnLetras($total, $note->moneda === 'USD' ? 'DÓLARES' : 'SOLES'),
                ),
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

    private function client(NoteModel $note): Client
    {
        $sale = $note->sale;

        return (new Client())
            ->setTipoDoc($sale->customer?->tipo_documento ?? '0')
            ->setNumDoc($sale->cliente_documento ?: '00000000')
            ->setRznSocial($sale->cliente_nombre ?: 'CLIENTES VARIOS');
    }
}
