<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespatchItem extends Model
{
    protected $fillable = [
        'despatch_id',
        'product_id',
        'descripcion',
        'cantidad',
        'unidad',
    ];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:4'];
    }

    public function despatch(): BelongsTo
    {
        return $this->belongsTo(Despatch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
