<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'placa',
        'marca',
        'modelo',
        'tipo',
        'cert_habilitacion',
        'capacidad_kg',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacidad_kg' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public const TIPOS = [
        'Camión',
        'Camioneta',
        'Furgón',
        'Tractocamión',
        'Motocarga',
        'Otro',
    ];

    public function despatches(): HasMany
    {
        return $this->hasMany(Despatch::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
