<?php

namespace App\Services;

use App\Models\Despatch;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TripService
{
    /** Estados SUNAT con los que una guía ya ampara el traslado. */
    public const GUIA_ESTADOS_VALIDOS = ['aceptado', 'observado', 'enviado', 'simulado'];

    /**
     * Guías que se pueden cargar a un viaje: transporte privado (vehículo
     * propio), ya emitidas y sin viaje asignado.
     */
    public function guiasDisponibles(): Builder
    {
        return Despatch::query()
            ->where('mod_traslado', '02')
            ->whereNull('trip_id')
            ->whereIn('sunat_estado', self::GUIA_ESTADOS_VALIDOS);
    }

    public function crear(array $data): Trip
    {
        $user = Auth::user();

        return DB::transaction(function () use ($data, $user) {
            $trip = Trip::create(array_merge(
                collect($data)->except('despatch_ids')->all(),
                [
                    'company_id' => $user->company_id,
                    'branch_id' => $user->branch_id,
                    'user_id' => $user->id,
                    'estado' => 'programado',
                ],
            ));

            if (! empty($data['despatch_ids'])) {
                $this->asignarGuias($trip, $data['despatch_ids']);
            }

            return $trip;
        });
    }

    public function actualizar(Trip $trip, array $data): Trip
    {
        if (in_array($trip->estado, ['entregado', 'cancelado'], true)) {
            // Cerrado: solo se completan costos, kilometraje y observaciones.
            $data = collect($data)->only([
                'km_final', 'costo_combustible', 'costo_peajes', 'costo_otros', 'observacion',
            ])->all();
        } elseif ($trip->estado !== 'programado') {
            // En ruta ya no se cambia de vehículo ni de conductor.
            unset($data['vehicle_id'], $data['driver_id']);
        }

        $this->validarKilometraje($data['km_inicial'] ?? $trip->km_inicial, $data['km_final'] ?? $trip->km_final);

        $trip->update($data);

        return $trip->refresh();
    }

    /** @param array<int> $despatchIds */
    public function asignarGuias(Trip $trip, array $despatchIds): void
    {
        if (! $trip->esEditable()) {
            throw new RuntimeException('El viaje ya está cerrado; no se pueden asignar guías.');
        }

        $guias = $this->guiasDisponibles()->whereIn('id', $despatchIds)->get();

        if ($guias->count() !== count(array_unique($despatchIds))) {
            throw new RuntimeException(
                'Alguna guía no está disponible: debe ser de transporte privado, estar emitida y no tener viaje asignado.',
            );
        }

        Despatch::whereIn('id', $guias->pluck('id'))->update(['trip_id' => $trip->id]);
    }

    public function quitarGuia(Trip $trip, Despatch $despatch): void
    {
        if (! $trip->esEditable()) {
            throw new RuntimeException('El viaje ya está cerrado; no se pueden quitar guías.');
        }

        if ($despatch->trip_id !== $trip->id) {
            throw new RuntimeException('La guía no pertenece a este viaje.');
        }

        $despatch->update(['trip_id' => null]);
    }

    public function cambiarEstado(Trip $trip, string $estado, array $data = []): Trip
    {
        $permitidos = Trip::TRANSICIONES[$trip->estado] ?? [];
        if (! in_array($estado, $permitidos, true)) {
            throw new RuntimeException(sprintf(
                'No se puede pasar de "%s" a "%s".',
                Trip::ESTADOS[$trip->estado] ?? $trip->estado,
                Trip::ESTADOS[$estado] ?? $estado,
            ));
        }

        $cambios = ['estado' => $estado];

        // Salida (desde una incidencia solo se retoma la ruta).
        if ($estado === 'en_ruta' && $trip->estado === 'programado') {
            $this->validarSalida($trip);
            $cambios['salida_at'] = now();
            if (isset($data['km_inicial'])) {
                $cambios['km_inicial'] = $data['km_inicial'];
            }
        }

        if ($estado === 'incidencia') {
            if (empty($data['incidencia'])) {
                throw new RuntimeException('Describe la incidencia.');
            }
            $cambios['incidencia'] = trim(($trip->incidencia ? $trip->incidencia . "\n" : '')
                . now()->format('d/m/Y H:i') . ' — ' . $data['incidencia']);
        }

        if ($estado === 'entregado') {
            $cambios['llegada_at'] = now();
            if (isset($data['km_final'])) {
                $cambios['km_final'] = $data['km_final'];
            }
        }

        $this->validarKilometraje($cambios['km_inicial'] ?? $trip->km_inicial, $cambios['km_final'] ?? $trip->km_final);

        $trip->update($cambios);

        return $trip->refresh();
    }

    /** Antes de salir: vehículo y conductor en regla, y carga dentro de la capacidad. */
    private function validarSalida(Trip $trip): void
    {
        $trip->loadMissing(['vehicle', 'driver', 'despatches']);

        if (! $trip->vehicle || ! $trip->vehicle->is_active) {
            throw new RuntimeException('Asigna un vehículo activo antes de iniciar el viaje.');
        }

        if (! $trip->driver || ! $trip->driver->is_active) {
            throw new RuntimeException('Asigna un conductor activo antes de iniciar el viaje.');
        }

        if ($trip->driver->licenciaVencida()) {
            throw new RuntimeException(
                "La licencia de {$trip->driver->nombre_completo} venció el {$trip->driver->licencia_vence->format('d/m/Y')}.",
            );
        }

        if ($trip->despatches->isEmpty()) {
            throw new RuntimeException('El viaje no tiene guías de remisión asignadas.');
        }

        $peso = (float) $trip->despatches->sum('peso_total');
        $capacidad = $trip->vehicle->capacidad_kg;
        if ($capacidad !== null && $peso > (float) $capacidad) {
            throw new RuntimeException(sprintf(
                'La carga (%s kg) supera la capacidad del vehículo %s (%s kg).',
                $peso, $trip->vehicle->placa, (float) $capacidad,
            ));
        }
    }

    private function validarKilometraje($inicial, $final): void
    {
        if ($inicial !== null && $final !== null && (float) $final < (float) $inicial) {
            throw new RuntimeException('El kilometraje final no puede ser menor que el inicial.');
        }
    }
}
