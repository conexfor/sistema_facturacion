<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $carrierId = $this->route('carrier')?->id;

        return [
            'ruc' => [
                'required', 'digits:11',
                Rule::unique('carriers', 'ruc')
                    ->where('company_id', $companyId)
                    ->ignore($carrierId),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'nro_mtc' => ['nullable', 'string', 'max:30'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ruc' => 'RUC',
            'razon_social' => 'razón social',
            'nro_mtc' => 'registro MTC',
        ];
    }
}
