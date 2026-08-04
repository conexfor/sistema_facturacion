<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $customerId = $this->route('customer')?->id;
        $tipoDoc = $this->input('tipo_documento');

        // DNI y RUC son los documentos que SUNAT valida en los comprobantes
        // electrónicos: exigimos la longitud exacta para no terminar
        // enviando un RUC/DNI vacío o inválido en una factura/boleta.
        $formatoDoc = match ($tipoDoc) {
            '1' => ['required', 'digits:8'],
            '6' => ['required', 'digits:11'],
            default => ['nullable', 'string', 'max:20'],
        };

        return [
            'tipo_documento' => ['required', Rule::in(array_keys(Customer::TIPOS))],
            'numero_documento' => [
                ...$formatoDoc,
                Rule::unique('customers', 'numero_documento')
                    ->where(fn ($q) => $q->where('company_id', $companyId)
                        ->where('tipo_documento', $tipoDoc))
                    ->ignore($customerId),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ubigeo' => ['nullable', 'string', 'size:6'],
            'estado_contribuyente' => ['nullable', 'string', 'max:20'],
            'condicion_domicilio' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'razon_social' => 'nombre / razón social',
        ];
    }
}
