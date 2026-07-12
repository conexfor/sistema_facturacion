<?php

namespace App\Http\Controllers\Sunat;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\ElectronicInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ComprobanteController extends Controller
{
    public function __construct(private readonly ElectronicInvoiceService $service)
    {
    }

    public function index(Request $request): Response
    {
        $comprobantes = Sale::query()
            ->whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->with('user:id,name')
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('cliente_nombre', 'like', "%{$s}%")
                    ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$s}%"]);
            }))
            ->when($request->input('sunat_estado'), fn ($q, $e) => $q->where('sunat_estado', $e))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $config = $request->user()->company?->sunatConfigurado() ?? false;

        return Inertia::render('Sunat/Comprobantes/Index', [
            'comprobantes' => $comprobantes,
            'filters' => $request->only(['search', 'sunat_estado']),
            'sunatConfigurado' => $config,
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function emitir(Sale $sale): RedirectResponse
    {
        try {
            $sale = $this->service->emitir($sale);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = match ($sale->sunat_estado) {
            'aceptado' => "Comprobante {$sale->numero} aceptado por SUNAT.",
            'simulado' => "Comprobante {$sale->numero} generado en modo simulación.",
            'observado' => "Comprobante {$sale->numero} aceptado con observaciones.",
            default => "Comprobante {$sale->numero}: {$sale->sunat_mensaje}",
        };

        $tipo = in_array($sale->sunat_estado, ['rechazado'], true) ? 'error' : 'success';

        return back()->with($tipo, $msg);
    }

    public function xml(Sale $sale): StreamedResponse
    {
        abort_unless($sale->sunat_xml_path && Storage::exists($sale->sunat_xml_path), 404);

        return Storage::download($sale->sunat_xml_path, $sale->numero . '.xml');
    }

    public function cdr(Sale $sale): StreamedResponse
    {
        abort_unless($sale->sunat_cdr_path && Storage::exists($sale->sunat_cdr_path), 404);

        return Storage::download($sale->sunat_cdr_path, 'R-' . $sale->numero . '.zip');
    }
}
