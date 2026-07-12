<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'user_id',
        'tipo_comprobante',
        'serie',
        'numero',
        'fecha',
        'fecha_vencimiento',
        'moneda',
        'subtotal',
        'igv',
        'total',
        'percepcion_tasa',
        'percepcion_monto',
        'monto_pagado',
        'estado_pago',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_vencimiento' => 'date',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'percepcion_tasa' => 'decimal:2',
            'percepcion_monto' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Total a pagar al proveedor = comprobante + percepción. */
    public function getTotalPagarAttribute(): float
    {
        return round((float) $this->total + (float) $this->percepcion_monto, 2);
    }

    public function getSaldoAttribute(): float
    {
        return round($this->total_pagar - (float) $this->monto_pagado, 2);
    }

    public function getNumeroCompletoAttribute(): string
    {
        return trim(($this->serie ? $this->serie . '-' : '') . ($this->numero ?? ''));
    }
}
