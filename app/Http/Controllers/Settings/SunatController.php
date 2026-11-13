<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\DocumentLookupService;
use App\Services\Sunat\CertificateInspector;
use App\Services\Sunat\CertificateLoader;
use App\Services\Sunat\PfxConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SunatController extends Controller
{
    /** Datos fiscales que se toman del certificado / consulta RUC. */
    private const CAMPOS_FISCALES = ['razon_social', 'nombre_comercial', 'direccion', 'ubigeo', 'departamento', 'provincia', 'distrito'];

    /** Datos de contacto que pertenecían a la empresa anterior al cambiar de RUC. */
    private const CAMPOS_CONTACTO = ['telefono', 'email'];

    public function __construct(private readonly DocumentLookupService $lookup)
    {
    }

    public function edit(Request $request): Response
    {
        $company = $request->user()->company;

        return Inertia::render('Settings/Sunat/Edit', [
            'company' => [
                'ruc' => $company->ruc,
                'razon_social' => $company->razon_social,
                'ubigeo' => $company->ubigeo,
                'departamento' => $company->departamento,
                'provincia' => $company->provincia,
                'distrito' => $company->distrito,
                'direccion' => $company->direccion,
                'sunat_modo' => $company->sunat_modo,
                'agente_percepcion' => (bool) $company->agente_percepcion,
                'percepcion_tasa' => (float) ($company->percepcion_tasa ?? 2),
                'sol_user' => $company->sol_user,
                'tiene_certificado' => ! empty($company->certificate_path),
                'tiene_clave_sol' => ! empty($company->sol_pass),
                'gre_client_id' => $company->gre_client_id,
                'tiene_gre_secret' => ! empty($company->gre_client_secret),
            ],
            'configurado' => $company->sunatConfigurado(),
            'certificado' => $this->certificado($company),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $request->user()->company;

        $data = $request->validate([
            'ubigeo' => ['nullable', 'string', 'size:6'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            'distrito' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'sunat_modo' => ['required', Rule::in(['beta', 'produccion'])],
            'agente_percepcion' => ['boolean'],
            'percepcion_tasa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sol_user' => ['nullable', 'string', 'max:50'],
            'sol_pass' => ['nullable', 'string', 'max:100'],
            'certificado' => ['nullable', 'file', 'max:5120'],
            'certificado_password' => ['nullable', 'string', 'max:100'],
            'gre_client_id' => ['nullable', 'string', 'max:100'],
            'gre_client_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $company->fill([
            'ubigeo' => $data['ubigeo'] ?? $company->ubigeo,
            'departamento' => $data['departamento'] ?? $company->departamento,
            'provincia' => $data['provincia'] ?? $company->provincia,
            'distrito' => $data['distrito'] ?? $company->distrito,
            'direccion' => $data['direccion'] ?? $company->direccion,
            'sunat_modo' => $data['sunat_modo'],
            'agente_percepcion' => $request->boolean('agente_percepcion'),
            'percepcion_tasa' => $data['percepcion_tasa'] ?? ($company->percepcion_tasa ?? 2),
            'sol_user' => $data['sol_user'] ?? $company->sol_user,
            'gre_client_id' => $data['gre_client_id'] ?? $company->gre_client_id,
        ]);

        if (! empty($data['sol_pass'])) {
            $company->sol_pass = $data['sol_pass'];
        }

        if (! empty($data['gre_client_secret'])) {
            $company->gre_client_secret = $data['gre_client_secret'];
        }

        if (! $request->hasFile('certificado')) {
            $company->save();

            return back()->with('success', 'Configuración SUNAT actualizada.');
        }

        $contents = file_get_contents($request->file('certificado')->getRealPath());
        $pem = $contents;

        // Si no es PEM en texto, es un .pfx/.p12 binario: convertir a PEM.
        if (! str_contains($contents, '-----BEGIN')) {
            try {
                $pem = PfxConverter::toPem($contents, $data['certificado_password'] ?? '');
            } catch (RuntimeException $e) {
                return back()->withErrors(['certificado' => $e->getMessage()]);
            }
        }

        $info = CertificateInspector::inspect($pem);
        if (! $info || ! @openssl_pkey_get_private($pem)) {
            return back()->withErrors(['certificado' => 'El archivo debe contener el certificado y su clave privada.']);
        }

        if ($info['ruc'] && $this->rucEnOtraEmpresa($company, $info['ruc'])) {
            return back()->withErrors(['certificado' => "El RUC {$info['ruc']} del certificado ya está registrado en otra empresa del sistema."]);
        }

        $path = "sunat/{$company->ruc}/cert.pem";
        Storage::put($path, $pem);
        $company->certificate_path = $path;

        if (! $info['ruc']) {
            $company->save();

            return back()->with('warning', 'Certificado cargado, pero no se encontró el RUC del titular. Verifica los datos de la empresa manualmente.');
        }

        [$tipo, $mensaje] = $this->aplicarDatosCertificado($company, $info);

        return back()->with($tipo, $mensaje);
    }

    /** Aplica los datos del certificado ya cargado (botón del panel). */
    public function aplicarCertificado(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        $info = $this->certificado($company);

        if (! $info || ! $info['ruc']) {
            return back()->with('error', 'No se encontró un RUC válido en el certificado cargado.');
        }

        if ($this->rucEnOtraEmpresa($company, $info['ruc'])) {
            return back()->with('error', "El RUC {$info['ruc']} ya está registrado en otra empresa del sistema.");
        }

        [$tipo, $mensaje] = $this->aplicarDatosCertificado($company, $info);

        return redirect()->route('settings.sunat.edit')->with($tipo, $mensaje);
    }

    /**
     * Sincroniza los datos de la empresa con el titular del certificado,
     * consultando su RUC en SUNAT cuando la consulta está configurada.
     *
     * Si el RUC cambia, los datos de la empresa anterior se descartan: lo que
     * no venga del certificado o de SUNAT queda vacío para completarlo a mano,
     * nunca con datos de la empresa anterior (p. ej. los de demostración).
     *
     * @return array{0: string, 1: string} [tipo de flash, mensaje]
     */
    private function aplicarDatosCertificado(Company $company, array $info): array
    {
        $ruc = $info['ruc'];
        $cambiaRuc = $ruc !== $company->ruc;

        [$datos, $error] = $this->consultarRuc($ruc);
        $datos['razon_social'] = ($datos['razon_social'] ?? null) ?: $info['titular'];

        if ($cambiaRuc) {
            foreach (array_merge(self::CAMPOS_FISCALES, self::CAMPOS_CONTACTO) as $campo) {
                $company->{$campo} = null;
            }
            $company->ruc = $ruc;
        }

        foreach (self::CAMPOS_FISCALES as $campo) {
            if (! empty($datos[$campo])) {
                $company->{$campo} = $datos[$campo];
            }
        }

        // razon_social es obligatoria en la tabla.
        $company->razon_social ??= $ruc;

        // El certificado vive en la carpeta del RUC de la empresa.
        $nuevoPath = "sunat/{$ruc}/cert.pem";
        if ($company->certificate_path && $company->certificate_path !== $nuevoPath
            && Storage::exists($company->certificate_path)) {
            Storage::move($company->certificate_path, $nuevoPath);
            $company->certificate_path = $nuevoPath;
        }

        $company->save();

        if ($error === null) {
            return ['success', "Certificado cargado. Datos de {$company->razon_social} (RUC {$ruc}) actualizados desde SUNAT."];
        }

        $faltan = collect(['direccion' => 'dirección', 'ubigeo' => 'ubigeo'])
            ->filter(fn ($_, $campo) => empty($company->{$campo}))
            ->values()
            ->implode(' y ');

        return ['warning', "Certificado cargado. RUC {$ruc} y razón social tomados del certificado. "
            . "No se pudo consultar SUNAT ({$error})"
            . ($faltan !== '' ? "; completa {$faltan} manualmente." : '.')];
    }

    /** @return array{0: array, 1: ?string} [datos, error] */
    private function consultarRuc(string $ruc): array
    {
        if (! $this->lookup->isEnabled()) {
            return [[], 'consulta de RUC no configurada: PERU_API_TOKEN'];
        }

        try {
            return [$this->lookup->lookup('6', $ruc), null];
        } catch (RuntimeException $e) {
            return [[], rtrim($e->getMessage(), '.')];
        }
    }

    private function rucEnOtraEmpresa(Company $company, string $ruc): bool
    {
        return Company::where('ruc', $ruc)->where('id', '!=', $company->id)->exists();
    }

    /** Datos del certificado cargado, o null si no hay o no se puede leer. */
    private function certificado(Company $company): ?array
    {
        if (! $company->certificate_path) {
            return null;
        }

        try {
            $info = CertificateInspector::inspect(CertificateLoader::pem($company));
        } catch (RuntimeException) {
            return null;
        }

        return $info ? $info + ['coincide' => $info['ruc'] === $company->ruc] : null;
    }
}
