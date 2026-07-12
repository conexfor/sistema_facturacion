<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'category_id',
        'unit_id',
        'codigo',
        'codigo_barras',
        'nombre',
        'descripcion',
        'tipo',
        'precio_compra',
        'precio_venta',
        'afecto_igv',
        'tipo_afectacion_igv',
        'costo_promedio',
        'stock_minimo',
        'controla_stock',
        'controla_lotes',
        'imagen_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:4',
            'precio_venta' => 'decimal:4',
            'costo_promedio' => 'decimal:4',
            'stock_minimo' => 'decimal:4',
            'afecto_igv' => 'boolean',
            'controla_stock' => 'boolean',
            'controla_lotes' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Catálogo 07 SUNAT. */
    public const TIPOS_AFECTACION = [
        '10' => 'Gravado (IGV)',
        '20' => 'Exonerado',
        '30' => 'Inafecto',
    ];

    protected static function booted(): void
    {
        // Mantiene afecto_igv en sincronía con el tipo de afectación.
        static::saving(function (Product $product) {
            $product->afecto_igv = ($product->tipo_afectacion_igv ?? '10') === '10';
        });
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ProductLot::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
