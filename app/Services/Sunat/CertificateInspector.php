<?php

namespace App\Services\Sunat;

use Carbon\CarbonImmutable;

/**
 * Lee los datos del titular de un certificado digital (PEM).
 *
 * Cada entidad certificadora ubica el RUC en un campo distinto del "subject"
 * (organizationIdentifier "VATPE-20…", serialNumber, OU, incluso el CN), así
 * que se recorren todos los campos y se toma el primer número de 11 dígitos
 * que sea un RUC válido según su dígito verificador.
 */
class CertificateInspector
{
    /** Campos del subject en orden de prioridad para buscar el RUC. */
    private const CAMPOS_RUC = ['organizationIdentifier', '2.5.4.97', 'UNDEF', 'serialNumber', 'OU', 'CN', 'O'];

    /**
     * @return array{ruc: ?string, titular: ?string, emisor: ?string, vence: ?string, vencido: bool}|null
     */
    public static function inspect(string $pem): ?array
    {
        $info = @openssl_x509_parse($pem);
        if (! $info) {
            return null;
        }

        $subject = $info['subject'] ?? [];
        $issuer = $info['issuer'] ?? [];
        $vence = isset($info['validTo_time_t'])
            ? CarbonImmutable::createFromTimestamp($info['validTo_time_t'])
            : null;

        return [
            'ruc' => self::buscarRuc($subject),
            'titular' => self::valor($subject, 'O') ?? self::valor($subject, 'CN'),
            'emisor' => self::valor($issuer, 'O') ?? self::valor($issuer, 'CN'),
            'vence' => $vence?->toDateString(),
            'vencido' => $vence ? $vence->isPast() : false,
        ];
    }

    private static function buscarRuc(array $subject): ?string
    {
        $prioritarios = array_intersect_key(array_flip(self::CAMPOS_RUC), $subject);
        $orden = array_merge(array_keys($prioritarios), array_keys(array_diff_key($subject, $prioritarios)));

        foreach ($orden as $campo) {
            foreach ((array) $subject[$campo] as $texto) {
                preg_match_all('/(?<!\d)(10|15|16|17|20)\d{9}(?!\d)/', (string) $texto, $m);
                foreach ($m[0] as $candidato) {
                    if (self::rucValido($candidato)) {
                        return $candidato;
                    }
                }
            }
        }

        return null;
    }

    /** Valida el dígito verificador del RUC (módulo 11). */
    public static function rucValido(string $ruc): bool
    {
        if (! preg_match('/^\d{11}$/', $ruc)) {
            return false;
        }

        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        foreach ($pesos as $i => $peso) {
            $suma += (int) $ruc[$i] * $peso;
        }

        $digito = 11 - ($suma % 11);
        $digito = match ($digito) {
            10 => 0,
            11 => 1,
            default => $digito,
        };

        return $digito === (int) $ruc[10];
    }

    private static function valor(array $dn, string $campo): ?string
    {
        $v = $dn[$campo] ?? null;
        if (is_array($v)) {
            $v = $v[0] ?? null;
        }

        return $v !== null && trim((string) $v) !== '' ? trim((string) $v) : null;
    }
}
