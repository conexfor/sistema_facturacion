<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\Model\DocumentInterface;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;

/**
 * Driver real: firma con el certificado de la empresa y envía a SUNAT
 * (beta o producción según company.sunat_modo). Se activa cuando la empresa
 * tiene configuradas las credenciales (certificado + clave SOL).
 */
class GreenterSender implements SunatSenderInterface
{
    public function __construct(private readonly Company $company)
    {
    }

    public function emitir(DocumentInterface $document): array
    {
        $pem = $this->loadCertificate();

        $prod = $this->company->sunat_modo === 'produccion';
        // Las guías de remisión (tipoDoc 09) usan un endpoint distinto al de
        // facturas/boletas/notas.
        $esGuia = $document instanceof \Greenter\Model\Sale\Despatch;
        $endpoint = $esGuia
            ? ($prod ? SunatEndpoints::GUIA_PRODUCCION : SunatEndpoints::GUIA_BETA)
            : ($prod ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);

        $see = new See();
        $see->setCertificate($pem);
        $see->setService($endpoint);
        $see->setCredentials(
            $this->company->ruc . $this->company->sol_user,
            (string) $this->company->sol_pass,
        );

        $xml = $see->getXmlSigned($document);
        $hash = $this->digest($xml);

        /** @var \Greenter\Model\Response\BillResult|null $result */
        $result = $see->send($document);

        if (! $result || ! $result->isSuccess()) {
            $error = $result?->getError();

            return [
                'estado' => 'rechazado',
                'xml' => $xml,
                'cdrZip' => null,
                'hash' => $hash,
                'mensaje' => $error
                    ? trim($error->getCode() . ' - ' . $error->getMessage())
                    : 'No se obtuvo respuesta de SUNAT.',
            ];
        }

        $cdr = $result->getCdrResponse();
        $code = $cdr?->getCode();

        return [
            'estado' => $code === '0' ? 'aceptado' : 'observado',
            'xml' => $xml,
            'cdrZip' => $result->getCdrZip(),
            'hash' => $hash,
            'mensaje' => $cdr?->getDescription() ?? 'Procesado por SUNAT.',
        ];
    }

    private function loadCertificate(): string
    {
        return CertificateLoader::pem($this->company);
    }

    private function digest(string $xml): ?string
    {
        return preg_match('/<ds:DigestValue>(.*?)<\/ds:DigestValue>/', $xml, $m) ? $m[1] : null;
    }
}
