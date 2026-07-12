<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SunatController extends Controller
{
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

        if ($request->hasFile('certificado')) {
            $contents = file_get_contents($request->file('certificado')->getRealPath());
            $pwd = $data['certificado_password'] ?? '';
            $pem = $contents;

            // Si es un .pfx/.p12, convertir a PEM (clave privada + certificado)
            $certs = [];
            if (@openssl_pkcs12_read($contents, $certs, $pwd)) {
                $pem = $certs['pkey'] . $certs['cert'];
            } elseif ($pwd !== '' || str_contains(strtolower($request->file('certificado')->getClientOriginalName()), '.pfx')) {
                return back()->withErrors([
                    'certificado' => 'No se pudo leer el certificado .pfx (clave incorrecta o formato no soportado).',
                ]);
            }

            $path = "sunat/{$company->ruc}/cert.pem";
            Storage::put($path, $pem);
            $company->certificate_path = $path;
        }

        $company->save();

        return back()->with('success', 'Configuración SUNAT actualizada.');
    }
}
