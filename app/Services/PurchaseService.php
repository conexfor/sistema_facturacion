<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * Registra una compra: cabecera, ítems, ingreso de stock al kardex
     * (costo neto) y la cuenta por pagar correspondiente.
     */
    public function registrar(array $data): Purchase
    {
        if (empty($data['items'])) {
            throw new RuntimeException('La compra debe tener al menos un producto.');
        }

        $user = Auth::user();
        $company = $user->company;
        $igvPct = (float) ($company->igv_porcentaje ?? 18);

        $warehouse = Warehouse::findOrFail($data['warehouse_id']);
        $supplier = ! empty($data['supplier_id'])
            ? Supplier::findOrFail($data['supplier_id'])
            : null;

        $condicion = $data['condicion_pago'] ?? 'contado';

        return DB::transaction(function () use ($data, $user, $company, $igvPct, $warehouse, $supplier, $condicion) {
            $subtotal = 0.0;
            $igvTotal = 0.0;
            $total = 0.0;
            $lineas = [];

            foreach ($data['items'] as $row) {
                $product = Product::findOrFail($row['product_id']);
                $cantidad = (float) $row['cantidad'];
                $costo = (float) $row['costo_unitario'];
                $descuento = round((float) ($row['descuento'] ?? 0), 2);

                if ($cantidad <= 0) {
                    throw new RuntimeException("Cantidad inválida para {$product->nombre}.");
                }

                $base = round($cantidad * $costo - $descuento, 2);
                if ($base < 0) {
                    throw new RuntimeException("El descuento supera el importe en {$product->nombre}.");
                }

                $afecto = array_key_exists('afecto_igv', $row)
                    ? (bool) $row['afecto_igv']
                    : (bool) $product->afecto_igv;
                $igvLinea = $afecto ? round($base * $igvPct / 100, 2) : 0.0;
                $totalLinea = round($base + $igvLinea, 2);

                $subtotal += $base;
                $igvTotal += $igvLinea;
                $total += $totalLinea;

                $lineas[] = [
                    'product' => $product,
                    'costo' => $costo,
                    'cantidad' => $cantidad,
                    'attrs' => [
                        'product_id' => $product->id,
                        'descripcion' => $product->nombre,
                        'cantidad' => $cantidad,
                        'costo_unitario' => $costo,
                        'descuento' => $descuento,
                        'afecto_igv' => $afecto,
                        'base' => $base,
                        'igv' => $igvLinea,
                        'total' => $totalLinea,
                    ],
                ];
            }

            $total = round($total, 2);

            // Percepción que el proveedor (agente) cobra sobre el total
            $percepcionTasa = round((float) ($data['percepcion_tasa'] ?? 0), 2);
            $percepcion = $percepcionTasa > 0 ? round($total * $percepcionTasa / 100, 2) : 0.0;
            $totalPagar = round($total + $percepcion, 2);

            $purchase = Purchase::create([
                'company_id' => $company->id,
                'branch_id' => $user->branch_id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplier?->id,
                'user_id' => $user->id,
                'tipo_comprobante' => $data['tipo_comprobante'],
                'serie' => $data['serie'] ?? null,
                'numero' => $data['numero'] ?? null,
                'fecha' => $data['fecha'],
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                'moneda' => $company->moneda ?? 'PEN',
                'subtotal' => round($subtotal, 2),
                'igv' => round($igvTotal, 2),
                'total' => $total,
                'percepcion_tasa' => $percepcionTasa,
                'percepcion_monto' => $percepcion,
                'monto_pagado' => $condicion === 'contado' ? $totalPagar : 0,
                'estado_pago' => $condicion === 'contado' ? 'pagada' : 'pendiente',
                'observacion' => $data['observacion'] ?? null,
            ]);

            $referencia = 'Compra ' . ($purchase->numero_completo ?: "#{$purchase->id}");

            foreach ($lineas as $linea) {
                $purchase->items()->create($linea['attrs']);

                /** @var Product $product */
                $product = $linea['product'];
                if ($product->controla_stock) {
                    $this->inventory->entrada(
                        $product,
                        $warehouse,
                        (float) $linea['cantidad'],
                        (float) $linea['costo'],
                        'Compra a ' . ($supplier?->razon_social ?? 'proveedor'),
                        $referencia,
                    );
                }

                // Actualiza el último costo de compra de referencia
                $product->update(['precio_compra' => $linea['costo']]);
            }

            if ($condicion === 'contado') {
                $purchase->payments()->create([
                    'user_id' => $user->id,
                    'fecha' => $data['fecha'],
                    'monto' => $totalPagar,
                    'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                    'observacion' => 'Pago al contado',
                ]);
            }

            return $purchase;
        });
    }

    /** Registra un pago a una cuenta por pagar y actualiza su estado. */
    public function registrarPago(
        Purchase $purchase,
        float $monto,
        string $metodo,
        ?Carbon $fecha = null,
        ?string $observacion = null,
    ): PurchasePayment {
        return DB::transaction(function () use ($purchase, $monto, $metodo, $fecha, $observacion) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            $saldo = $purchase->saldo;

            if ($monto <= 0) {
                throw new RuntimeException('El monto del pago debe ser mayor a cero.');
            }
            if ($monto > $saldo + 0.005) {
                throw new RuntimeException('El pago no puede exceder el saldo pendiente (' . number_format($saldo, 2) . ').');
            }

            $payment = $purchase->payments()->create([
                'user_id' => Auth::id(),
                'fecha' => $fecha ?? now(),
                'monto' => round($monto, 2),
                'metodo_pago' => $metodo,
                'observacion' => $observacion,
            ]);

            $pagado = round((float) $purchase->monto_pagado + $monto, 2);
            $purchase->update([
                'monto_pagado' => $pagado,
                'estado_pago' => $pagado + 0.005 >= $purchase->total_pagar ? 'pagada' : 'parcial',
            ]);

            return $payment;
        });
    }
}
