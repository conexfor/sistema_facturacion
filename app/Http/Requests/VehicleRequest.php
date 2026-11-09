<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // SUNAT espera la placa sin guiones ni espacios (ej. ABC123).
        $this->merge([
            'placa' => strtoupper(preg_replace('/[\s-]+/', '', (string) $this->input('placa'))),
        ]);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'placa' => [
                'required', 'string', 'regex:/^[A-Z0-9]{6,8}$/',
                Rule::unique('vehicles', 'placa')
                    ->where('company_id', $companyId)
                    ->ignore($vehicleId),
            ],
            'marca' => ['nullable', 'string', 'max:60'],
            'modelo' => ['nullable', 'string', 'max:60'],
            'tipo' => ['nullable', 'string', 'max:30'],
            'cert_habilitacion' => ['nullable', 'string', 'max:30'],
            'capacidad_kg' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cert_habilitacion' => 'certificado de habilitación',
            'capacidad_kg' => 'capacidad (kg)',
        ];
    }

    public function messages(): array
    {
        return [
            'placa.regex' => 'La placa debe tener entre 6 y 8 letras o números (ej. ABC123).',
        ];
    }
}
