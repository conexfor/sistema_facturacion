<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el estado y la condición del contribuyente devueltos por SUNAT
 * (estado: ACTIVO/BAJA…, condición: HABIDO/NO HABIDO) para advertir antes de
 * emitir una factura a un RUC no habilitado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'estado_contribuyente')) {
                $table->string('estado_contribuyente', 20)->nullable()->after('ubigeo');
            }
            if (! Schema::hasColumn('customers', 'condicion_domicilio')) {
                $table->string('condicion_domicilio', 20)->nullable()->after('estado_contribuyente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach (['estado_contribuyente', 'condicion_domicilio'] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
