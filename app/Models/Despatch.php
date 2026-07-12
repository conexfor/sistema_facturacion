<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Despatch extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_traslado' => 'date',
            'peso_total' => 'decimal:3',
            'sunat_enviado_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DespatchItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNumeroAttribute(): string
    {
        return $this->serie . '-' . str_pad((string) $this->correlativo, 6, '0', STR_PAD_LEFT);
    }
}
