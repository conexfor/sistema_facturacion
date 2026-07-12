<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\Model\DocumentInterface;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;

/**
 * Driver de simulación: genera el XML UBL firmado con un certificado efímero
 * pero NO lo envía a SUNAT. Útil para desarrollar y demostrar el flujo
 * mientras no se cuente con el certificado real.
 */
class SimulatedSender implements SunatSenderInterface
{
    public function __construct(private readonly Company $company)
    {
    }

    public function emitir(DocumentInterface $document): array
    {
        $see = new See();
        $see->setCertificate(CertificateGenerator::ephemeralPem());
        $see->setService(SunatEndpoints::FE_BETA);
        $see->setCredentials($this->company->ruc . 'MODDATOS', 'moddatos');

        $xml = $see->getXmlSigned($document);

        return [
            'estado' => 'simulado',
            'xml' => $xml,
            'cdrZip' => null,
            'hash' => $this->digest($xml),
            'mensaje' => 'Comprobante generado y firmado en modo simulación (no enviado a SUNAT). '
                . 'Configura el certificado y la clave SOL para emitir realmente.',
        ];
    }

    private function digest(string $xml): ?string
    {
        return preg_match('/<ds:DigestValue>(.*?)<\/ds:DigestValue>/', $xml, $m) ? $m[1] : null;
    }
}
