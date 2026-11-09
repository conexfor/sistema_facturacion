<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Viajes (hoja de ruta): un vehículo con su conductor que lleva una o
     * varias guías de remisión. Registra el avance (estado), kilometraje y
     * costos del traslado.
     */
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->date('fecha');
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
            // programado, en_ruta, entregado, incidencia, cancelado
            $table->string('estado', 12)->default('programado');
            $table->timestamp('salida_at')->nullable();
            $table->timestamp('llegada_at')->nullable();

            $table->decimal('km_inicial', 12, 1)->nullable();
            $table->decimal('km_final', 12, 1)->nullable();
            $table->decimal('costo_combustible', 12, 2)->default(0);
            $table->decimal('costo_peajes', 12, 2)->default(0);
            $table->decimal('costo_otros', 12, 2)->default(0);

            $table->string('observacion')->nullable();
            $table->text('incidencia')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'fecha']);
            $table->index(['company_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
