<?php

namespace App\Services\Sunat;

use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

/**
 * Convierte un certificado .pfx/.p12 a PEM (clave privada + certificado).
 *
 * Muchos certificados digitales emitidos en Perú usan cifrado antiguo
 * (RC2-40 / 3DES). OpenSSL 3, el que trae PHP, no lo abre por defecto y
 * responde "unsupported". En ese caso se recurre al binario `openssl` con la
 * opción -legacy (ruta configurable con OPENSSL_BIN).
 */
class PfxConverter
{
    public static function toPem(string $pfx, string $password): string
    {
        $certs = [];
        if (@openssl_pkcs12_read($pfx, $certs, $password)) {
            return self::normalizar($certs['pkey'] ?? '', $certs['cert'] ?? '');
        }

        $errores = self::erroresOpenssl();

        if (str_contains($errores, 'mac verify failure')) {
            throw new RuntimeException('La contraseña del certificado .pfx es incorrecta.');
        }

        if (str_contains($errores, 'unsupported')) {
            return self::convertirConCli($pfx, $password);
        }

        throw new RuntimeException('No se pudo leer el archivo. Verifica que sea un certificado .pfx/.p12 válido.');
    }

    /**
     * Conversión con el binario openssl usando el proveedor legacy.
     * La contraseña se pasa por variable de entorno para no exponerla en la
     * lista de procesos.
     */
    private static function convertirConCli(string $pfx, string $password): string
    {
        $bin = (string) config('services.openssl.bin', 'openssl');
        $tmp = tempnam(sys_get_temp_dir(), 'pfx');
        file_put_contents($tmp, $pfx);

        try {
            $result = Process::env(['PFX_PASS' => $password])
                ->timeout(20)
                ->run([$bin, 'pkcs12', '-in', $tmp, '-legacy', '-nodes', '-clcerts', '-passin', 'env:PFX_PASS']);
        } catch (Throwable) {
            throw new RuntimeException(self::mensajeSinCli());
        } finally {
            @unlink($tmp);
        }

        if (! $result->successful()) {
            $err = strtolower($result->errorOutput());

            if (str_contains($err, 'mac verify') || str_contains($err, 'invalid password')) {
                throw new RuntimeException('La contraseña del certificado .pfx es incorrecta.');
            }
            if (str_contains($err, 'not recognized') || str_contains($err, 'not found') || str_contains($err, 'no such file')) {
                throw new RuntimeException(self::mensajeSinCli());
            }

            throw new RuntimeException('No se pudo convertir el certificado .pfx: ' . trim($result->errorOutput()));
        }

        // La salida trae líneas "Bag Attributes" además de los bloques PEM:
        // se extraen solo la clave privada y el certificado.
        $out = $result->output();
        preg_match('/-----BEGIN (?:RSA |EC )?PRIVATE KEY-----.+?-----END (?:RSA |EC )?PRIVATE KEY-----/s', $out, $key);
        preg_match('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', $out, $cert);

        $keyPem = $key[0] ?? '';
        $certPem = $cert[0] ?? '';

        if (! openssl_pkey_get_private($keyPem) || ! openssl_x509_read($certPem)) {
            throw new RuntimeException('El certificado .pfx no contiene una clave privada y un certificado válidos.');
        }

        return self::normalizar($keyPem . "\n", $certPem . "\n");
    }

    /** Verifica que el .pfx traiga clave privada y certificado. */
    private static function normalizar(string $pkey, string $cert): string
    {
        if ($pkey === '' || $cert === '') {
            throw new RuntimeException('El certificado .pfx no contiene una clave privada y un certificado válidos.');
        }

        return $pkey . $cert;
    }

    private static function erroresOpenssl(): string
    {
        $errores = [];
        while ($msg = openssl_error_string()) {
            $errores[] = $msg;
        }

        return strtolower(implode(' | ', $errores));
    }

    private static function mensajeSinCli(): string
    {
        return 'El certificado usa un cifrado antiguo que PHP no puede abrir y no se encontró el programa openssl '
            . '(configura OPENSSL_BIN en el .env). Alternativa: conviértelo a .pem con '
            . '"openssl pkcs12 -in certificado.pfx -legacy -nodes -out certificado.pem" y sube el .pem.';
    }
}
