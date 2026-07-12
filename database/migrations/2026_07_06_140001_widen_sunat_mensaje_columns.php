<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los mensajes de error de SUNAT (CDR/ticket) pueden superar los 255
     * caracteres de un VARCHAR y provocan un "Data too long" al guardarlos.
     */
    public function up(): void
    {
        Schema::table('boleta_summaries', function (Blueprint $table) {
            $table->text('mensaje')->nullable()->change();
        });

        Schema::table('voided_documents', function (Blueprint $table) {
            $table->text('mensaje')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('boleta_summaries', function (Blueprint $table) {
            $table->string('mensaje')->nullable()->change();
        });

        Schema::table('voided_documents', function (Blueprint $table) {
            $table->string('mensaje')->nullable()->change();
        });
    }
};
