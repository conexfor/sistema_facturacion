<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoidedDocument extends Model
{
    use BelongsToCompany;

    /**
     * Aviso transitorio (no persistido) cuando el reembolso a caja al anular
     * la venta no pudo registrarse por falta de una sesión de caja abierta.
     * Se llena en VoidedService y se lee en el controlador; nunca se guarda.
     */
    public ?string $advertenciaCaja = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'sale_id',
        'fecha_referencia',
        'fecha_comunicacion',
        'correlativo',
        'identificador',
        'motivo',
        'ticket',
        'estado',
        'xml_path',
        'cdr_path',
        'mensaje',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_referencia' => 'date',
            'fecha_comunicacion' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
