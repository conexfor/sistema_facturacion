<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\Model\DocumentInterface;

/**
 * Driver real: firma con el certificado de la empresa y envía a SUNAT
 * (beta o producción según company.sunat_modo). Se activa cuando la empresa
 * tiene configuradas las credenciales (certificado + clave SOL).
 *
 * Solo se usa para facturas/boletas y notas (billService SOAP). Las guías de
 * remisión van por GreApiSender (GRE 2.0 REST), no por este sender.
 */
class GreenterSender implements SunatSenderInterface
{
    public function __construct(private readonly Company $company)
    {
    }

    public function emitir(DocumentInterface $document): array
    {
        // Certificado, credenciales, endpoint (beta/producción) y tiempos de espera.
        $see = SeeFactory::make($this->company);

        $xml = $see->getXmlSigned($document);
        $hash = $this->digest($xml);

        /** @var \Greenter\Model\Response\BillResult|null $result */
        $result = $see->send($document);

        if (! $result || ! $result->isSuccess()) {
            $error = $result?->getError();

            return [
                // Si SUNAT no respondió, el comprobante no fue rechazado: queda
                // "no_enviado" para volver a emitirlo.
                'estado' => SunatRespuesta::esFallaDeConexion($error) ? 'no_enviado' : 'rechazado',
                'xml' => $xml,
                'cdrZip' => null,
                'hash' => $hash,
                'mensaje' => SunatRespuesta::mensajeError($error, 'No se obtuvo respuesta de SUNAT.'),
            ];
        }

        $cdr = $result->getCdrResponse();
        $code = $cdr?->getCode();

        // 0 = aceptado; 4000+ = aceptado con observaciones; 2000-3999 = rechazado.
        $estado = SunatRespuesta::estadoCdr($code);
        $descripcion = $cdr?->getDescription() ?? 'Procesado por SUNAT.';

        return [
            'estado' => $estado,
            'xml' => $xml,
            'cdrZip' => $result->getCdrZip(),
            'hash' => $hash,
            'mensaje' => $estado === 'rechazado' ? trim($code . ' - ' . $descripcion) : $descripcion,
        ];
    }

    private function digest(string $xml): ?string
    {
        return preg_match('/<ds:DigestValue>(.*?)<\/ds:DigestValue>/', $xml, $m) ? $m[1] : null;
    }
}
