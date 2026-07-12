<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'tipo_comprobante' => ['required', Rule::in(['factura', 'boleta', 'recibo', 'nota', 'ticket'])],
            'serie' => ['nullable', 'string', 'max:10'],
            'numero' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'condicion_pago' => ['required', Rule::in(['contado', 'credito'])],
            'metodo_pago' => ['nullable', Rule::in(['efectivo', 'transferencia', 'deposito', 'cheque', 'tarjeta', 'yape', 'plin'])],
            'percepcion_tasa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observacion' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.costo_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'items.*.afecto_igv' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Agrega al menos un producto a la compra.',
            'items.min' => 'Agrega al menos un producto a la compra.',
        ];
    }
}
