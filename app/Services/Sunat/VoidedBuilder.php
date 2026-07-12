<?php

namespace App\Services\Sunat;

use App\Models\Company as CompanyModel;
use App\Models\VoidedDocument;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;

/**
 * Construye el documento Greenter "Comunicación de Baja" (RA) para anular
 * una factura ya emitida (catálogo de tipos: 01 factura).
 */
class VoidedBuilder
{
    private const TIPO_DOC = [
        'factura' => '01',
        'boleta' => '03',
    ];

    public function build(VoidedDocument $voided, CompanyModel $companyModel): Voided
    {
        $sale = $voided->sale;

        $detail = (new VoidedDetail())
            ->setTipoDoc(self::TIPO_DOC[$sale->tipo_comprobante] ?? '01')
            ->setSerie($sale->serie)
            ->setCorrelativo((string) $sale->correlativo)
            ->setDesMotivoBaja($voided->motivo);

        return (new Voided())
            ->setCorrelativo((string) $voided->correlativo)
            ->setFecGeneracion($voided->fecha_referencia)
            ->setFecComunicacion($voided->fecha_comunicacion)
            ->setCompany($this->company($companyModel))
            ->setDetails([$detail]);
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
