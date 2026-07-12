<?php

namespace App\Services\Sunat;

/**
 * Convierte un monto a su representación en letras (es-PE) para la leyenda
 * 1000 del comprobante electrónico, p.ej. "CIENTO VEINTE CON 50/100 SOLES".
 */
class NumberToWords
{
    private const UNIDADES = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
        'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
    ];

    private const DECENAS = [
        '', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private const CENTENAS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    public static function montoEnLetras(float $monto, string $moneda = 'SOLES'): string
    {
        $entero = (int) floor($monto);
        $decimal = (int) round(($monto - $entero) * 100);
        $letras = $entero === 0 ? 'CERO' : self::convertir($entero);

        return sprintf('%s CON %02d/100 %s', $letras, $decimal, $moneda);
    }

    private static function convertir(int $n): string
    {
        if ($n < 0) {
            return 'MENOS ' . self::convertir(-$n);
        }
        if ($n <= 20) {
            return self::UNIDADES[$n];
        }
        if ($n < 100) {
            $d = intdiv($n, 10);
            $u = $n % 10;
            if ($d === 2) {
                return 'VEINTI' . self::UNIDADES[$u];
            }

            return self::DECENAS[$d] . ($u ? ' Y ' . self::UNIDADES[$u] : '');
        }
        if ($n < 1000) {
            $c = intdiv($n, 100);
            $r = $n % 100;
            if ($n === 100) {
                return 'CIEN';
            }

            return self::CENTENAS[$c] . ($r ? ' ' . self::convertir($r) : '');
        }
        if ($n < 1000000) {
            $miles = intdiv($n, 1000);
            $r = $n % 1000;
            $pref = $miles === 1 ? 'MIL' : self::convertir($miles) . ' MIL';

            return $pref . ($r ? ' ' . self::convertir($r) : '');
        }

        $millones = intdiv($n, 1000000);
        $r = $n % 1000000;
        $pref = $millones === 1 ? 'UN MILLÓN' : self::convertir($millones) . ' MILLONES';

        return $pref . ($r ? ' ' . self::convertir($r) : '');
    }
}
