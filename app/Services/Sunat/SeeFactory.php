<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;

/**
 * Construye una instancia de Greenter\See configurada con el certificado,
 * las credenciales SOL y el endpoint (beta/producción) de una empresa.
 */
class SeeFactory
{
    public static function make(Company $company): See
    {
        $see = new See();
        $see->setCertificate(CertificateLoader::pem($company));
        $see->setService(
            $company->sunat_modo === 'produccion'
                ? SunatEndpoints::FE_PRODUCCION
                : SunatEndpoints::FE_BETA,
        );
        $see->setCredentials(
            $company->ruc . $company->sol_user,
            (string) $company->sol_pass,
        );

        return $see;
    }
}
