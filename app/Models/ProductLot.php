<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ProductLot extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'lote',
        'fecha_vencimiento',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
            'cantidad' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** vigente | por_vencer (<=30 días) | vencido | sin_fecha */
    public function getEstadoAttribute(): string
    {
        if (! $this->fecha_vencimiento) {
            return 'sin_fecha';
        }
        $dias = Carbon::today()->diffInDays($this->fecha_vencimiento, false);
        if ($dias < 0) {
            return 'vencido';
        }

        return $dias <= 30 ? 'por_vencer' : 'vigente';
    }
}
