<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'cash_register_id',
        'user_id',
        'closed_by',
        'fecha_apertura',
        'monto_apertura',
        'fecha_cierre',
        'monto_esperado',
        'monto_contado',
        'diferencia',
        'estado',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'monto_apertura' => 'decimal:2',
            'monto_esperado' => 'decimal:2',
            'monto_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function totalIngresos(): float
    {
        return (float) $this->movements()->where('tipo', 'ingreso')->sum('monto');
    }

    public function totalEgresos(): float
    {
        return (float) $this->movements()->where('tipo', 'egreso')->sum('monto');
    }

    /** Efectivo esperado en caja = apertura + ingresos - egresos. */
    public function saldoEsperado(): float
    {
        return round((float) $this->monto_apertura + $this->totalIngresos() - $this->totalEgresos(), 2);
    }
}
