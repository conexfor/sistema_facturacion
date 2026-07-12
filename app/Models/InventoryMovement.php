<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'user_id',
        'fecha',
        'tipo',
        'motivo',
        'cantidad',
        'costo_unitario',
        'saldo_cantidad',
        'saldo_costo_promedio',
        'saldo_valor',
        'referencia',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'cantidad' => 'decimal:4',
            'costo_unitario' => 'decimal:4',
            'saldo_cantidad' => 'decimal:4',
            'saldo_costo_promedio' => 'decimal:4',
            'saldo_valor' => 'decimal:4',
        ];
    }

    /** Tipos que incrementan stock. */
    public const ENTRADAS = ['entrada', 'ajuste_entrada', 'transferencia_entrada'];

    public function isEntrada(): bool
    {
        return in_array($this->tipo, self::ENTRADAS, true);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
