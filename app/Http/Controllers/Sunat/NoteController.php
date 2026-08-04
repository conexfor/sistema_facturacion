<?php

namespace App\Http\Controllers\Sunat;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Sale;
use App\Services\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $notes)
    {
    }

    public function index(Request $request): Response
    {
        $notas = Note::query()
            ->with(['sale:id,serie,correlativo,tipo_comprobante', 'user:id,name'])
            ->when($request->input('search'), fn ($q, $s) => $q->whereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$s}%"]))
            ->when($request->input('tipo'), fn ($q, $t) => $q->where('tipo', $t))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sunat/Notes/Index', [
            'notas' => $notas,
            'filters' => $request->only(['search', 'tipo']),
            'motivosCredito' => NoteService::MOTIVOS_CREDITO,
            'motivosDebito' => NoteService::MOTIVOS_DEBITO,
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function buscarVentas(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        $ventas = Sale::query()
            ->whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->where('estado', '!=', 'anulada')
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('cliente_nombre', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$q}%"]);
            }))
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'serie', 'correlativo', 'tipo_comprobante', 'cliente_nombre', 'total']);

        return response()->json($ventas->map(fn (Sale $s) => [
            'id' => $s->id,
            'numero' => $s->numero,
            'tipo' => $s->tipo_comprobante,
            'cliente' => $s->cliente_nombre,
            'total' => (float) $s->total,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'sale_id' => ['required', Rule::exists('sales', 'id')->where('company_id', $companyId)],
            'tipo' => ['required', Rule::in(['credito', 'debito'])],
            'cod_motivo' => ['required', 'string', 'max:2'],
            'des_motivo' => ['required', 'string', 'max:255'],
            'concepto' => ['required_if:tipo,debito', 'nullable', 'string', 'max:255'],
            'monto' => ['required_if:tipo,debito', 'nullable', 'numeric', 'gt:0'],
            'afecto_igv' => ['boolean'],
            'items' => ['nullable', 'array'],
            'items.*.sale_item_id' => ['required_with:items', 'integer'],
            'items.*.cantidad' => ['required_with:items', 'numeric', 'gt:0'],
        ]);

        $sale = Sale::findOrFail($data['sale_id']);
        $items = array_filter($data['items'] ?? [], fn ($i) => (float) ($i['cantidad'] ?? 0) > 0);

        try {
            if ($data['tipo'] === 'credito') {
                $note = ! empty($items)
                    ? $this->notes->registrarCreditoParcial($sale, $data['cod_motivo'], $data['des_motivo'], $items)
                    : $this->notes->registrarCredito($sale, $data['cod_motivo'], $data['des_motivo']);
            } else {
                $note = $this->notes->registrarDebito(
                    $sale, $data['cod_motivo'], $data['des_motivo'],
                    $data['concepto'], (float) $data['monto'], (bool) ($data['afecto_igv'] ?? true),
                );
            }

            $note = $this->notes->emitir($note);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $tipoTxt = $note->tipo === 'credito' ? 'crédito' : 'débito';
        $msg = "Nota de {$tipoTxt} {$note->numero}: {$note->sunat_mensaje}";

        if ($note->sunat_estado === 'rechazado') {
            return redirect()->route('notas.index')->with('error', $msg);
        }
        if ($note->advertenciaCaja) {
            return redirect()->route('notas.index')->with('warning', $msg . ' ' . $note->advertenciaCaja);
        }

        return redirect()->route('notas.index')->with('success', $msg);
    }

    public function emitir(Note $note): RedirectResponse
    {
        try {
            $note = $this->notes->emitir($note);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Nota {$note->numero} reenviada.");
    }

    public function xml(Note $note): StreamedResponse
    {
        abort_unless($note->sunat_xml_path && Storage::exists($note->sunat_xml_path), 404);

        return Storage::download($note->sunat_xml_path, $note->numero . '.xml');
    }

    public function cdr(Note $note): StreamedResponse
    {
        abort_unless($note->sunat_cdr_path && Storage::exists($note->sunat_cdr_path), 404);

        return Storage::download($note->sunat_cdr_path, 'R-' . $note->numero . '.zip');
    }

    public function pdf(Request $request, Note $note, \App\Services\DocumentPdfService $pdf): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->note($note, $request->query('formato') === 'ticket' ? 'ticket' : 'a4');
    }
}
