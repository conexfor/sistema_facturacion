<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\DocumentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $suppliers = Supplier::query()
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('razon_social', 'like', "%{$s}%")
                    ->orWhere('numero_documento', 'like', "%{$s}%");
            }))
            ->orderBy('razon_social')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Purchases/Suppliers/Index', [
            'suppliers' => $suppliers,
            'tipos' => Supplier::TIPOS,
            'filters' => $request->only(['search']),
            'lookupEnabled' => app(DocumentLookupService::class)->isEnabled(),
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return back()->with('success', 'Proveedor registrado correctamente.');
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'Proveedor eliminado.');
    }

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

    public function buscar(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        return response()->json(
            Supplier::where('is_active', true)
                ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                    $query->where('razon_social', 'like', "%{$q}%")
                        ->orWhere('numero_documento', 'like', "%{$q}%");
                }))
                ->orderBy('razon_social')
                ->limit(10)
                ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social']),
        );
    }
}
