<?php

namespace App\Http\Controllers\Sunat;

use App\Http\Controllers\Controller;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LedgerController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function index(Request $request): Response
    {
        $company = $request->user()->company;
        $periodo = $this->periodo($request);

        $ventas = $this->ledger->registroVentas($company, $periodo);
        $compras = $this->ledger->registroCompras($company, $periodo);

        return Inertia::render('Sunat/Ledgers/Index', [
            'periodo' => $periodo,
            'ventas' => ['count' => $ventas['count'], 'total' => $ventas['total'], 'filename' => $ventas['filename']],
            'compras' => ['count' => $compras['count'], 'total' => $compras['total'], 'filename' => $compras['filename']],
            'moneda' => $company->moneda ?? 'PEN',
        ]);
    }

    public function ventas(Request $request): HttpResponse
    {
        $libro = $this->ledger->registroVentas($request->user()->company, $this->periodo($request));

        return $this->descarga($libro);
    }

    public function compras(Request $request): HttpResponse
    {
        $libro = $this->ledger->registroCompras($request->user()->company, $this->periodo($request));

        return $this->descarga($libro);
    }

    private function descarga(array $libro): HttpResponse
    {
        return response($libro['content'], 200, [
            'Content-Type' => 'text/plain; charset=ISO-8859-1',
            'Content-Disposition' => 'attachment; filename="' . $libro['filename'] . '"',
        ]);
    }

    private function periodo(Request $request): string
    {
        $p = (string) $request->input('periodo', Carbon::now()->format('Ym'));

        return preg_match('/^\d{6}$/', $p) ? $p : Carbon::now()->format('Ym');
    }
}
