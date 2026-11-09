<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected $appends = ['numero', 'costo_total', 'km_recorridos'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'salida_at' => 'datetime',
            'llegada_at' => 'datetime',
            'km_inicial' => 'decimal:1',
            'km_final' => 'decimal:1',
            'costo_combustible' => 'decimal:2',
            'costo_peajes' => 'decimal:2',
            'costo_otros' => 'decimal:2',
        ];
    }

    public const ESTADOS = [
        'programado' => 'Programado',
        'en_ruta' => 'En ruta',
        'entregado' => 'Entregado',
        'incidencia' => 'Incidencia',
        'cancelado' => 'Cancelado',
    ];

    /** Transiciones permitidas: estado actual => estados destino. */
    public const TRANSICIONES = [
        'programado' => ['en_ruta', 'cancelado'],
        'en_ruta' => ['entregado', 'incidencia'],
        'incidencia' => ['en_ruta', 'entregado'],
        'entregado' => [],
        'cancelado' => [],
    ];

    public function despatches(): HasMany
    {
        return $this->hasMany(Despatch::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNumeroAttribute(): string
    {
        return 'V-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getCostoTotalAttribute(): float
    {
        return round((float) $this->costo_combustible + (float) $this->costo_peajes + (float) $this->costo_otros, 2);
    }

    public function getKmRecorridosAttribute(): ?float
    {
        if ($this->km_inicial === null || $this->km_final === null) {
            return null;
        }

        return max(0, round((float) $this->km_final - (float) $this->km_inicial, 1));
    }

    /** Las guías solo se pueden asignar o quitar antes de cerrar el viaje. */
    public function esEditable(): bool
    {
        return in_array($this->estado, ['programado', 'en_ruta', 'incidencia'], true);
    }
}
