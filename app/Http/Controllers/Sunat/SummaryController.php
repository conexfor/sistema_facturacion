<?php

namespace App\Http\Controllers\Sunat;

use App\Http\Controllers\Controller;
use App\Models\BoletaSummary;
use App\Services\SummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SummaryController extends Controller
{
    public function __construct(private readonly SummaryService $summaries)
    {
    }

    public function index(Request $request): Response
    {
        $fecha = $request->input('fecha', Carbon::today()->toDateString());

        $resumenes = BoletaSummary::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Sunat/Resumenes/Index', [
            'resumenes' => $resumenes,
            'fecha' => $fecha,
            'pendientes' => $this->summaries->boletasPendientes(Carbon::parse($fecha))->count(),
            'sunatConfigurado' => $request->user()->company?->sunatConfigurado() ?? false,
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function generar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $summary = $this->summaries->generar(Carbon::parse($data['fecha']));
            $summary = $this->summaries->enviar($summary);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = match ($summary->estado) {
            'enviado' => "Resumen {$summary->identificador} enviado. Consulta el estado para obtener el CDR.",
            'simulado' => "Resumen {$summary->identificador} generado en modo simulación.",
            'rechazado' => "Resumen rechazado: {$summary->mensaje}",
            'pendiente' => "Resumen {$summary->identificador} no se pudo enviar: {$summary->mensaje}",
            default => "Resumen {$summary->identificador} procesado.",
        };

        $tipo = match ($summary->estado) {
            'rechazado' => 'error',
            'pendiente' => 'warning',
            default => 'success',
        };

        return back()->with($tipo, $msg);
    }

    /** Reintenta el envío de un resumen que SUNAT no llegó a recibir. */
    public function reenviar(BoletaSummary $boletaSummary): RedirectResponse
    {
        try {
            $summary = $this->summaries->enviar($boletaSummary);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return match ($summary->estado) {
            'enviado' => back()->with('success', "Resumen {$summary->identificador} enviado. Consulta el estado para obtener el CDR."),
            'rechazado' => back()->with('error', "Resumen rechazado: {$summary->mensaje}"),
            default => back()->with('warning', (string) $summary->mensaje),
        };
    }

    public function consultar(BoletaSummary $boletaSummary): RedirectResponse
    {
        try {
            $boletaSummary = $this->summaries->consultar($boletaSummary);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = match ($boletaSummary->estado) {
            'aceptado' => "Resumen {$boletaSummary->identificador} aceptado por SUNAT.",
            'observado' => "Resumen aceptado con observaciones: {$boletaSummary->mensaje}",
            'rechazado' => "Resumen rechazado: {$boletaSummary->mensaje}. Sus boletas quedaron libres para un nuevo resumen.",
            default => $boletaSummary->mensaje ?? 'Consulta realizada.',
        };

        // "enviado" tras consultar = SUNAT aún no da respuesta.
        $tipo = match ($boletaSummary->estado) {
            'aceptado' => 'success',
            'rechazado' => 'error',
            default => 'warning',
        };

        return back()->with($tipo, $msg);
    }

    public function xml(BoletaSummary $boletaSummary): StreamedResponse
    {
        abort_unless($boletaSummary->xml_path && Storage::exists($boletaSummary->xml_path), 404);

        return Storage::download($boletaSummary->xml_path, $boletaSummary->identificador . '.xml');
    }

    public function cdr(BoletaSummary $boletaSummary): StreamedResponse
    {
        abort_unless($boletaSummary->cdr_path && Storage::exists($boletaSummary->cdr_path), 404);

        return Storage::download($boletaSummary->cdr_path, 'R-' . $boletaSummary->identificador . '.zip');
    }
}
