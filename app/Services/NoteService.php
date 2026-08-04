<?php

namespace App\Services;

use App\Models\Note;
use App\Models\NoteItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\Sunat\GreenterSender;
use App\Services\Sunat\NoteBuilder;
use App\Services\Sunat\SimulatedSender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class NoteService
{
    /** Catálogo 09 SUNAT (motivos de nota de crédito). */
    public const MOTIVOS_CREDITO = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '09' => 'Disminución en el valor',
        '10' => 'Otros conceptos',
    ];

    /** Catálogo 10 SUNAT (motivos de nota de débito). */
    public const MOTIVOS_DEBITO = [
        '01' => 'Intereses por mora',
        '02' => 'Aumento en el valor',
        '03' => 'Penalidades u otros conceptos',
    ];

    public function __construct(
        private readonly NoteBuilder $builder,
        private readonly InventoryService $inventory,
        private readonly CashService $cash,
    ) {
    }

    /**
     * Crea una nota de crédito que anula la venta y devuelve el stock.
     */
    public function registrarCredito(Sale $sale, string $codMotivo, string $desMotivo): Note
    {
        $this->validarVenta($sale);
        if ($sale->estado === 'anulada') {
            throw new RuntimeException('La venta ya está anulada.');
        }

        return DB::transaction(function () use ($sale, $codMotivo, $desMotivo) {
            $serie = $sale->tipo_comprobante === 'factura' ? 'FC01' : 'BC01';
            $note = $this->crearCabecera($sale, 'credito', '07', $serie, $codMotivo, $desMotivo, [
                'subtotal' => (float) $sale->subtotal,
                'igv' => (float) $sale->igv,
                'total' => (float) $sale->total,
            ]);

            $sale->loadMissing('items');
            $warehouse = $sale->warehouse_id ? Warehouse::find($sale->warehouse_id) : null;

            foreach ($sale->items as $item) {
                $note->items()->create([
                    'product_id' => $item->product_id,
                    'sale_item_id' => $item->id,
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'descuento' => $item->descuento,
                    'afecto_igv' => $item->afecto_igv,
                    'base' => $item->base,
                    'igv' => $item->igv,
                    'total' => $item->total,
                ]);

                // Devolución de stock al almacén de la venta
                if ($warehouse && $item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product && $product->controla_stock) {
                        $this->inventory->entrada(
                            $product, $warehouse, (float) $item->cantidad, (float) $item->costo_unitario,
                            'Anulación venta ' . $sale->numero, $note->numero,
                        );
                    }
                }
            }

            // Reembolso del efectivo cobrado a la caja abierta (si aplica)
            $cobrado = (float) $sale->monto_cobrado;
            if ($cobrado > 0 && $sale->metodo_pago === 'efectivo') {
                if (! $this->cash->registrarReembolsoVenta($sale, $cobrado)) {
                    $note->advertenciaCaja = 'No se pudo registrar el reembolso de '
                        . number_format($cobrado, 2) . ' en caja: no hay una sesión de caja abierta.';
                }
            }

            $sale->update(['estado' => 'anulada']);

            return $note;
        });
    }

    /**
     * Crea una nota de crédito PARCIAL (devolución por ítem). Devuelve solo
     * las cantidades indicadas; anula la venta solo si se devuelve todo.
     *
     * @param  array<int, array{sale_item_id:int, cantidad:float}>  $items
     */
    public function registrarCreditoParcial(Sale $sale, string $codMotivo, string $desMotivo, array $items): Note
    {
        $this->validarVenta($sale);
        if ($sale->estado === 'anulada') {
            throw new RuntimeException('La venta ya está anulada.');
        }
        if (empty($items)) {
            throw new RuntimeException('Selecciona al menos un ítem a devolver.');
        }

        return DB::transaction(function () use ($sale, $codMotivo, $desMotivo, $items) {
            $sale->loadMissing('items');
            $saleItems = $sale->items->keyBy('id');
            $warehouse = $sale->warehouse_id ? Warehouse::find($sale->warehouse_id) : null;
            $devuelto = $this->devueltoPorLinea($sale);

            $lineas = [];
            $subtotal = 0.0;
            $igvTotal = 0.0;
            $total = 0.0;

            foreach ($items as $row) {
                $sid = (int) ($row['sale_item_id'] ?? 0);
                $q = (float) ($row['cantidad'] ?? 0);
                if ($q <= 0) {
                    continue;
                }
                $si = $saleItems->get($sid);
                if (! $si) {
                    throw new RuntimeException('Un ítem no pertenece a la venta.');
                }
                $restante = (float) $si->cantidad - (float) ($devuelto[$sid] ?? 0);
                if ($q > $restante + 0.0001) {
                    throw new RuntimeException(
                        "La cantidad a devolver de «{$si->descripcion}» supera lo disponible (máx {$restante}).",
                    );
                }

                $factor = $q / (float) $si->cantidad;
                $base = round((float) $si->base * $factor, 2);
                $igv = round((float) $si->igv * $factor, 2);
                $tot = round((float) $si->total * $factor, 2);
                $desc = round((float) $si->descuento * $factor, 2);

                $subtotal += $base;
                $igvTotal += $igv;
                $total += $tot;
                $lineas[] = compact('si', 'q', 'base', 'igv', 'tot', 'desc');
            }

            if (empty($lineas)) {
                throw new RuntimeException('Selecciona al menos un ítem con cantidad mayor a cero.');
            }

            $serie = $sale->tipo_comprobante === 'factura' ? 'FC01' : 'BC01';
            $note = $this->crearCabecera($sale, 'credito', '07', $serie, $codMotivo, $desMotivo, [
                'subtotal' => $subtotal,
                'igv' => $igvTotal,
                'total' => $total,
            ]);

            foreach ($lineas as $l) {
                $si = $l['si'];
                $note->items()->create([
                    'product_id' => $si->product_id,
                    'sale_item_id' => $si->id,
                    'descripcion' => $si->descripcion,
                    'cantidad' => $l['q'],
                    'precio_unitario' => $si->precio_unitario,
                    'descuento' => $l['desc'],
                    'afecto_igv' => $si->afecto_igv,
                    'base' => $l['base'],
                    'igv' => $l['igv'],
                    'total' => $l['tot'],
                ]);

                if ($warehouse && $si->product_id) {
                    $product = Product::find($si->product_id);
                    if ($product && $product->controla_stock) {
                        $this->inventory->entrada(
                            $product, $warehouse, (float) $l['q'], (float) $si->costo_unitario,
                            'Devolución venta ' . $sale->numero, $note->numero,
                        );
                    }
                }
            }

            // Reembolso proporcional en efectivo (acotado a lo cobrado)
            $cobrado = (float) $sale->monto_cobrado;
            if ($cobrado > 0 && $sale->metodo_pago === 'efectivo') {
                $montoReembolso = min(round($total, 2), $cobrado);
                if (! $this->cash->registrarReembolsoVenta($sale, $montoReembolso)) {
                    $note->advertenciaCaja = 'No se pudo registrar el reembolso de '
                        . number_format($montoReembolso, 2) . ' en caja: no hay una sesión de caja abierta.';
                }
            }

            // Si tras esta devolución todo quedó devuelto, anular la venta
            $devueltoFinal = $this->devueltoPorLinea($sale);
            $todoDevuelto = $sale->items->every(
                fn ($si) => (float) ($devueltoFinal[$si->id] ?? 0) + 0.0001 >= (float) $si->cantidad,
            );
            if ($todoDevuelto) {
                $sale->update(['estado' => 'anulada']);
            }

            return $note;
        });
    }

    /** Cantidad ya devuelta por cada línea de venta (suma de notas de crédito previas). */
    private function devueltoPorLinea(Sale $sale): \Illuminate\Support\Collection
    {
        return NoteItem::whereHas('note', fn ($q) => $q->where('sale_id', $sale->id)->where('tipo', 'credito'))
            ->selectRaw('sale_item_id, SUM(cantidad) as q')
            ->groupBy('sale_item_id')
            ->pluck('q', 'sale_item_id');
    }

    /**
     * Crea una nota de débito (cargo adicional) sobre una venta.
     */
    public function registrarDebito(
        Sale $sale,
        string $codMotivo,
        string $desMotivo,
        string $concepto,
        float $monto,
        bool $afectoIgv = true,
    ): Note {
        $this->validarVenta($sale);
        if ($monto <= 0) {
            throw new RuntimeException('El monto debe ser mayor a cero.');
        }

        $igvPct = (float) ($sale->company->igv_porcentaje ?? 18);
        $total = round($monto, 2);
        $igv = $afectoIgv ? round($total - $total / (1 + $igvPct / 100), 2) : 0.0;
        $base = round($total - $igv, 2);

        return DB::transaction(function () use ($sale, $codMotivo, $desMotivo, $concepto, $total, $igv, $base, $afectoIgv) {
            $serie = $sale->tipo_comprobante === 'factura' ? 'FD01' : 'BD01';
            $note = $this->crearCabecera($sale, 'debito', '08', $serie, $codMotivo, $desMotivo, [
                'subtotal' => $base,
                'igv' => $igv,
                'total' => $total,
            ]);

            $note->items()->create([
                'product_id' => null,
                'descripcion' => $concepto,
                'cantidad' => 1,
                'precio_unitario' => $total,
                'descuento' => 0,
                'afecto_igv' => $afectoIgv,
                'base' => $base,
                'igv' => $igv,
                'total' => $total,
            ]);

            return $note;
        });
    }

    public function emitir(Note $note): Note
    {
        $company = $note->company;
        $greenterNote = $this->builder->build($note, $company);

        $sender = $company->sunatConfigurado()
            ? new GreenterSender($company)
            : new SimulatedSender($company);

        $res = $sender->emitir($greenterNote);

        $name = $greenterNote->getName();
        $ruc = $company->ruc;
        $xmlPath = "sunat/{$ruc}/xml/{$name}.xml";
        Storage::put($xmlPath, $res['xml']);

        $cdrPath = null;
        if (! empty($res['cdrZip'])) {
            $cdrPath = "sunat/{$ruc}/cdr/R-{$name}.zip";
            Storage::put($cdrPath, $res['cdrZip']);
        }

        $note->update([
            'sunat_estado' => $res['estado'],
            'sunat_xml_path' => $xmlPath,
            'sunat_cdr_path' => $cdrPath,
            'sunat_hash' => $res['hash'],
            'sunat_mensaje' => $res['mensaje'],
            'sunat_enviado_at' => now(),
        ]);

        return $note->refresh();
    }

    private function validarVenta(Sale $sale): void
    {
        if (! in_array($sale->tipo_comprobante, ['boleta', 'factura'], true)) {
            throw new RuntimeException('Solo se emiten notas sobre boletas o facturas.');
        }
    }

    private function crearCabecera(Sale $sale, string $tipo, string $tipoDoc, string $serie, string $codMotivo, string $desMotivo, array $totales): Note
    {
        $user = Auth::user();

        $correlativo = (int) Note::where('tipo_doc', $tipoDoc)
            ->where('serie', $serie)
            ->lockForUpdate()
            ->max('correlativo');
        $correlativo++;

        return Note::create([
            'company_id' => $sale->company_id,
            'branch_id' => $sale->branch_id,
            'user_id' => $user?->id,
            'sale_id' => $sale->id,
            'tipo' => $tipo,
            'tipo_doc' => $tipoDoc,
            'serie' => $serie,
            'correlativo' => $correlativo,
            'fecha' => now(),
            'moneda' => $sale->moneda ?? 'PEN',
            'cod_motivo' => $codMotivo,
            'des_motivo' => $desMotivo,
            'subtotal' => round($totales['subtotal'], 2),
            'igv' => round($totales['igv'], 2),
            'total' => round($totales['total'], 2),
        ]);
    }
}
