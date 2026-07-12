<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Consulta datos de contribuyentes/personas en SUNAT (RUC) y RENIEC (DNI)
 * a través de un proveedor HTTP configurable (por defecto decolecta.com;
 * también compatible con apis.net.pe v2).
 *
 * Devuelve un arreglo normalizado que, además de los datos básicos, incluye
 * el estado y la condición del contribuyente y su domicilio fiscal completo
 * para que la representación impresa del comprobante sea tributariamente válida:
 *
 *   [
 *     'tipo_documento', 'numero_documento', 'razon_social', 'nombre_comercial',
 *     'direccion', 'ubigeo', 'departamento', 'provincia', 'distrito',
 *     'estado' (RUC: ACTIVO/BAJA/…), 'condicion' (RUC: HABIDO/NO HABIDO/…),
 *     'habilitado' (bool: seguro para emitir comprobante),
 *   ]
 */
class DocumentLookupService
{
    public function isEnabled(): bool
    {
        return ! empty(config('services.peru_api.token'));
    }

    public function lookup(string $tipo, string $numero): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException(
                'La consulta automática a SUNAT/RENIEC no está configurada. '
                . 'Define PERU_API_TOKEN (y opcionalmente PERU_API_URL) en el archivo .env.',
            );
        }

        $numero = preg_replace('/\D/', '', $numero) ?? '';

        // Cache de resultados (los datos de RUC/DNI cambian con muy poca frecuencia):
        // evita depender del tiempo de respuesta del proveedor en cada venta y
        // ahorra cuota de la API. Solo se cachea el resultado exitoso; los errores
        // se propagan sin cachearse. Con TTL 0 el cache queda deshabilitado.
        $ttl = (int) config('services.peru_api.cache_ttl', 86400);
        $resolver = fn () => match ($tipo) {
            '6' => $this->consultarRuc($numero),
            '1' => $this->consultarDni($numero),
            default => throw new RuntimeException('Solo se puede consultar automáticamente RUC (6) o DNI (1).'),
        };

        if ($ttl <= 0) {
            return $resolver();
        }

        return Cache::remember("peru_api:{$tipo}:{$numero}", $ttl, $resolver);
    }

    private function consultarRuc(string $ruc): array
    {
        if (strlen($ruc) !== 11) {
            throw new RuntimeException('El RUC debe tener 11 dígitos.');
        }

        $data = $this->request('/sunat/ruc', ['numero' => $ruc]);

        // Soporta decolecta.com (snake_case) y apis.net.pe (camelCase).
        $estado = strtoupper((string) ($data['estado'] ?? $data['estado_del_contribuyente'] ?? ''));
        $condicion = strtoupper((string) (
            $data['condicion'] ?? $data['condicion_de_domicilio'] ?? $data['condicion_domicilio'] ?? ''
        ));

        $departamento = $data['departamento'] ?? null;
        $provincia = $data['provincia'] ?? null;
        $distrito = $data['distrito'] ?? null;

        return [
            'tipo_documento' => '6',
            'numero_documento' => $ruc,
            'razon_social' => $data['razon_social'] ?? $data['razonSocial'] ?? $data['nombre'] ?? '',
            'nombre_comercial' => $data['nombre_comercial'] ?? $data['nombreComercial'] ?? null,
            'direccion' => $this->armarDireccion($data, $departamento, $provincia, $distrito),
            'ubigeo' => $data['ubigeo'] ?? $data['ubigeo_sunat'] ?? null,
            'departamento' => $departamento,
            'provincia' => $provincia,
            'distrito' => $distrito,
            'estado' => $estado ?: null,
            'condicion' => $condicion ?: null,
            // El contribuyente es seguro para emitir si está ACTIVO y HABIDO.
            // Si el proveedor no devuelve el dato, no bloqueamos (null => asumimos válido).
            'habilitado' => $this->esHabilitado($estado, $condicion),
        ];
    }

    private function consultarDni(string $dni): array
    {
        if (strlen($dni) !== 8) {
            throw new RuntimeException('El DNI debe tener 8 dígitos.');
        }

        $data = $this->request('/reniec/dni', ['numero' => $dni]);

        // decolecta.com: first_name / first_last_name / second_last_name / full_name.
        // apis.net.pe: nombres / apellidoPaterno / apellidoMaterno / nombreCompleto.
        $nombres = $data['nombres'] ?? $data['first_name'] ?? '';
        $apePaterno = $data['apellidoPaterno'] ?? $data['first_last_name'] ?? '';
        $apeMaterno = $data['apellidoMaterno'] ?? $data['second_last_name'] ?? '';
        $armado = trim("{$nombres} {$apePaterno} {$apeMaterno}");

        $nombre = $armado !== ''
            ? $armado
            : ($data['nombreCompleto'] ?? $data['full_name'] ?? '');

        return [
            'tipo_documento' => '1',
            'numero_documento' => $dni,
            'razon_social' => trim($nombre),
            'nombre_comercial' => null,
            'direccion' => $data['direccion'] ?? $data['direccion_completa'] ?? null,
            'ubigeo' => $data['ubigeo'] ?? null,
            'departamento' => $data['departamento'] ?? null,
            'provincia' => $data['provincia'] ?? null,
            'distrito' => $data['distrito'] ?? null,
            'estado' => null,
            'condicion' => null,
            'habilitado' => true,
        ];
    }

    /**
     * Construye el domicilio fiscal. Prioriza la dirección completa; si el
     * proveedor solo entrega la vía, le anexa distrito/provincia/departamento.
     */
    private function armarDireccion(array $data, ?string $dep, ?string $prov, ?string $dist): ?string
    {
        $completa = $data['direccion_completa'] ?? $data['direccionCompleta'] ?? null;
        if (! empty($completa)) {
            return trim($completa);
        }

        $via = $data['direccion'] ?? null;
        if (empty($via)) {
            return null;
        }

        $sufijo = implode(' - ', array_filter([$dist, $prov, $dep]));

        return trim($sufijo !== '' ? "{$via} - {$sufijo}" : $via);
    }

    private function esHabilitado(string $estado, string $condicion): ?bool
    {
        if ($estado === '' && $condicion === '') {
            return null;
        }

        $estadoOk = $estado === '' || $estado === 'ACTIVO';
        $condicionOk = $condicion === '' || $condicion === 'HABIDO';

        return $estadoOk && $condicionOk;
    }

    private function request(string $path, array $query): array
    {
        $base = rtrim((string) config('services.peru_api.base_url'), '/');
        $token = config('services.peru_api.token');

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('services.peru_api.timeout', 12))
                ->retry(2, 300, throw: false)
                ->get($base . $path, $query);
        } catch (\Throwable $e) {
            Log::warning('Consulta SUNAT/RENIEC falló (conexión)', ['path' => $path, 'error' => $e->getMessage()]);
            throw new RuntimeException('No se pudo conectar con el servicio de consulta. Intenta nuevamente.');
        }

        if ($response->status() === 404) {
            throw new RuntimeException('No se encontraron datos para el documento indicado.');
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException('El token de consulta (PERU_API_TOKEN) es inválido o expiró.');
        }

        if ($response->status() === 422) {
            throw new RuntimeException('El número de documento consultado no es válido.');
        }

        if (! $response->successful()) {
            Log::warning('Consulta SUNAT/RENIEC no exitosa', ['path' => $path, 'status' => $response->status()]);
            throw new RuntimeException('El servicio de consulta no está disponible en este momento.');
        }

        $json = $response->json();

        // Algunos proveedores envuelven la respuesta en {"data": {...}}.
        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }

        return is_array($json) ? $json : [];
    }
}
