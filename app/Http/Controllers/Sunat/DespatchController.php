<?php

namespace App\Http\Controllers\Sunat;

use App\Http\Controllers\Controller;
use App\Models\Despatch;
use App\Models\Sale;
use App\Services\DespatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DespatchController extends Controller
{
    public function __construct(private readonly DespatchService $despatches)
    {
    }

    public function index(Request $request): Response
    {
        $guias = Despatch::query()
            ->with('user:id,name')
            ->when($request->input('search'), fn ($q, $s) => $q->whereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$s}%"])
                ->orWhere('dest_nombre', 'like', "%{$s}%"))
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sunat/Despatches/Index', [
            'guias' => $guias,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $company = $request->user()->company;

        return Inertia::render('Sunat/Despatches/Create', [
            'motivos' => DespatchService::MOTIVOS,
            'modalidades' => DespatchService::MODALIDADES,
            'empresa' => [
                'ubigeo' => $company->ubigeo,
                'direccion' => $company->direccion,
            ],
        ]);
    }

    public function buscarVentas(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        $ventas = Sale::query()
            ->whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->where('estado', '!=', 'anulada')
            ->with(['customer:id,tipo_documento,numero_documento,direccion', 'items:id,sale_id,product_id,descripcion,cantidad'])
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('cliente_nombre', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$q}%"]);
            }))
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return response()->json($ventas->map(fn (Sale $s) => [
            'id' => $s->id,
            'numero' => $s->numero,
            'dest_tipo_doc' => $s->customer?->tipo_documento ?? $s->customer?->tipo_documento ?? '6',
            'dest_num_doc' => $s->cliente_documento,
            'dest_nombre' => $s->cliente_nombre,
            'llegada_direccion' => $s->customer?->direccion,
            'items' => $s->items->map(fn ($it) => [
                'product_id' => $it->product_id,
                'descripcion' => $it->descripcion,
                'cantidad' => (float) $it->cantidad,
                'unidad' => 'NIU',
            ]),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'sale_id' => ['nullable', Rule::exists('sales', 'id')->where('company_id', $companyId)],
            'fecha_emision' => ['required', 'date'],
            'fecha_traslado' => ['required', 'date'],
            'cod_traslado' => ['required', Rule::in(array_keys(DespatchService::MOTIVOS))],
            'des_traslado' => ['required', 'string', 'max:255'],
            'mod_traslado' => ['required', Rule::in(['01', '02'])],
            'peso_total' => ['required', 'numeric', 'min:0'],
            'num_bultos' => ['required', 'integer', 'min:1'],
            'dest_tipo_doc' => ['required', 'string', 'max:2'],
            'dest_num_doc' => ['nullable', 'string', 'max:20'],
            'dest_nombre' => ['required', 'string', 'max:255'],
            'partida_ubigeo' => ['nullable', 'string', 'size:6'],
            'partida_direccion' => ['required', 'string', 'max:255'],
            'llegada_ubigeo' => ['nullable', 'string', 'size:6'],
            'llegada_direccion' => ['required', 'string', 'max:255'],
            'transportista_doc' => ['nullable', 'required_if:mod_traslado,01', 'string', 'max:20'],
            'transportista_nombre' => ['nullable', 'required_if:mod_traslado,01', 'string', 'max:255'],
            'transportista_mtc' => ['nullable', 'string', 'max:30'],
            'vehiculo_placa' => ['nullable', 'required_if:mod_traslado,02', 'string', 'max:20'],
            'chofer_num_doc' => ['nullable', 'required_if:mod_traslado,02', 'string', 'max:20'],
            'chofer_licencia' => ['nullable', 'required_if:mod_traslado,02', 'string', 'max:20'],
            'chofer_nombres' => ['nullable', 'required_if:mod_traslado,02', 'string', 'max:255'],
            'chofer_apellidos' => ['nullable', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.descripcion' => ['required', 'string', 'max:255'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ]);

        $data['chofer_tipo_doc'] = '1';

        try {
            $despatch = $this->despatches->registrar($data);
            $despatch = $this->despatches->emitir($despatch);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $estado = $despatch->sunat_estado === 'rechazado' ? 'error' : 'success';

        return redirect()->route('guias.index')
            ->with($estado, "Guía {$despatch->numero}: {$despatch->sunat_mensaje}");
    }

    public function emitir(Despatch $despatch): RedirectResponse
    {
        try {
            $despatch = $this->despatches->emitir($despatch);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($despatch->sunat_estado === 'rechazado') {
            return back()->with('error', "Guía {$despatch->numero} rechazada: {$despatch->sunat_mensaje}");
        }

        return back()->with('success', "Guía {$despatch->numero} enviada. {$despatch->sunat_mensaje}");
    }

    public function consultar(Despatch $despatch): RedirectResponse
    {
        try {
            $despatch = $this->despatches->consultar($despatch);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Guía {$despatch->numero}: {$despatch->sunat_mensaje}");
    }

    public function pdf(Request $request, Despatch $despatch, \App\Services\DocumentPdfService $pdf): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->despatch($despatch, $request->query('formato') === 'ticket' ? 'ticket' : 'a4');
    }

    public function xml(Despatch $despatch): StreamedResponse
    {
        abort_unless($despatch->sunat_xml_path && Storage::exists($despatch->sunat_xml_path), 404);

        return Storage::download($despatch->sunat_xml_path, $despatch->numero . '.xml');
    }

    public function cdr(Despatch $despatch): StreamedResponse
    {
        abort_unless($despatch->sunat_cdr_path && Storage::exists($despatch->sunat_cdr_path), 404);

        return Storage::download($despatch->sunat_cdr_path, 'R-' . $despatch->numero . '.zip');
    }
}
