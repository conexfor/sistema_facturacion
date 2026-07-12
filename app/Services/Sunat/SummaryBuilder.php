<?php

namespace App\Services\Sunat;

use App\Models\BoletaSummary;
use App\Models\Company as CompanyModel;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;

/**
 * Construye el documento Greenter "Resumen Diario de Boletas" (RC) a partir
 * de un BoletaSummary y las boletas que agrupa.
 */
class SummaryBuilder
{
    public function build(BoletaSummary $summary, CompanyModel $companyModel): Summary
    {
        $summary->loadMissing('sales.items', 'sales.customer');

        $details = [];
        foreach ($summary->sales as $sale) {
            $gravadas = 0.0;
            $exoneradas = 0.0;
            $inafectas = 0.0;
            foreach ($sale->items as $item) {
                $base = (float) $item->base;
                $tipo = $item->tipo_afectacion ?? ((bool) $item->afecto_igv ? '10' : '30');
                match ($tipo) {
                    '20' => $exoneradas += $base,
                    '30' => $inafectas += $base,
                    default => $gravadas += $base,
                };
            }

            $detail = (new SummaryDetail())
                ->setTipoDoc('03')
                ->setSerieNro($sale->serie . '-' . $sale->correlativo)
                ->setEstado('1') // 1 = adicionar
                ->setTotal(round((float) $sale->total, 2))
                ->setMtoOperGravadas(round($gravadas, 2))
                ->setMtoOperExoneradas(round($exoneradas, 2))
                ->setMtoOperInafectas(round($inafectas, 2))
                ->setMtoIGV(round((float) $sale->igv, 2));

            // Receptor (obligatorio en el RC). Si la boleta fue a "varios" sin
            // documento, se usa tipo 0 (sin RUC) y número 00000000 — igual que el
            // comprobante individual que SUNAT ya acepta. Evita el AdditionalAccountID
            // vacío que SUNAT rechaza (catálogo 06).
            $detail->setClienteTipo($sale->customer?->tipo_documento ?: '0')
                ->setClienteNro($sale->cliente_documento ?: '00000000');

            $details[] = $detail;
        }

        return (new Summary())
            ->setCorrelativo((string) $summary->correlativo)
            ->setFecGeneracion($summary->fecha_referencia)
            ->setFecResumen($summary->fecha_envio)
            ->setMoneda('PEN')
            ->setCompany($this->company($companyModel))
            ->setDetails($details);
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
}
