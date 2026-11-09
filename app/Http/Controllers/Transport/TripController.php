<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Despatch;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TripController extends Controller
{
    public function __construct(private readonly TripService $trips)
    {
    }

    public function index(Request $request): Response
    {
        $estado = $request->input('estado');

        $trips = Trip::query()
            ->with(['vehicle:id,placa', 'driver:id,nombres,apellidos'])
            ->withCount('despatches')
            ->when($estado && array_key_exists($estado, Trip::ESTADOS), fn ($q) => $q->where('estado', $estado))
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('destino', 'like', "%{$s}%")
                    ->orWhere('origen', 'like', "%{$s}%")
                    ->orWhereHas('vehicle', fn ($q) => $q->where('placa', 'like', "%{$s}%"))
                    ->orWhereHas('driver', fn ($q) => $q->where('apellidos', 'like', "%{$s}%")
                        ->orWhere('nombres', 'like', "%{$s}%"));
            }))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $conteo = Trip::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return Inertia::render('Transport/Trips/Index', [
            'trips' => $trips,
            'estados' => Trip::ESTADOS,
            'conteo' => $conteo,
            'filters' => $request->only(['search', 'estado']),
            ...$this->catalogos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request) + [
            'despatch_ids' => ['nullable', 'array'],
            'despatch_ids.*' => ['integer'],
        ]);

        try {
            $trip = $this->trips->crear($data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('transporte.viajes.show', $trip)
            ->with('success', "Viaje {$trip->numero} programado.");
    }

    public function show(Trip $trip): Response
    {
        $trip->load([
            'vehicle', 'driver', 'user:id,name',
            'despatches' => fn ($q) => $q->orderBy('id'),
        ]);

        return Inertia::render('Transport/Trips/Show', [
            'trip' => $trip,
            'estados' => Trip::ESTADOS,
            'transiciones' => Trip::TRANSICIONES[$trip->estado] ?? [],
            'editable' => $trip->esEditable(),
            ...$this->catalogos(),
        ]);
    }

    public function update(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate($this->rules($request) + [
            'km_final' => ['nullable', 'numeric', 'min:0'],
            'costo_combustible' => ['nullable', 'numeric', 'min:0'],
            'costo_peajes' => ['nullable', 'numeric', 'min:0'],
            'costo_otros' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach (['costo_combustible', 'costo_peajes', 'costo_otros'] as $campo) {
            $data[$campo] = (float) ($data[$campo] ?? 0);
        }

        try {
            $this->trips->actualizar($trip, $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Viaje actualizado.');
    }

    public function estado(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Trip::ESTADOS))],
            'km_inicial' => ['nullable', 'numeric', 'min:0'],
            'km_final' => ['nullable', 'numeric', 'min:0'],
            'incidencia' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $trip = $this->trips->cambiarEstado($trip, $data['estado'], $data);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Viaje {$trip->numero}: " . Trip::ESTADOS[$trip->estado] . '.');
    }

    public function buscarGuias(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');

        $guias = $this->trips->guiasDisponibles()
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('dest_nombre', 'like', "%{$q}%")
                    ->orWhere('llegada_direccion', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(serie,'-',LPAD(correlativo,6,'0')) like ?", ["%{$q}%"]);
            }))
            ->orderByDesc('fecha_traslado')
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'serie', 'correlativo', 'fecha_traslado', 'dest_nombre', 'llegada_direccion', 'peso_total', 'vehiculo_placa']);

        return response()->json($guias->map(fn (Despatch $d) => [
            'id' => $d->id,
            'numero' => $d->numero,
            'fecha_traslado' => $d->fecha_traslado->toDateString(),
            'dest_nombre' => $d->dest_nombre,
            'llegada_direccion' => $d->llegada_direccion,
            'peso_total' => (float) $d->peso_total,
            'vehiculo_placa' => $d->vehiculo_placa,
        ]));
    }

    public function asignarGuias(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate([
            'despatch_ids' => ['required', 'array', 'min:1'],
            'despatch_ids.*' => ['integer'],
        ]);

        try {
            $this->trips->asignarGuias($trip, $data['despatch_ids']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Guías asignadas al viaje.');
    }

    public function quitarGuia(Trip $trip, Despatch $despatch): RedirectResponse
    {
        try {
            $this->trips->quitarGuia($trip, $despatch);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Guía {$despatch->numero} retirada del viaje.");
    }

    private function rules(Request $request): array
    {
        $companyId = $request->user()->company_id;

        return [
            'fecha' => ['required', 'date'],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->where('company_id', $companyId)],
            'driver_id' => ['required', Rule::exists('drivers', 'id')->where('company_id', $companyId)],
            'origen' => ['nullable', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
            'km_inicial' => ['nullable', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function catalogos(): array
    {
        return [
            'vehiculos' => Vehicle::where('is_active', true)->orderBy('placa')
                ->get(['id', 'placa', 'marca', 'modelo', 'capacidad_kg']),
            'conductores' => Driver::where('is_active', true)->orderBy('apellidos')
                ->get(['id', 'nombres', 'apellidos', 'licencia', 'licencia_vence']),
        ];
    }
}
