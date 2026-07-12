<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Warehouse;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes)
    {
    }

    private function tipo(Request $request): string
    {
        return $request->route('tipo') ?? $request->input('tipo', 'cotizacion');
    }

    public function index(Request $request): Response
    {
        $tipo = $this->tipo($request);

        $quotes = Quote::query()
            ->where('tipo', $tipo)
            ->with('user:id,name')
            ->when($request->input('search'), fn ($q, $s) => $q->where('cliente_nombre', 'like', "%{$s}%")
                ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$s}%"]))
            ->when($request->input('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Documents/Index', [
            'tipo' => $tipo,
            'quotes' => $quotes,
            'filters' => $request->only(['search', 'estado']),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Sales/Documents/Create', [
            'tipo' => $this->tipo($request),
            'igv' => (float) ($request->user()->company->igv_porcentaje ?? 18),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        return response()->json(
            Product::where('is_active', true)
                ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                    $query->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%");
                }))
                ->with('unit:id,abreviatura')
                ->orderBy('nombre')->limit(15)
                ->get(['id', 'codigo', 'nombre', 'precio_venta', 'afecto_igv', 'unit_id']),
        );
    }

    public function buscarClientes(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        return response()->json(
            Customer::where('is_active', true)
                ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                    $query->where('razon_social', 'like', "%{$q}%")->orWhere('numero_documento', 'like', "%{$q}%");
                }))
                ->orderBy('razon_social')->limit(10)
                ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social']),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'tipo' => ['required', Rule::in(['cotizacion', 'pedido'])],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'fecha' => ['required', 'date'],
            'fecha_validez' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.descripcion' => ['nullable', 'string', 'max:255'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $quote = $this->quotes->registrar($data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('documentos.show', $quote->id)
            ->with('success', ucfirst($quote->tipo) . " {$quote->numero} registrada.");
    }

    public function show(Quote $quote): Response
    {
        $quote->load(['items.product:id,codigo', 'customer', 'user:id,name', 'sale:id,serie,correlativo']);

        return Inertia::render('Sales/Documents/Show', [
            'quote' => $quote,
            'warehouses' => Warehouse::where('is_active', true)->orderByDesc('is_main')->orderBy('nombre')->get(['id', 'nombre']),
            'moneda' => $quote->moneda,
        ]);
    }

    public function convertir(Request $request, Quote $quote): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'tipo_comprobante' => ['required', Rule::in(['boleta', 'factura', 'nota_venta'])],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'metodo_pago' => ['required', 'string'],
            'monto_recibido' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $sale = $this->quotes->convertirEnVenta($quote, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $sale->id)
            ->with('success', "Venta {$sale->numero} generada desde {$quote->tipo} {$quote->numero}.");
    }

    public function estado(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'aprobada', 'anulada'])],
        ]);

        try {
            $this->quotes->cambiarEstado($quote, $data['estado']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->estado === 'convertida') {
            return back()->with('error', 'No se puede eliminar un documento convertido en venta.');
        }
        $quote->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
