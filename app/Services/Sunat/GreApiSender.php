<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\Api;
use Greenter\Model\DocumentInterface;
use Throwable;

/**
 * Envía guías de remisión electrónicas (GRE 2.0) por la API REST de SUNAT.
 *
 * A diferencia de facturas/boletas (SOAP síncrono), las GRE usan OAuth2 +
 * REST y son asíncronas: al enviar se obtiene un ticket y luego se consulta
 * su estado para descargar el CDR.
 *
 * En modo "beta" apunta al simulador de homologación GRE (Greenter/NubeFact)
 * con las credenciales de prueba, para poder validar el flujo sin credenciales
 * reales. En "producción" usa la API de SUNAT con las credenciales de la empresa.
 */
class GreApiSender
{
    private const ENDPOINTS_PROD = [
        'auth' => 'https://api-seguridad.sunat.gob.pe/v1',
        'cpe' => 'https://api-cpe.sunat.gob.pe/v1',
    ];

    private const ENDPOINTS_BETA = [
        'auth' => 'https://gre-test.nubefact.com/v1',
        'cpe' => 'https://gre-test.nubefact.com/v1',
    ];

    /** Credenciales de homologación GRE (simulador). */
    private const BETA_CLIENT_ID = 'test-85e5b0ae-255c-4891-a595-0b98c65c9854';
    private const BETA_CLIENT_SECRET = 'test-Hty/M6QshYvPgItX2P0+Kw==';
    private const BETA_RUC = '20161515648';
    private const BETA_SOL_USER = 'MODDATOS';
    private const BETA_SOL_PASS = 'MODDATOS';

    public function __construct(private readonly Company $company)
    {
    }

    /** Envía la guía. Devuelve el ticket (flujo asíncrono), no el CDR. */
    public function emitir(DocumentInterface $document): array
    {
        $api = $this->api();

        // El simulador de homologación solo acepta su RUC de pruebas. En beta se
        // sobreescribe el emisor para poder validar el flujo completo; en
        // producción se conserva el RUC real de la empresa.
        if ($this->company->sunat_modo !== 'produccion' && method_exists($document, 'getCompany')) {
            $document->getCompany()?->setRuc(self::BETA_RUC);
        }

        try {
            $result = $api->send($document);
        } catch (Throwable $e) {
            return [
                'estado' => 'rechazado',
                'xml' => $api->getLastXml() ?? '',
                'ticket' => null,
                'hash' => $this->digest($api->getLastXml()),
                'mensaje' => $this->limpiar($e->getMessage()),
            ];
        }

        $xml = $api->getLastXml() ?? '';
        $hash = $this->digest($xml);

        if (! $result || ! $result->isSuccess()) {
            $error = $result?->getError();

            return [
                'estado' => 'rechazado',
                'xml' => $xml,
                'ticket' => null,
                'hash' => $hash,
                'mensaje' => $error
                    ? trim($error->getCode() . ' - ' . $error->getMessage())
                    : 'No se obtuvo respuesta de SUNAT.',
            ];
        }

        return [
            'estado' => 'enviado',
            'xml' => $xml,
            'ticket' => method_exists($result, 'getTicket') ? $result->getTicket() : null,
            'hash' => $hash,
            'mensaje' => 'Guía enviada. Consulta el estado para obtener el CDR.',
        ];
    }

    /** Consulta el estado de un ticket y, si ya terminó, devuelve el CDR. */
    public function consultar(string $ticket): array
    {
        $api = $this->api();

        try {
            $result = $api->getStatus($ticket);
        } catch (Throwable $e) {
            return ['estado' => 'enviado', 'cdrZip' => null, 'mensaje' => $this->limpiar($e->getMessage())];
        }

        // 98 = aún en proceso.
        if ($result->getCode() === '98') {
            return [
                'estado' => 'enviado',
                'cdrZip' => null,
                'mensaje' => 'En proceso en SUNAT. Vuelve a consultar en unos segundos.',
            ];
        }

        if ($result->isSuccess()) {
            $cdr = $result->getCdrResponse();

            return [
                'estado' => $cdr?->getCode() === '0' ? 'aceptado' : 'observado',
                'cdrZip' => $result->getCdrZip(),
                'mensaje' => $cdr?->getDescription() ?? 'Procesado por SUNAT.',
            ];
        }

        $error = $result->getError();

        return [
            'estado' => 'rechazado',
            'cdrZip' => null,
            'mensaje' => $error
                ? trim($error->getCode() . ' - ' . $error->getMessage())
                : 'Rechazado por SUNAT.',
        ];
    }

    private function api(): Api
    {
        $prod = $this->company->sunat_modo === 'produccion';

        $api = new Api($prod ? self::ENDPOINTS_PROD : self::ENDPOINTS_BETA);
        $api->setCertificate(CertificateLoader::pem($this->company));

        if ($prod) {
            $api->setClaveSOL($this->company->ruc, (string) $this->company->sol_user, (string) $this->company->sol_pass)
                ->setApiCredentials((string) $this->company->gre_client_id, (string) $this->company->gre_client_secret);
        } else {
            $api->setClaveSOL(self::BETA_RUC, self::BETA_SOL_USER, self::BETA_SOL_PASS)
                ->setApiCredentials(self::BETA_CLIENT_ID, self::BETA_CLIENT_SECRET);
        }

        return $api;
    }

    private function digest(?string $xml): ?string
    {
        return $xml && preg_match('/<ds:DigestValue>(.*?)<\/ds:DigestValue>/', $xml, $m) ? $m[1] : null;
    }

    private function limpiar(string $mensaje): string
    {
        return trim(mb_substr($mensaje, 0, 500));
    }
}
