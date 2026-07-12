<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $productId = $this->route('product')?->id;

        return [
            'codigo' => [
                'required', 'string', 'max:50',
                Rule::unique('products', 'codigo')
                    ->where('company_id', $companyId)
                    ->ignore($productId),
            ],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('company_id', $companyId),
            ],
            'unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where('company_id', $companyId),
            ],
            'tipo' => ['required', Rule::in(['bien', 'servicio'])],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'afecto_igv' => ['boolean'],
            'tipo_afectacion_igv' => ['nullable', Rule::in(['10', '20', '30'])],
            'controla_stock' => ['boolean'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],

            // Stock inicial opcional (solo al crear)
            'stock_inicial' => ['nullable', 'array'],
            'stock_inicial.*.warehouse_id' => [
                'required_with:stock_inicial',
                Rule::exists('warehouses', 'id')->where('company_id', $companyId),
            ],
            'stock_inicial.*.cantidad' => ['nullable', 'numeric', 'min:0'],
            'stock_inicial.*.costo' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'category_id' => 'categoría',
            'unit_id' => 'unidad de medida',
            'precio_compra' => 'precio de compra',
            'precio_venta' => 'precio de venta',
            'stock_minimo' => 'stock mínimo',
        ];
    }
}
