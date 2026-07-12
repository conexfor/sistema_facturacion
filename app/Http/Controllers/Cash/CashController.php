<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Services\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CashController extends Controller
{
    public function __construct(private readonly CashService $cash)
    {
    }

    public function index(): Response
    {
        $sessions = CashSession::query()
            ->with(['cashRegister:id,nombre', 'user:id,name'])
            ->orderByDesc('id')
            ->paginate(10);

        $activa = $this->cash->sesionActiva();
        if ($activa) {
            $activa->loadSum(['movements as ingresos' => fn ($q) => $q->where('tipo', 'ingreso')], 'monto');
            $activa->loadSum(['movements as egresos' => fn ($q) => $q->where('tipo', 'egreso')], 'monto');
            $activa->load('cashRegister:id,nombre');
        }

        return Inertia::render('Cash/Index', [
            'sessions' => $sessions,
            'activa' => $activa,
            'registers' => CashRegister::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
            'allRegisters' => CashRegister::withCount(['sessions as abiertas' => fn ($q) => $q->where('estado', 'abierta')])
                ->orderBy('nombre')->get(['id', 'nombre', 'is_active']),
            'moneda' => request()->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function abrir(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cash_register_id' => ['required', Rule::exists('cash_registers', 'id')->where('company_id', $request->user()->company_id)],
            'monto_apertura' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $session = $this->cash->abrir(
                CashRegister::findOrFail($data['cash_register_id']),
                (float) $data['monto_apertura'],
                $data['observacion'] ?? null,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('caja.show', $session->id)->with('success', 'Caja aperturada.');
    }

    public function show(CashSession $cashSession): Response
    {
        $cashSession->load(['cashRegister:id,nombre', 'user:id,name', 'movements.user:id,name']);
        $cashSession->setRelation(
            'movements',
            $cashSession->movements->sortByDesc('id')->values(),
        );

        return Inertia::render('Cash/Show', [
            'session' => $cashSession,
            'ingresos' => $cashSession->totalIngresos(),
            'egresos' => $cashSession->totalEgresos(),
            'saldoEsperado' => $cashSession->saldoEsperado(),
            'moneda' => request()->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function movimiento(Request $request, CashSession $cashSession): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['ingreso', 'egreso'])],
            'categoria' => ['required', Rule::in(['gasto', 'retiro', 'deposito', 'ajuste', 'otro'])],
            'concepto' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $this->cash->registrarMovimiento(
                $cashSession, $data['tipo'], $data['categoria'], $data['concepto'], (float) $data['monto'],
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movimiento registrado.');
    }

    public function cerrar(Request $request, CashSession $cashSession): RedirectResponse
    {
        $data = $request->validate([
            'monto_contado' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->cash->cerrar($cashSession, (float) $data['monto_contado'], $data['observacion'] ?? null);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Caja cerrada. Arqueo registrado.');
    }

    public function flujo(Request $request): Response
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $movs = CashMovement::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->get(['tipo', 'categoria', 'monto']);

        $ingresos = round((float) $movs->where('tipo', 'ingreso')->sum('monto'), 2);
        $egresos = round((float) $movs->where('tipo', 'egreso')->sum('monto'), 2);

        $porCategoria = $movs->groupBy('categoria')->map(fn ($g, $cat) => [
            'categoria' => $cat,
            'ingresos' => round((float) $g->where('tipo', 'ingreso')->sum('monto'), 2),
            'egresos' => round((float) $g->where('tipo', 'egreso')->sum('monto'), 2),
        ])->values();

        return Inertia::render('Cash/Flujo', [
            'filters' => ['desde' => $desde, 'hasta' => $hasta],
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'neto' => round($ingresos - $egresos, 2),
            'porCategoria' => $porCategoria,
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }
}
