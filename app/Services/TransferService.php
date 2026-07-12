<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transfer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * Registra una transferencia: salida del almacén origen + entrada al
     * destino, manteniendo el costo promedio del producto.
     *
     * @param  array{origin_warehouse_id:int, dest_warehouse_id:int, observacion?:string|null,
     *   items: array<int, array{product_id:int, cantidad:float}>}  $data
     */
    public function registrar(array $data): Transfer
    {
        if (empty($data['items'])) {
            throw new RuntimeException('La transferencia debe tener al menos un producto.');
        }
        if ($data['origin_warehouse_id'] == $data['dest_warehouse_id']) {
            throw new RuntimeException('El almacén de origen y destino deben ser distintos.');
        }

        $user = Auth::user();
        $origin = Warehouse::findOrFail($data['origin_warehouse_id']);
        $dest = Warehouse::findOrFail($data['dest_warehouse_id']);

        return DB::transaction(function () use ($data, $user, $origin, $dest) {
            $transfer = Transfer::create([
                'company_id' => $user->company_id,
                'origin_warehouse_id' => $origin->id,
                'dest_warehouse_id' => $dest->id,
                'user_id' => $user->id,
                'fecha' => now(),
                'estado' => 'completada',
                'observacion' => $data['observacion'] ?? null,
            ]);

            $ref = "Transferencia #{$transfer->id}";
            $motivo = "{$origin->nombre} → {$dest->nombre}";

            foreach ($data['items'] as $row) {
                $product = Product::findOrFail($row['product_id']);
                $cantidad = (float) $row['cantidad'];

                if ($cantidad <= 0) {
                    throw new RuntimeException("Cantidad inválida para {$product->nombre}.");
                }

                // Salida del origen (valida stock y se valoriza al promedio vigente)
                $this->inventory->registrar(
                    $product, $origin, 'transferencia_salida', $cantidad, null, $motivo, $ref,
                );

                // Entrada al destino al mismo costo promedio (no altera el promedio)
                $costo = (float) $product->fresh()->costo_promedio;
                $this->inventory->registrar(
                    $product, $dest, 'transferencia_entrada', $cantidad, $costo, $motivo, $ref,
                );

                $transfer->items()->create([
                    'product_id' => $product->id,
                    'descripcion' => $product->nombre,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                ]);
            }

            return $transfer;
        });
    }
}
