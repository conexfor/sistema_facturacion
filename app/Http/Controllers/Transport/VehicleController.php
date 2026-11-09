<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $vehicles = Vehicle::query()
            ->withCount(['despatches', 'trips'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('placa', 'like', "%{$s}%")
                    ->orWhere('marca', 'like', "%{$s}%")
                    ->orWhere('modelo', 'like', "%{$s}%");
            }))
            ->orderByDesc('is_active')
            ->orderBy('placa')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Transport/Vehicles/Index', [
            'vehicles' => $vehicles,
            'tipos' => Vehicle::TIPOS,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        Vehicle::create($request->validated());

        return back()->with('success', 'Vehículo registrado correctamente.');
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return back()->with('success', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->despatches()->exists() || $vehicle->trips()->exists()) {
            return back()->with(
                'error',
                'No se puede eliminar: el vehículo tiene guías o viajes registrados. Desactívalo en su lugar.',
            );
        }

        $vehicle->delete();

        return back()->with('success', 'Vehículo eliminado.');
    }
}
