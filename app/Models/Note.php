<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Note extends Model
{
    use BelongsToCompany;

    /**
     * Aviso transitorio (no persistido) cuando el reembolso a caja de una
     * nota de crédito no pudo registrarse por falta de una sesión de caja
     * abierta. Se llena en NoteService y se lee en el controlador para
     * avisar al usuario; nunca se guarda en la base de datos.
     */
    public ?string $advertenciaCaja = null;

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'sale_id',
        'tipo',
        'tipo_doc',
        'serie',
        'correlativo',
        'fecha',
        'moneda',
        'cod_motivo',
        'des_motivo',
        'subtotal',
        'igv',
        'total',
        'sunat_estado',
        'sunat_xml_path',
        'sunat_cdr_path',
        'sunat_hash',
        'sunat_mensaje',
        'sunat_enviado_at',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'sunat_enviado_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(NoteItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNumeroAttribute(): string
    {
        return $this->serie . '-' . str_pad((string) $this->correlativo, 6, '0', STR_PAD_LEFT);
    }
}
