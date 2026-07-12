<?php

namespace App\Http\Controllers\Sunat;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\VoidedDocument;
use App\Services\VoidedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class VoidedController extends Controller
{
    public function __construct(private readonly VoidedService $voided)
    {
    }

    public function index(Request $request): Response
    {
        $bajas = VoidedDocument::query()
            ->with(['sale:id,serie,correlativo,tipo_comprobante,cliente_nombre', 'user:id,name'])
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Sunat/Bajas/Index', [
            'bajas' => $bajas,
            'sunatConfigurado' => $request->user()->company?->sunatConfigurado() ?? false,
        ]);
    }

    /** Facturas que aún pueden darse de baja (emitidas, no anuladas, ≤ 7 días). */
    public function buscarFacturas(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        $facturas = Sale::query()
            ->where('tipo_comprobante', 'factura')
            ->whereIn('sunat_estado', ['aceptado', 'simulado', 'observado'])
            ->where('estado', '!=', 'anulada')
            ->whereDate('fecha', '>=', Carbon::today()->subDays(7)->toDateString())
            ->whereNotIn('id', VoidedDocument::query()
                ->whereIn('estado', ['pendiente', 'enviado', 'aceptado', 'simulado'])
                ->whereNotNull('sale_id')
                ->pluck('sale_id'))
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('cliente_nombre', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$q}%"]);
            }))
            ->orderByDesc('fecha')
            ->limit(10)
            ->get(['id', 'serie', 'correlativo', 'cliente_nombre', 'total', 'fecha']);

        return response()->json($facturas->map(fn ($s) => [
            'id' => $s->id,
            'numero' => $s->numero,
            'cliente_nombre' => $s->cliente_nombre,
            'total' => $s->total,
            'fecha' => $s->fecha->toDateString(),
        ]));
    }

    public function anular(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'sale_id' => ['required', Rule::exists('sales', 'id')->where('company_id', $companyId)],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->voided->anular(Sale::findOrFail($data['sale_id']), $data['motivo']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Comunicación de baja registrada.');
    }

    public function consultar(VoidedDocument $voidedDocument): RedirectResponse
    {
        try {
            $voidedDocument = $this->voided->consultar($voidedDocument);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = match ($voidedDocument->estado) {
            'aceptado' => "Baja {$voidedDocument->identificador} aceptada. Comprobante anulado.",
            'observado' => "Baja observada: {$voidedDocument->mensaje}",
            default => $voidedDocument->mensaje ?? 'Consulta realizada.',
        };

        return back()->with($voidedDocument->estado === 'observado' ? 'error' : 'success', $msg);
    }

    public function xml(VoidedDocument $voidedDocument): StreamedResponse
    {
        abort_unless($voidedDocument->xml_path && Storage::exists($voidedDocument->xml_path), 404);

        return Storage::download($voidedDocument->xml_path, $voidedDocument->identificador . '.xml');
    }

    public function cdr(VoidedDocument $voidedDocument): StreamedResponse
    {
        abort_unless($voidedDocument->cdr_path && Storage::exists($voidedDocument->cdr_path), 404);

        return Storage::download($voidedDocument->cdr_path, 'R-' . $voidedDocument->identificador . '.zip');
    }
}
