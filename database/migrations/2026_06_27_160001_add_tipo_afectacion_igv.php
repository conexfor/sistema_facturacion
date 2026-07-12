<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo 07 SUNAT: 10=Gravado, 20=Exonerado, 30=Inafecto
        Schema::table('products', function (Blueprint $table) {
            $table->string('tipo_afectacion_igv', 2)->default('10')->after('afecto_igv');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('tipo_afectacion', 2)->default('10')->after('afecto_igv');
        });

        // Backfill: lo que no estaba afecto al IGV pasa a Inafecto (30)
        DB::table('products')->where('afecto_igv', false)->update(['tipo_afectacion_igv' => '30']);
        DB::table('sale_items')->where('afecto_igv', false)->update(['tipo_afectacion' => '30']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tipo_afectacion_igv');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('tipo_afectacion');
        });
    }
};
