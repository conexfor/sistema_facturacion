<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boleta_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Día de las boletas (FecGeneracion) y día de envío del resumen (FecResumen)
            $table->date('fecha_referencia');
            $table->date('fecha_envio');
            $table->unsignedInteger('correlativo');
            // Identificador SUNAT del resumen: RC-YYYYMMDD-N
            $table->string('identificador', 30);

            $table->string('ticket')->nullable();
            // pendiente | enviado | aceptado | observado | rechazado | simulado
            $table->string('estado', 12)->default('pendiente');
            $table->unsignedInteger('total_boletas')->default(0);

            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('mensaje')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'identificador']);
            $table->index(['company_id', 'fecha_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boleta_summaries');
    }
};
