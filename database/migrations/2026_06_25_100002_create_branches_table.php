<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Código de establecimiento anexo SUNAT (4 dígitos), '0000' = principal
            $table->string('codigo', 4)->default('0000');
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('ubigeo', 6)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
