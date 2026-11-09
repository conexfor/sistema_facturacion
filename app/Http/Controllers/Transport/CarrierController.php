<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarrierRequest;
use App\Models\Carrier;
use App\Services\DocumentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CarrierController extends Controller
{
    public function index(Request $request): Response
    {
        $carriers = Carrier::query()
            ->withCount('despatches')
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('razon_social', 'like', "%{$s}%")
                    ->orWhere('ruc', 'like', "%{$s}%");
            }))
            ->orderByDesc('is_active')
            ->orderBy('razon_social')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Transport/Carriers/Index', [
            'carriers' => $carriers,
            'filters' => $request->only(['search']),
            'lookupEnabled' => app(DocumentLookupService::class)->isEnabled(),
        ]);
    }

    public function store(CarrierRequest $request): RedirectResponse
    {
        Carrier::create($request->validated());

        return back()->with('success', 'Transportista registrado correctamente.');
    }

    public function update(CarrierRequest $request, Carrier $carrier): RedirectResponse
    {
        $carrier->update($request->validated());

        return back()->with('success', 'Transportista actualizado correctamente.');
    }

    public function destroy(Carrier $carrier): RedirectResponse
    {
        if ($carrier->despatches()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar: el transportista tiene guías registradas. Desactívalo en su lugar.',
            );
        }

        $carrier->delete();

        return back()->with('success', 'Transportista eliminado.');
    }

    public function consultar(Request $request, DocumentLookupService $lookup): JsonResponse
    {
        $data = $request->validate(['numero' => ['required', 'digits:11']]);

        try {
            return response()->json(['data' => $lookup->lookup('6', $data['numero'])]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
