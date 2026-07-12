<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'customer_id',
        'user_id',
        'tipo_comprobante',
        'serie',
        'correlativo',
        'cliente_documento',
        'cliente_nombre',
        'cliente_direccion',
        'fecha',
        'moneda',
        'subtotal',
        'igv',
        'descuento_total',
        'total',
        'aplica_percepcion',
        'percepcion_tasa',
        'percepcion_monto',
        'monto_cobrado',
        'estado',
        'metodo_pago',
        'monto_recibido',
        'vuelto',
        'observacion',
        'sunat_estado',
        'sunat_xml_path',
        'sunat_cdr_path',
        'sunat_hash',
        'sunat_ticket',
        'sunat_mensaje',
        'sunat_enviado_at',
        'boleta_summary_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'descuento_total' => 'decimal:2',
            'total' => 'decimal:2',
            'aplica_percepcion' => 'boolean',
            'percepcion_tasa' => 'decimal:2',
            'percepcion_monto' => 'decimal:2',
            'monto_cobrado' => 'decimal:2',
            'monto_recibido' => 'decimal:2',
            'vuelto' => 'decimal:2',
            'sunat_enviado_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function getSaldoAttribute(): float
    {
        return round((float) $this->total - (float) $this->monto_cobrado, 2);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function boletaSummary(): BelongsTo
    {
        return $this->belongsTo(BoletaSummary::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
