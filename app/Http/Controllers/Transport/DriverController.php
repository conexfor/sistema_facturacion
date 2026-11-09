<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverRequest;
use App\Models\Driver;
use App\Services\DocumentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DriverController extends Controller
{
    public function index(Request $request): Response
    {
        $drivers = Driver::query()
            ->withCount(['despatches', 'trips'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombres', 'like', "%{$s}%")
                    ->orWhere('apellidos', 'like', "%{$s}%")
                    ->orWhere('num_doc', 'like', "%{$s}%")
                    ->orWhere('licencia', 'like', "%{$s}%");
            }))
            ->orderByDesc('is_active')
            ->orderBy('apellidos')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Transport/Drivers/Index', [
            'drivers' => $drivers,
            'tiposDoc' => Driver::TIPOS_DOC,
            'filters' => $request->only(['search']),
            'lookupEnabled' => app(DocumentLookupService::class)->isEnabled(),
        ]);
    }

    public function store(DriverRequest $request): RedirectResponse
    {
        Driver::create($request->validated());

        return back()->with('success', 'Conductor registrado correctamente.');
    }

    public function update(DriverRequest $request, Driver $driver): RedirectResponse
    {
        $driver->update($request->validated());

        return back()->with('success', 'Conductor actualizado correctamente.');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        if ($driver->despatches()->exists() || $driver->trips()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar: el conductor tiene guías o viajes registrados. Desactívalo en su lugar.',
            );
        }

        $driver->delete();

        return back()->with('success', 'Conductor eliminado.');
    }

    /** Consulta de DNI para prellenar nombres y apellidos. */
    public function consultar(Request $request, DocumentLookupService $lookup): JsonResponse
    {
        $data = $request->validate(['numero' => ['required', 'digits:8']]);

        try {
            return response()->json(['data' => $lookup->lookup('1', $data['numero'])]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
