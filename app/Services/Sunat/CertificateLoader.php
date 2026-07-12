<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Carga el certificado digital de una empresa y lo devuelve siempre en formato
 * PEM (clave privada + certificado), listo para firmar con Greenter.
 *
 * Acepta tanto un PEM ya convertido como un .pfx/.p12 binario, que se convierte
 * al vuelo usando la contraseña del certificado. Centralizar esta lógica evita
 * que los distintos flujos de emisión (comprobantes, notas, resúmenes, bajas)
 * la implementen de forma inconsistente.
 */
class CertificateLoader
{
    public static function pem(Company $company): string
    {
        if (! $company->certificate_path || ! Storage::exists($company->certificate_path)) {
            throw new RuntimeException('No se encontró el certificado digital de la empresa.');
        }

        $content = (string) Storage::get($company->certificate_path);

        // Ya es PEM (clave + certificado en texto).
        if (str_contains($content, '-----BEGIN')) {
            return $content;
        }

        // Es un .pfx/.p12 binario: se convierte a PEM con la contraseña del certificado.
        $certs = [];
        if (! openssl_pkcs12_read($content, $certs, (string) $company->certificate_password)) {
            throw new RuntimeException('No se pudo abrir el certificado .pfx (verifica la contraseña del certificado).');
        }

        return ($certs['pkey'] ?? '') . ($certs['cert'] ?? '');
    }
}
