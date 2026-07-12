<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LotService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /** Ingresa un lote: suma al kardex y registra el lote con su vencimiento. */
    public function registrar(
        int $productId,
        int $warehouseId,
        string $lote,
        ?string $fechaVencimiento,
        float $cantidad,
        ?float $costo = null,
    ): ProductLot {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        $product = Product::findOrFail($productId);
        $warehouse = Warehouse::findOrFail($warehouseId);

        return DB::transaction(function () use ($product, $warehouse, $lote, $fechaVencimiento, $cantidad, $costo) {
            $this->inventory->entrada(
                $product, $warehouse, $cantidad,
                $costo ?? (float) $product->precio_compra,
                'Ingreso lote ' . $lote, $lote,
            );

            $registro = ProductLot::firstOrNew([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'lote' => $lote,
            ]);
            $registro->company_id = $product->company_id;
            $registro->fecha_vencimiento = $fechaVencimiento ?: $registro->fecha_vencimiento;
            $registro->cantidad = (float) $registro->cantidad + $cantidad;
            $registro->save();

            if (! $product->controla_lotes) {
                $product->update(['controla_lotes' => true]);
            }

            return $registro;
        });
    }

    /** Da de baja (merma/salida) cantidad de un lote. */
    public function merma(ProductLot $lot, float $cantidad, ?string $motivo = null): ProductLot
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }
        if ($cantidad > (float) $lot->cantidad) {
            throw new RuntimeException('La cantidad supera el saldo del lote.');
        }

        return DB::transaction(function () use ($lot, $cantidad, $motivo) {
            $product = $lot->product;
            $warehouse = $lot->warehouse;

            $this->inventory->salida(
                $product, $warehouse, $cantidad,
                $motivo ?? ('Merma lote ' . $lot->lote), $lot->lote,
            );

            $lot->update(['cantidad' => (float) $lot->cantidad - $cantidad]);

            return $lot->refresh();
        });
    }

    public function user(): ?int
    {
        return Auth::id();
    }
}
