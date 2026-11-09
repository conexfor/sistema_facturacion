<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('placa', 10);
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->string('tipo', 30)->nullable();
            // Certificado de habilitación vehicular / TUC (MTC)
            $table->string('cert_habilitacion', 30)->nullable();
            $table->decimal('capacidad_kg', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'placa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
