<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ReceivableController extends Controller
{
    public function __construct(private readonly CashService $cash)
    {
    }

    public function index(Request $request): Response
    {
        $sales = Sale::query()
            ->where('estado', 'pendiente')
            ->with('customer:id,razon_social')
            ->when($request->input('search'), fn ($q, $s) => $q->where('cliente_nombre', 'like', "%{$s}%"))
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        $totalPorCobrar = Sale::where('estado', 'pendiente')
            ->selectRaw('COALESCE(SUM(total - monto_cobrado), 0) as deuda')
            ->value('deuda');

        return Inertia::render('Finance/Receivables', [
            'sales' => $sales,
            'filters' => $request->only(['search']),
            'totalPorCobrar' => round((float) $totalPorCobrar, 2),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function cobrar(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'yape', 'plin', 'deposito'])],
            'fecha' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->cash->registrarCobro(
                $sale,
                (float) $data['monto'],
                $data['metodo_pago'],
                isset($data['fecha']) ? \Illuminate\Support\Carbon::parse($data['fecha']) : null,
                $data['observacion'] ?? null,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cobro registrado correctamente.');
    }
}
