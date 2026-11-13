<?php

namespace App\Support;

use Throwable;

/**
 * Detecta si una excepción se debe a que no hay conexión con la base de datos
 * (servidor caído, usuario/host rechazado, base inexistente...), a diferencia
 * de un error en una consulta concreta.
 */
class DatabaseUnavailable
{
    /** Códigos de error de MySQL/MariaDB que indican falta de conexión. */
    private const CODIGOS = [
        1040, // Too many connections
        1044, // Acceso denegado a la base de datos
        1045, // Usuario o contraseña incorrectos
        1049, // Base de datos desconocida
        1130, // Host no autorizado (tabla de usuarios dañada o sin permiso)
        2002, // No se puede conectar (servidor apagado)
        2003, // No se puede conectar al host
        2005, // Host desconocido
        2006, // MySQL server has gone away
        2013, // Conexión perdida durante la consulta
    ];

    /** Código de error de conexión encontrado en la excepción, o null. */
    public static function codigo(Throwable $e): ?int
    {
        for ($actual = $e; $actual !== null; $actual = $actual->getPrevious()) {
            // Los errores de conexión llevan el código del driver entre
            // corchetes: "SQLSTATE[HY000] [1130] Host ... is not allowed".
            if (preg_match('/SQLSTATE\[\w+\] \[(\d+)\]/', $actual->getMessage(), $m)
                && in_array((int) $m[1], self::CODIGOS, true)) {
                return (int) $m[1];
            }
        }

        return null;
    }

    public static function matches(Throwable $e): bool
    {
        return self::codigo($e) !== null;
    }
}
