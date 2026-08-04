<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function edit(Request $request): Response
    {
        $company = $request->user()->company;

        return Inertia::render('Settings/Company/Edit', [
            'company' => [
                'ruc' => $company->ruc,
                'razon_social' => $company->razon_social,
                'nombre_comercial' => $company->nombre_comercial,
                'telefono' => $company->telefono,
                'email' => $company->email,
                'moneda' => $company->moneda,
                'igv_porcentaje' => (float) $company->igv_porcentaje,
                'logo_path' => $company->logo_path,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $request->user()->company;

        $data = $request->validate([
            'ruc' => [
                'required', 'digits:11',
                Rule::unique('companies', 'ruc')->ignore($company->id),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'moneda' => ['required', Rule::in(['PEN', 'USD'])],
            'igv_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $company->fill([
            'ruc' => $data['ruc'],
            'razon_social' => $data['razon_social'],
            'nombre_comercial' => $data['nombre_comercial'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'moneda' => $data['moneda'],
            'igv_porcentaje' => $data['igv_porcentaje'],
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = "companies/{$data['ruc']}/logo." . $file->getClientOriginalExtension();
            Storage::put($path, file_get_contents($file->getRealPath()));
            $company->logo_path = $path;
        }

        $company->save();

        return back()->with('success', 'Datos de la empresa actualizados.');
    }
}
