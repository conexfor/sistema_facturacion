<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Company extends Model
{
    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'departamento',
        'provincia',
        'distrito',
        'telefono',
        'email',
        'logo_path',
        'moneda',
        'igv_porcentaje',
        'sunat_modo',
        'agente_percepcion',
        'percepcion_tasa',
        'percepcion_regimen',
        'sol_user',
        'sol_pass',
        'certificate_path',
        'certificate_password',
        'gre_client_id',
        'gre_client_secret',
        'is_active',
    ];

    protected $hidden = ['sol_pass', 'certificate_password', 'gre_client_secret'];

    protected function casts(): array
    {
        return [
            'igv_porcentaje' => 'decimal:2',
            'is_active' => 'boolean',
            'agente_percepcion' => 'boolean',
            'percepcion_tasa' => 'decimal:2',
        ];
    }

    /**
     * Credenciales sensibles cifradas en reposo. A diferencia del cast
     * `encrypted` nativo, si el valor almacenado no se puede descifrar
     * (p. ej. la APP_KEY cambió) devolvemos null en vez de lanzar una
     * excepción que tumbaría toda la página SUNAT. El usuario solo tiene
     * que volver a guardar la credencial para regenerarla con la clave actual.
     */
    protected function encryptedSecret(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return null;
                }
            },
            set: fn (?string $value) => $value === null || $value === ''
                ? null
                : Crypt::encryptString($value),
        );
    }

    protected function solPass(): Attribute
    {
        return $this->encryptedSecret();
    }

    protected function certificatePassword(): Attribute
    {
        return $this->encryptedSecret();
    }

    protected function greClientSecret(): Attribute
    {
        return $this->encryptedSecret();
    }

    /** ¿Tiene configuradas las credenciales para emitir ante SUNAT? */
    public function sunatConfigurado(): bool
    {
        return ! empty($this->sol_user)
            && ! empty($this->sol_pass)
            && ! empty($this->certificate_path);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function mainBranch(): ?Branch
    {
        return $this->branches()->where('is_main', true)->first()
            ?? $this->branches()->first();
    }
}
