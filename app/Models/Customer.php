<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'estado_contribuyente',
        'condicion_domicilio',
        'email',
        'telefono',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * ¿El contribuyente está habilitado para recibir comprobantes?
     * (ACTIVO + HABIDO). Devuelve null si no hay datos de SUNAT.
     */
    public function getHabilitadoSunatAttribute(): ?bool
    {
        if (empty($this->estado_contribuyente) && empty($this->condicion_domicilio)) {
            return null;
        }

        $estadoOk = empty($this->estado_contribuyente) || strtoupper($this->estado_contribuyente) === 'ACTIVO';
        $condicionOk = empty($this->condicion_domicilio) || strtoupper($this->condicion_domicilio) === 'HABIDO';

        return $estadoOk && $condicionOk;
    }

    public const TIPOS = [
        '1' => 'DNI',
        '6' => 'RUC',
        '7' => 'Pasaporte',
        '4' => 'Carné de extranjería',
        '0' => 'Otros',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getTipoDocumentoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo_documento] ?? 'Doc.';
    }
}
