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

        return [
            'tipo_documento' => ['required', Rule::in(array_keys(Customer::TIPOS))],
            'numero_documento' => [
                'nullable', 'string', 'max:20',
                Rule::unique('customers', 'numero_documento')
                    ->where(fn ($q) => $q->where('company_id', $companyId)
                        ->where('tipo_documento', $this->input('tipo_documento')))
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
