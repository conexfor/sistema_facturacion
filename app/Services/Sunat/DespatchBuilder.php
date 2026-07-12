<?php

namespace App\Services\Sunat;

use App\Models\Company as CompanyModel;
use App\Models\Despatch as DespatchModel;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Despatch\Direction;
use Greenter\Model\Despatch\Driver;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\Despatch\Transportist;
use Greenter\Model\Despatch\Vehicle;

class DespatchBuilder
{
    public function build(DespatchModel $d, CompanyModel $companyModel): Despatch
    {
        $d->loadMissing('items');

        $shipment = (new Shipment())
            ->setCodTraslado($d->cod_traslado)
            ->setDesTraslado($d->des_traslado)
            ->setModTraslado($d->mod_traslado)
            ->setFecTraslado(new \DateTime($d->fecha_traslado))
            ->setPesoTotal((float) $d->peso_total)
            ->setUndPesoTotal($d->und_peso ?: 'KGM')
            ->setNumBultos((int) $d->num_bultos)
            ->setPartida(new Direction($d->partida_ubigeo ?: '150101', $d->partida_direccion))
            ->setLlegada(new Direction($d->llegada_ubigeo ?: '150101', $d->llegada_direccion));

        if ($d->mod_traslado === '01') {
            $shipment->setTransportista(
                (new Transportist())
                    ->setTipoDoc('6')
                    ->setNumDoc($d->transportista_doc ?: '')
                    ->setRznSocial($d->transportista_nombre ?: '')
                    ->setNroMtc($d->transportista_mtc),
            );
        } else {
            $shipment->setVehiculo((new Vehicle())->setPlaca($d->vehiculo_placa ?: ''));
            $shipment->setChoferes([
                (new Driver())
                    ->setTipo('Principal')
                    ->setTipoDoc($d->chofer_tipo_doc ?: '1')
                    ->setNroDoc($d->chofer_num_doc ?: '')
                    ->setLicencia($d->chofer_licencia ?: '')
                    ->setNombres($d->chofer_nombres ?: '')
                    ->setApellidos($d->chofer_apellidos ?: '-'),
            ]);
        }

        $details = $d->items->map(fn ($it) => (new DespatchDetail())
            ->setCodigo((string) ($it->product_id ?? 'S/C'))
            ->setCantidad((float) $it->cantidad)
            ->setUnidad($it->unidad ?: 'NIU')
            ->setDescripcion($it->descripcion))->all();

        return (new Despatch())
            ->setVersion('2022')
            ->setTipoDoc('09')
            ->setSerie($d->serie)
            ->setCorrelativo((string) $d->correlativo)
            ->setFechaEmision(new \DateTime($d->fecha_emision))
            ->setObservacion($d->observacion)
            ->setCompany($this->company($companyModel))
            ->setDestinatario(
                (new Client())
                    ->setTipoDoc($d->dest_tipo_doc ?: '6')
                    ->setNumDoc($d->dest_num_doc ?: '')
                    ->setRznSocial($d->dest_nombre ?: 'CLIENTES VARIOS'),
            )
            ->setEnvio($shipment)
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
