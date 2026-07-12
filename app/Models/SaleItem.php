<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'afecto_igv',
        'tipo_afectacion',
        'base',
        'igv',
        'total',
        'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:4',
            'precio_unitario' => 'decimal:4',
            'descuento' => 'decimal:2',
            'afecto_igv' => 'boolean',
            'base' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'costo_unitario' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
