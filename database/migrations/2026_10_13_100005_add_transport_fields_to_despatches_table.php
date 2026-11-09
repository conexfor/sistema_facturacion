<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula la guía con los catálogos de transporte, la transferencia que la
     * origina (motivo 04) y el viaje que la lleva. Las columnas de texto
     * existentes (vehiculo_placa, chofer_*, transportista_*) se mantienen como
     * snapshot: la guía emitida no cambia si luego se edita el catálogo.
     */
    public function up(): void
    {
        Schema::table('despatches', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('sale_id')->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')->constrained('drivers')->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->after('driver_id')->constrained('carriers')->nullOnDelete();
            $table->foreignId('transfer_id')->nullable()->after('carrier_id')->constrained('transfers')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->after('transfer_id')->constrained('trips')->nullOnDelete();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('ubigeo', 6)->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('despatches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trip_id');
            $table->dropConstrainedForeignId('transfer_id');
            $table->dropConstrainedForeignId('carrier_id');
            $table->dropConstrainedForeignId('driver_id');
            $table->dropConstrainedForeignId('vehicle_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('ubigeo');
        });
    }
};
