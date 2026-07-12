<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $sales = Sale::query()
            ->with(['customer:id,razon_social', 'user:id,name'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('cliente_nombre', 'like', "%{$s}%")
                    ->orWhere('serie', 'like', "%{$s}%")
                    ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$s}%"]);
            }))
            ->when($request->input('tipo_comprobante'), fn ($q, $t) => $q->where('tipo_comprobante', $t))
            ->when($request->input('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->when($request->input('desde'), fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($request->input('hasta'), fn ($q, $h) => $q->whereDate('fecha', '<=', $h))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Totales del periodo filtrado (solo ventas no anuladas)
        $resumen = (clone $sales->getCollection())->where('estado', '!=', 'anulada');

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'tipo_comprobante', 'estado', 'desde', 'hasta']),
            'totalPagina' => round($resumen->sum('total'), 2),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function show(Sale $sale): Response
    {
        $sale->load(['items.product:id,codigo', 'customer', 'user:id,name', 'warehouse:id,nombre']);

        $devueltos = \App\Models\NoteItem::whereHas(
            'note', fn ($q) => $q->where('sale_id', $sale->id)->where('tipo', 'credito'),
        )->selectRaw('sale_item_id, SUM(cantidad) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id');

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
            'numero' => $sale->numero,
            'puedeAnular' => $sale->estado !== 'anulada'
                && in_array($sale->tipo_comprobante, ['boleta', 'factura'], true),
            'motivosCredito' => \App\Services\NoteService::MOTIVOS_CREDITO,
            'devueltos' => $devueltos,
        ]);
    }

    public function pdf(Request $request, Sale $sale, DocumentPdfService $pdf): HttpResponse
    {
        return $pdf->sale($sale, $request->query('formato') === 'ticket' ? 'ticket' : 'a4');
    }
}
