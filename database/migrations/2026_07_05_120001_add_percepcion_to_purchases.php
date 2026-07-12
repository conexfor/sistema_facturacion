<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Percepción que el proveedor (agente) cobra al comprar
            $table->decimal('percepcion_tasa', 5, 2)->default(0)->after('total');
            $table->decimal('percepcion_monto', 14, 2)->default(0)->after('percepcion_tasa');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['percepcion_tasa', 'percepcion_monto']);
        });
    }
};
