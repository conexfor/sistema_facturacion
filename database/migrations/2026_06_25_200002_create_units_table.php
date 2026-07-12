<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Código SUNAT (catálogo 03), p.ej. NIU, KGM, LTR
            $table->string('codigo', 10);
            $table->string('nombre');
            $table->string('abreviatura', 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
