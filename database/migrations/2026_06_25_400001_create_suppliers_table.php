<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('tipo_documento', 2)->default('6');
            $table->string('numero_documento', 20)->nullable();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('direccion')->nullable();
            $table->string('contacto')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'tipo_documento', 'numero_documento'], 'suppliers_doc_unique');
            $table->index(['company_id', 'razon_social']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
