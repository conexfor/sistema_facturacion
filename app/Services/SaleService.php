<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SaleService
{
    /** Serie por defecto según el tipo de comprobante. */
    private const SERIES = [
        'boleta' => 'B001',
        'factura' => 'F001',
        'nota_venta' => 'NV01',
    ];

    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * Registra una venta completa: cabecera, ítems, totales con IGV y la
     * salida de inventario correspondiente. Todo dentro de una transacción.
     *
     * @param  array{
     *   customer_id?: int|null, tipo_comprobante: string, warehouse_id: int,
     *   metodo_pago: string, monto_recibido?: float, observacion?: string|null,
     *   items: array<int, array{product_id:int, cantidad:float, precio_unitario:float, descuento?:float}>
     * }  $data
     */
    public function registrar(array $data): Sale
    {
        if (empty($data['items'])) {
            throw new RuntimeException('La venta debe tener al menos un producto.');
        }

        $user = Auth::user();
        $company = $user->company;
        $igvPct = (float) ($company->igv_porcentaje ?? 18);

        $tipo = $data['tipo_comprobante'];
        $serie = self::SERIES[$tipo] ?? 'V001';

        $warehouse = Warehouse::findOrFail($data['warehouse_id']);
        $customer = ! empty($data['customer_id'])
            ? Customer::findOrFail($data['customer_id'])
            : null;

        if ($tipo === 'factura' && (! $customer || $customer->tipo_documento !== '6')) {
            throw new RuntimeException('Para emitir una factura el cliente debe tener RUC.');
        }

        return DB::transaction(function () use ($data, $user, $company, $igvPct, $tipo, $serie, $warehouse, $customer) {
            $subtotal = 0.0;
            $igvTotal = 0.0;
            $descuentoTotal = 0.0;
            $total = 0.0;
            $lineas = [];

            foreach ($data['items'] as $row) {
                $product = Product::findOrFail($row['product_id']);
                $cantidad = (float) $row['cantidad'];
                $precio = (float) $row['precio_unitario'];
                $descuento = round((float) ($row['descuento'] ?? 0), 2);

                if ($cantidad <= 0) {
                    throw new RuntimeException("Cantidad inválida para {$product->nombre}.");
                }

                $totalLinea = round($cantidad * $precio - $descuento, 2);
                if ($totalLinea < 0) {
                    throw new RuntimeException("El descuento supera el importe en {$product->nombre}.");
                }

                $tipoAfectacion = $product->tipo_afectacion_igv ?? '10';
                $afecto = $tipoAfectacion === '10';
                $igvLinea = $afecto
                    ? round($totalLinea - ($totalLinea / (1 + $igvPct / 100)), 2)
                    : 0.0;
                $baseLinea = round($totalLinea - $igvLinea, 2);

                $subtotal += $baseLinea;
                $igvTotal += $igvLinea;
                $descuentoTotal += $descuento;
                $total += $totalLinea;

                $lineas[] = [
                    'product' => $product,
                    'attrs' => [
                        'product_id' => $product->id,
                        'descripcion' => $product->nombre,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'descuento' => $descuento,
                        'afecto_igv' => $afecto,
                        'tipo_afectacion' => $tipoAfectacion,
                        'base' => $baseLinea,
                        'igv' => $igvLinea,
                        'total' => $totalLinea,
                        'costo_unitario' => (float) $product->costo_promedio,
                    ],
                ];
            }

            $total = round($total, 2);
            $correlativo = (int) Sale::where('tipo_comprobante', $tipo)
                ->where('serie', $serie)
                ->lockForUpdate()
                ->max('correlativo');
            $correlativo++;

            // Percepción del IGV (solo si la empresa es Agente de Percepción)
            $aplicaPercepcion = (bool) ($company->agente_percepcion && ! empty($data['aplica_percepcion']));
            $percepcion = $aplicaPercepcion
                ? round($total * (float) $company->percepcion_tasa / 100, 2)
                : 0.0;
            $montoCobrar = round($total + $percepcion, 2);

            $metodo = $data['metodo_pago'];
            $montoRecibido = $metodo === 'efectivo'
                ? round((float) ($data['monto_recibido'] ?? $montoCobrar), 2)
                : $montoCobrar;
            $vuelto = max(0, round($montoRecibido - $montoCobrar, 2));

            $atributos = [
                'company_id' => $company->id,
                'branch_id' => $user->branch_id,
                'warehouse_id' => $warehouse->id,
                'customer_id' => $customer?->id,
                'user_id' => $user->id,
                'tipo_comprobante' => $tipo,
                'serie' => $serie,
                'correlativo' => $correlativo,
                'cliente_documento' => $customer?->numero_documento,
                'cliente_nombre' => $customer?->razon_social ?? 'Consumidor final',
                'fecha' => now(),
                'moneda' => $company->moneda ?? 'PEN',
                'subtotal' => round($subtotal, 2),
                'igv' => round($igvTotal, 2),
                'descuento_total' => round($descuentoTotal, 2),
                'total' => $total,
                'aplica_percepcion' => $aplicaPercepcion,
                'percepcion_tasa' => $aplicaPercepcion ? (float) $company->percepcion_tasa : 0,
                'percepcion_monto' => $percepcion,
                'monto_cobrado' => $metodo === 'credito' ? 0 : $total,
                'estado' => $metodo === 'credito' ? 'pendiente' : 'pagada',
                'metodo_pago' => $metodo,
                'monto_recibido' => $metodo === 'credito' ? 0 : $montoRecibido,
                'vuelto' => $vuelto,
                'observacion' => $data['observacion'] ?? null,
            ];

            // Snapshot del domicilio fiscal (columna añadida por migración).
            // Guardado defensivo: si la migración aún no corrió, la venta igual se registra.
            if (Schema::hasColumn('sales', 'cliente_direccion')) {
                $atributos['cliente_direccion'] = $customer?->direccion;
            }

            $sale = Sale::create($atributos);

            $numero = $sale->numero;

            foreach ($lineas as $linea) {
                $sale->items()->create($linea['attrs']);

                /** @var Product $product */
                $product = $linea['product'];
                if ($product->controla_stock) {
                    $this->inventory->salida(
                        $product,
                        $warehouse,
                        (float) $linea['attrs']['cantidad'],
                        "Venta {$numero}",
                        $numero,
                    );
                }
            }

            return $sale;
        });
    }
}
