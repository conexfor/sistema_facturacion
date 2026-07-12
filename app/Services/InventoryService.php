<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Centraliza los movimientos de inventario manteniendo el stock por almacén
 * y el costo promedio ponderado a nivel de producto (kardex valorizado).
 */
class InventoryService
{
    /**
     * Registra un movimiento de inventario de forma atómica.
     *
     * @param  string  $tipo  entrada|salida|ajuste_entrada|ajuste_salida|transferencia_entrada|transferencia_salida
     */
    public function registrar(
        Product $product,
        Warehouse $warehouse,
        string $tipo,
        float $cantidad,
        ?float $costoUnitario = null,
        ?string $motivo = null,
        ?string $referencia = null,
        ?Carbon $fecha = null,
    ): InventoryMovement {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad del movimiento debe ser mayor a cero.');
        }

        return DB::transaction(function () use (
            $product, $warehouse, $tipo, $cantidad, $costoUnitario, $motivo, $referencia, $fecha
        ) {
            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $esEntrada = in_array($tipo, InventoryMovement::ENTRADAS, true);

            $stock = ProductStock::lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['cantidad' => 0],
            );

            $cantTotalAntes = (float) ProductStock::where('product_id', $product->id)->sum('cantidad');
            $costoProm = (float) $product->costo_promedio;
            $valorAntes = $cantTotalAntes * $costoProm;

            if ($esEntrada) {
                // Costo de la entrada: el indicado o, si no, el promedio vigente.
                $costoUnitario = $costoUnitario ?? $costoProm;
                $cantTotalDespues = $cantTotalAntes + $cantidad;
                $valorDespues = $valorAntes + ($cantidad * $costoUnitario);
                $nuevoCosto = $cantTotalDespues > 0
                    ? $valorDespues / $cantTotalDespues
                    : $costoUnitario;
                $nuevaCantStock = (float) $stock->cantidad + $cantidad;
            } else {
                if ($cantidad > (float) $stock->cantidad) {
                    throw new RuntimeException(
                        "Stock insuficiente en {$warehouse->nombre}: disponible "
                        . rtrim(rtrim(number_format((float) $stock->cantidad, 4, '.', ''), '0'), '.')
                        . ", solicitado {$cantidad}.",
                    );
                }
                // Las salidas se valorizan al promedio vigente (no lo alteran).
                $costoUnitario = $costoProm;
                $cantTotalDespues = $cantTotalAntes - $cantidad;
                $nuevoCosto = $costoProm;
                $valorDespues = $cantTotalDespues * $nuevoCosto;
                $nuevaCantStock = (float) $stock->cantidad - $cantidad;
            }

            $stock->update(['cantidad' => $nuevaCantStock]);
            $product->update(['costo_promedio' => $nuevoCosto]);

            return InventoryMovement::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => Auth::id(),
                'fecha' => $fecha ?? now(),
                'tipo' => $tipo,
                'motivo' => $motivo,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'saldo_cantidad' => $cantTotalDespues,
                'saldo_costo_promedio' => $nuevoCosto,
                'saldo_valor' => $valorDespues,
                'referencia' => $referencia,
            ]);
        });
    }

    public function entrada(
        Product $product,
        Warehouse $warehouse,
        float $cantidad,
        float $costoUnitario,
        ?string $motivo = null,
        ?string $referencia = null,
    ): InventoryMovement {
        return $this->registrar($product, $warehouse, 'entrada', $cantidad, $costoUnitario, $motivo, $referencia);
    }

    public function salida(
        Product $product,
        Warehouse $warehouse,
        float $cantidad,
        ?string $motivo = null,
        ?string $referencia = null,
    ): InventoryMovement {
        return $this->registrar($product, $warehouse, 'salida', $cantidad, null, $motivo, $referencia);
    }

    /**
     * Ajusta el stock de un almacén a una cantidad objetivo, generando el
     * movimiento de ajuste correspondiente (entrada o salida).
     */
    public function ajustar(
        Product $product,
        Warehouse $warehouse,
        float $cantidadObjetivo,
        ?string $motivo = null,
    ): ?InventoryMovement {
        $actual = (float) ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('cantidad') ?? 0;

        $diff = round($cantidadObjetivo - $actual, 4);

        if ($diff === 0.0) {
            return null;
        }

        $tipo = $diff > 0 ? 'ajuste_entrada' : 'ajuste_salida';

        return $this->registrar(
            $product,
            $warehouse,
            $tipo,
            abs($diff),
            $diff > 0 ? (float) $product->costo_promedio : null,
            $motivo ?? 'Ajuste de inventario',
        );
    }
}
