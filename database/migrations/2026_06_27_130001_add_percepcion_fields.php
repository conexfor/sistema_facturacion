<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('agente_percepcion')->default(false)->after('sunat_modo');
            $table->decimal('percepcion_tasa', 5, 2)->default(2.00)->after('agente_percepcion');
            // Régimen de percepción (catálogo): 01 = venta interna
            $table->string('percepcion_regimen', 2)->default('01')->after('percepcion_tasa');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('aplica_percepcion')->default(false)->after('total');
            $table->decimal('percepcion_monto', 14, 2)->default(0)->after('aplica_percepcion');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['agente_percepcion', 'percepcion_tasa', 'percepcion_regimen']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['aplica_percepcion', 'percepcion_monto']);
        });
    }
};
