<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'origin_warehouse_id',
        'dest_warehouse_id',
        'user_id',
        'fecha',
        'estado',
        'observacion',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function dest(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'dest_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
