<?php

namespace App\Services\Sunat;

use Greenter\Model\Response\Error;

/**
 * Reglas comunes para comunicarse con SUNAT e interpretar sus respuestas.
 */
class SunatRespuesta
{
    public const MENSAJE_SIN_RESPUESTA = 'SUNAT no respondió a tiempo o su servicio no está disponible '
        . '(no es un error del comprobante). Intenta nuevamente en unos minutos.';

    /**
     * Los servicios de SUNAT (sobre todo beta) a veces tardan más de un minuto.
     * Se limita la espera del socket para que la llamada falle de forma
     * controlada antes de que PHP corte la petición por max_execution_time.
     */
    public static function prepararTiempos(): void
    {
        $espera = (int) config('services.sunat.timeout', 25);
        ini_set('default_socket_timeout', (string) $espera);

        // Margen para cargar el WSDL, firmar y procesar la respuesta.
        if ((int) ini_get('max_execution_time') !== 0) {
            @set_time_limit($espera * 2 + 15);
        }
    }

    /** El error es de comunicación (SUNAT caído/lento), no del documento. */
    public static function esFallaDeConexion(?Error $error): bool
    {
        if (! $error) {
            return false;
        }

        $texto = $error->getCode() . ' ' . $error->getMessage();

        return $error->getCode() === 'HTTP'
            || (bool) preg_match(
                '/Error Fetching http headers|Could not connect|timed out|Maximum execution time|getaddrinfo|SSL|Connection reset|Service Unavailable|Bad Gateway|Gateway Time-?out/i',
                $texto,
            );
    }

    public static function mensajeError(?Error $error, string $porDefecto): string
    {
        if (self::esFallaDeConexion($error)) {
            return self::MENSAJE_SIN_RESPUESTA;
        }

        return $error ? trim($error->getCode() . ' - ' . $error->getMessage()) : $porDefecto;
    }

    /**
     * Estado según el código del CDR (catálogo de errores de SUNAT):
     * 0 = aceptado; 4000+ = aceptado con observaciones; el resto = rechazado.
     */
    public static function estadoCdr(?string $codigo): string
    {
        $n = (int) $codigo;

        return match (true) {
            $codigo !== null && $n === 0 => 'aceptado',
            $n >= 4000 => 'observado',
            default => 'rechazado',
        };
    }
}
