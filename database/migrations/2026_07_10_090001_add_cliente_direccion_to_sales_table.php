<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el domicilio fiscal del cliente al momento de la venta (snapshot),
 * para que la representación impresa (PDF/ticket) del comprobante muestre
 * siempre la dirección tributaria válida aunque el cliente se edite después.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'cliente_direccion')) {
                $table->string('cliente_direccion')->nullable()->after('cliente_nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'cliente_direccion')) {
                $table->dropColumn('cliente_direccion');
            }
        });
    }
};
