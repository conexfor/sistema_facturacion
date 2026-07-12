<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CashService
{
    /** Sesión de caja abierta del usuario autenticado, si existe. */
    public function sesionActiva(): ?CashSession
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return CashSession::where('user_id', $user->id)
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }

    public function abrir(CashRegister $register, float $montoApertura, ?string $observacion = null): CashSession
    {
        if ($register->sessions()->where('estado', 'abierta')->exists()) {
            throw new RuntimeException('Esta caja ya tiene una sesión abierta.');
        }

        return CashSession::create([
            'company_id' => $register->company_id,
            'cash_register_id' => $register->id,
            'user_id' => Auth::id(),
            'fecha_apertura' => now(),
            'monto_apertura' => round($montoApertura, 2),
            'estado' => 'abierta',
            'observacion' => $observacion,
        ]);
    }

    public function registrarMovimiento(
        CashSession $session,
        string $tipo,
        string $categoria,
        string $concepto,
        float $monto,
        array $extra = [],
    ): CashMovement {
        if ($session->estado !== 'abierta') {
            throw new RuntimeException('La caja está cerrada; no se pueden registrar movimientos.');
        }
        if ($monto <= 0) {
            throw new RuntimeException('El monto debe ser mayor a cero.');
        }

        return $session->movements()->create([
            'company_id' => $session->company_id,
            'user_id' => Auth::id(),
            'fecha' => $extra['fecha'] ?? now(),
            'tipo' => $tipo,
            'categoria' => $categoria,
            'concepto' => $concepto,
            'monto' => round($monto, 2),
            'metodo_pago' => $extra['metodo_pago'] ?? 'efectivo',
            'sale_id' => $extra['sale_id'] ?? null,
            'purchase_id' => $extra['purchase_id'] ?? null,
            'referencia' => $extra['referencia'] ?? null,
        ]);
    }

    public function cerrar(CashSession $session, float $montoContado, ?string $observacion = null): CashSession
    {
        if ($session->estado !== 'abierta') {
            throw new RuntimeException('La caja ya está cerrada.');
        }

        $esperado = $session->saldoEsperado();
        $session->update([
            'estado' => 'cerrada',
            'fecha_cierre' => now(),
            'closed_by' => Auth::id(),
            'monto_esperado' => $esperado,
            'monto_contado' => round($montoContado, 2),
            'diferencia' => round($montoContado - $esperado, 2),
            'observacion' => $observacion ?? $session->observacion,
        ]);

        return $session;
    }

    /**
     * Registra el cobro de una venta a crédito y, si es en efectivo y hay
     * una caja abierta, lo refleja como ingreso de caja.
     */
    public function registrarCobro(
        Sale $sale,
        float $monto,
        string $metodo,
        ?Carbon $fecha = null,
        ?string $observacion = null,
    ): void {
        DB::transaction(function () use ($sale, $monto, $metodo, $fecha, $observacion) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            $saldo = $sale->saldo;

            if ($monto <= 0) {
                throw new RuntimeException('El monto del cobro debe ser mayor a cero.');
            }
            if ($monto > $saldo + 0.005) {
                throw new RuntimeException('El cobro no puede exceder el saldo (' . number_format($saldo, 2) . ').');
            }

            $sale->payments()->create([
                'user_id' => Auth::id(),
                'fecha' => $fecha ?? now(),
                'monto' => round($monto, 2),
                'metodo_pago' => $metodo,
                'observacion' => $observacion,
            ]);

            $cobrado = round((float) $sale->monto_cobrado + $monto, 2);
            $sale->update([
                'monto_cobrado' => $cobrado,
                'estado' => $cobrado + 0.005 >= (float) $sale->total ? 'pagada' : 'pendiente',
            ]);

            if ($metodo === 'efectivo') {
                $this->ingresoEfectivoSiCajaAbierta(
                    'cobro',
                    'Cobro venta ' . $sale->numero,
                    $monto,
                    ['sale_id' => $sale->id, 'referencia' => $sale->numero],
                );
            }
        });
    }

    /** Ingreso automático por venta en efectivo. */
    public function registrarIngresoVenta(Sale $sale): void
    {
        if ($sale->metodo_pago !== 'efectivo') {
            return;
        }
        $this->ingresoEfectivoSiCajaAbierta(
            'venta',
            'Venta ' . $sale->numero,
            (float) $sale->total,
            ['sale_id' => $sale->id, 'referencia' => $sale->numero],
        );
    }

    /** Egreso automático por pago de compra en efectivo. */
    public function registrarEgresoPagoCompra(Purchase $purchase, float $monto, string $metodo): void
    {
        if ($metodo !== 'efectivo') {
            return;
        }
        $session = $this->sesionActiva();
        if (! $session) {
            return;
        }
        $this->registrarMovimiento(
            $session,
            'egreso',
            'pago_compra',
            'Pago compra ' . ($purchase->numero_completo ?: "#{$purchase->id}"),
            $monto,
            ['purchase_id' => $purchase->id, 'referencia' => $purchase->numero_completo],
        );
    }

    /** Egreso automático por reembolso de una venta anulada (efectivo). */
    public function registrarReembolsoVenta(Sale $sale, float $monto): void
    {
        if ($monto <= 0) {
            return;
        }
        $session = $this->sesionActiva();
        if (! $session) {
            return;
        }
        $this->registrarMovimiento(
            $session,
            'egreso',
            'reembolso',
            'Reembolso venta ' . $sale->numero,
            $monto,
            ['sale_id' => $sale->id, 'referencia' => $sale->numero],
        );
    }

    private function ingresoEfectivoSiCajaAbierta(string $categoria, string $concepto, float $monto, array $extra): void
    {
        $session = $this->sesionActiva();
        if (! $session) {
            return;
        }
        $this->registrarMovimiento($session, 'ingreso', $categoria, $concepto, $monto, $extra);
    }
}
