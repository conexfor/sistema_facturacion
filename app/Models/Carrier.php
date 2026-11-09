<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'ruc',
        'razon_social',
        'nro_mtc',
        'telefono',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function despatches(): HasMany
    {
        return $this->hasMany(Despatch::class);
    }
}
