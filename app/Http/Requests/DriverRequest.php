<?php

namespace App\Http\Requests;

use App\Models\Driver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'licencia' => strtoupper(trim((string) $this->input('licencia'))),
        ]);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $driverId = $this->route('driver')?->id;

        return [
            'tipo_doc' => ['required', Rule::in(array_keys(Driver::TIPOS_DOC))],
            'num_doc' => [
                'required', 'string', 'max:20',
                Rule::when($this->input('tipo_doc') === '1', ['digits:8']),
                Rule::unique('drivers', 'num_doc')
                    ->where(fn ($q) => $q->where('company_id', $companyId)
                        ->where('tipo_doc', $this->input('tipo_doc')))
                    ->ignore($driverId),
            ],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'licencia' => ['required', 'string', 'min:9', 'max:10'],
            'licencia_vence' => ['nullable', 'date'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_doc' => 'tipo de documento',
            'num_doc' => 'número de documento',
            'licencia_vence' => 'vencimiento de licencia',
        ];
    }
}
