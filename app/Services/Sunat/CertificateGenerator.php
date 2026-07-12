<?php

namespace App\Services\Sunat;

use RuntimeException;

/**
 * Genera (y cachea) un certificado autofirmado efímero para el modo
 * simulación, de modo que Greenter pueda producir un XML UBL firmado
 * sin necesidad del certificado real de la empresa. NO es válido ante SUNAT.
 */
class CertificateGenerator
{
    public static function ephemeralPem(): string
    {
        $dir = storage_path('app/sunat/_sim');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pemPath = $dir . '/cert.pem';
        if (is_file($pemPath)) {
            return (string) file_get_contents($pemPath);
        }

        // PHP CLI en Windows no trae openssl.cnf por defecto
        $cnf = $dir . '/openssl.cnf';
        file_put_contents($cnf, "[req]\ndistinguished_name = dn\n[dn]\n");

        $cfg = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $cnf,
        ];

        $pkey = openssl_pkey_new($cfg);
        if (! $pkey) {
            throw new RuntimeException('No se pudo generar la clave para el certificado de simulación.');
        }

        $dn = ['countryName' => 'PE', 'organizationName' => 'ERP Simulacion', 'commonName' => 'ERP DEMO'];
        $csr = openssl_csr_new($dn, $pkey, $cfg);
        $x509 = openssl_csr_sign($csr, null, $pkey, 365, $cfg);
        openssl_x509_export($x509, $cert);
        openssl_pkey_export($pkey, $priv, null, $cfg);

        $pem = $priv . $cert;
        file_put_contents($pemPath, $pem);

        return $pem;
    }
}
