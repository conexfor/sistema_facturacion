<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Driver extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'tipo_doc',
        'num_doc',
        'nombres',
        'apellidos',
        'licencia',
        'licencia_vence',
        'telefono',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'licencia_vence' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Catálogo 06 SUNAT (documentos de identidad admitidos para conductores). */
    public const TIPOS_DOC = [
        '1' => 'DNI',
        '4' => 'Carné de extranjería',
        '7' => 'Pasaporte',
    ];

    public function despatches(): HasMany
    {
        return $this->hasMany(Despatch::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    /** Licencia vencida a la fecha indicada (hoy por defecto). */
    public function licenciaVencida(?Carbon $fecha = null): bool
    {
        return $this->licencia_vence !== null
            && $this->licencia_vence->lt(($fecha ?? Carbon::today())->copy()->startOfDay());
    }
}
