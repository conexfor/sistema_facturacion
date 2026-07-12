<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuoteService
{
    private const SERIES = [
        'cotizacion' => 'COT1',
        'pedido' => 'PED1',
    ];

    public function __construct(private readonly SaleService $sales)
    {
    }

    public function registrar(array $data): Quote
    {
        if (empty($data['items'])) {
            throw new RuntimeException('El documento debe tener al menos un producto.');
        }

        $user = Auth::user();
        $company = $user->company;
        $igvPct = (float) ($company->igv_porcentaje ?? 18);
        $tipo = $data['tipo'];
        $serie = self::SERIES[$tipo] ?? 'DOC1';

        $customer = ! empty($data['customer_id']) ? Customer::findOrFail($data['customer_id']) : null;

        return DB::transaction(function () use ($data, $user, $company, $igvPct, $tipo, $serie, $customer) {
            $subtotal = 0.0;
            $igvTotal = 0.0;
            $descuentoTotal = 0.0;
            $total = 0.0;
            $lineas = [];

            foreach ($data['items'] as $row) {
                $product = Product::find($row['product_id'] ?? null);
                $cantidad = (float) $row['cantidad'];
                $precio = (float) $row['precio_unitario'];
                $descuento = round((float) ($row['descuento'] ?? 0), 2);
                $afecto = $product ? (bool) $product->afecto_igv : (bool) ($row['afecto_igv'] ?? true);

                $totalLinea = round($cantidad * $precio - $descuento, 2);
                if ($totalLinea < 0) {
                    throw new RuntimeException('El descuento supera el importe en una línea.');
                }
                $igvLinea = $afecto ? round($totalLinea - $totalLinea / (1 + $igvPct / 100), 2) : 0.0;
                $baseLinea = round($totalLinea - $igvLinea, 2);

                $subtotal += $baseLinea;
                $igvTotal += $igvLinea;
                $descuentoTotal += $descuento;
                $total += $totalLinea;

                $lineas[] = [
                    'product_id' => $product?->id,
                    'descripcion' => $product?->nombre ?? ($row['descripcion'] ?? 'Ítem'),
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento' => $descuento,
                    'afecto_igv' => $afecto,
                    'base' => $baseLinea,
                    'igv' => $igvLinea,
                    'total' => $totalLinea,
                ];
            }

            $correlativo = (int) Quote::where('tipo', $tipo)->where('serie', $serie)
                ->lockForUpdate()->max('correlativo');
            $correlativo++;

            $quote = Quote::create([
                'company_id' => $company->id,
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'tipo' => $tipo,
                'serie' => $serie,
                'correlativo' => $correlativo,
                'fecha' => $data['fecha'] ?? now()->toDateString(),
                'fecha_validez' => $data['fecha_validez'] ?? null,
                'moneda' => $company->moneda ?? 'PEN',
                'cliente_documento' => $customer?->numero_documento,
                'cliente_nombre' => $customer?->razon_social ?? 'Sin cliente',
                'subtotal' => round($subtotal, 2),
                'igv' => round($igvTotal, 2),
                'descuento_total' => round($descuentoTotal, 2),
                'total' => round($total, 2),
                'observacion' => $data['observacion'] ?? null,
            ]);

            foreach ($lineas as $linea) {
                $quote->items()->create($linea);
            }

            return $quote;
        });
    }

    /** Convierte el documento en una venta real (descuenta stock). */
    public function convertirEnVenta(Quote $quote, array $data): Sale
    {
        if (in_array($quote->estado, ['convertida', 'anulada'], true)) {
            throw new RuntimeException('Este documento ya fue ' . $quote->estado . '.');
        }

        $quote->loadMissing('items');

        $sale = $this->sales->registrar([
            'tipo_comprobante' => $data['tipo_comprobante'],
            'warehouse_id' => $data['warehouse_id'],
            'customer_id' => $quote->customer_id,
            'metodo_pago' => $data['metodo_pago'],
            'monto_recibido' => $data['monto_recibido'] ?? null,
            'observacion' => 'Generada desde ' . ucfirst($quote->tipo) . ' ' . $quote->numero,
            'items' => $quote->items->map(fn ($it) => [
                'product_id' => $it->product_id,
                'cantidad' => (float) $it->cantidad,
                'precio_unitario' => (float) $it->precio_unitario,
                'descuento' => (float) $it->descuento,
            ])->all(),
        ]);

        $quote->update(['estado' => 'convertida', 'sale_id' => $sale->id]);

        return $sale;
    }

    public function cambiarEstado(Quote $quote, string $estado): void
    {
        if (in_array($quote->estado, ['convertida'], true)) {
            throw new RuntimeException('No se puede cambiar el estado de un documento ya convertido.');
        }
        $quote->update(['estado' => $estado]);
    }
}
