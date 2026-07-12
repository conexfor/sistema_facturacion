<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Warehouse;
use App\Services\LotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LotController extends Controller
{
    public function __construct(private readonly LotService $lots)
    {
    }

    public function index(Request $request): Response
    {
        $hoy = Carbon::today();
        $limite = (clone $hoy)->addDays(30);

        $lots = ProductLot::query()
            ->with(['product:id,codigo,nombre', 'warehouse:id,nombre'])
            ->where('cantidad', '>', 0)
            ->when($request->input('search'), fn ($q, $s) => $q->where('lote', 'like', "%{$s}%")
                ->orWhereHas('product', fn ($q) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%")))
            ->when($request->input('estado'), function ($q, $estado) use ($hoy, $limite) {
                match ($estado) {
                    'vencido' => $q->whereDate('fecha_vencimiento', '<', $hoy),
                    'por_vencer' => $q->whereDate('fecha_vencimiento', '>=', $hoy)->whereDate('fecha_vencimiento', '<=', $limite),
                    'vigente' => $q->whereDate('fecha_vencimiento', '>', $limite),
                    default => null,
                };
            })
            ->orderByRaw('fecha_vencimiento is null, fecha_vencimiento asc')
            ->paginate(15)
            ->withQueryString();

        $lots->getCollection()->transform(function (ProductLot $l) {
            $l->estado_calc = $l->estado;

            return $l;
        });

        return Inertia::render('Inventory/Lots/Index', [
            'lots' => $lots,
            'filters' => $request->only(['search', 'estado']),
            'alertas' => [
                'por_vencer' => ProductLot::where('cantidad', '>', 0)
                    ->whereDate('fecha_vencimiento', '>=', $hoy)->whereDate('fecha_vencimiento', '<=', $limite)->count(),
                'vencidos' => ProductLot::where('cantidad', '>', 0)
                    ->whereDate('fecha_vencimiento', '<', $hoy)->count(),
            ],
            'warehouses' => Warehouse::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        return response()->json(
            Product::where('is_active', true)->where('controla_stock', true)
                ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                    $query->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%");
                }))
                ->orderBy('nombre')->limit(15)
                ->get(['id', 'codigo', 'nombre', 'precio_compra']),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'lote' => ['required', 'string', 'max:50'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->lots->registrar(
                $data['product_id'], $data['warehouse_id'], $data['lote'],
                $data['fecha_vencimiento'] ?? null, (float) $data['cantidad'],
                isset($data['costo']) ? (float) $data['costo'] : null,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Lote ingresado y stock actualizado.');
    }

    public function merma(Request $request, ProductLot $lot): RedirectResponse
    {
        $data = $request->validate([
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->lots->merma($lot, (float) $data['cantidad'], $data['motivo'] ?? null);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Merma registrada.');
    }
}
