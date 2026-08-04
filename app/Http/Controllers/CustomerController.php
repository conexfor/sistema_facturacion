<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\DocumentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('razon_social', 'like', "%{$s}%")
                    ->orWhere('numero_documento', 'like', "%{$s}%");
            }))
            ->when($request->input('tipo_documento'), fn ($q, $t) => $q->where('tipo_documento', $t))
            ->orderBy('razon_social')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'tipos' => Customer::TIPOS,
            'filters' => $request->only(['search', 'tipo_documento']),
            'lookupEnabled' => app(DocumentLookupService::class)->isEnabled(),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return back()->with('success', 'Cliente registrado correctamente.');
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Desactiva al cliente en vez de borrarlo: preserva el historial de
     * ventas y comprobantes (incluyendo reintentos de facturas rechazadas),
     * y evita que se le pueda seguir vendiendo.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->update(['is_active' => false]);

        return back()->with('success', 'Cliente desactivado. Ya no aparecerá para nuevas ventas.');
    }

    /** Consulta automática RUC/DNI (SUNAT/RENIEC). */
    public function consultar(Request $request, DocumentLookupService $lookup): JsonResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['1', '6'])],
            'numero' => ['required', 'string'],
        ]);

        try {
            return response()->json(['data' => $lookup->lookup($data['tipo'], $data['numero'])]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Búsqueda rápida de clientes para el POS (JSON). */
    public function buscar(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        $customers = Customer::query()
            ->where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('razon_social', 'like', "%{$q}%")
                    ->orWhere('numero_documento', 'like', "%{$q}%");
            }))
            ->orderBy('razon_social')
            ->limit(10)
            ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social']);

        return response()->json($customers);
    }

    /**
     * Alta rápida de cliente desde el POS. Devuelve el cliente en JSON para
     * seleccionarlo de inmediato. Si el documento ya existe, lo reutiliza.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $formatoDoc = match ($request->input('tipo_documento')) {
            '1' => ['required', 'digits:8'],
            '6' => ['required', 'digits:11'],
            default => ['nullable', 'string', 'max:20'],
        };

        $data = $request->validate([
            'tipo_documento' => ['required', Rule::in(array_keys(Customer::TIPOS))],
            'numero_documento' => $formatoDoc,
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $customer = null;
        if (! empty($data['numero_documento'])) {
            $customer = Customer::where('tipo_documento', $data['tipo_documento'])
                ->where('numero_documento', $data['numero_documento'])
                ->first();
            if ($customer) {
                $customer->fill($data)->save();
            }
        }

        $customer ??= Customer::create($data);

        return response()->json($customer->only([
            'id', 'tipo_documento', 'numero_documento', 'razon_social',
        ]));
    }
}
