<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Catálogo 06 SUNAT: 1=DNI, 6=RUC, 7=Pasaporte, 4=Carné ext., 0=Otros
            $table->string('tipo_documento', 2)->default('1');
            $table->string('numero_documento', 20)->nullable();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ubigeo', 6)->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'tipo_documento', 'numero_documento'], 'customers_doc_unique');
            $table->index(['company_id', 'razon_social']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
