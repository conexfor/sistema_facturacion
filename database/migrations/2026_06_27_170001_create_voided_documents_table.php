<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voided_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->date('fecha_referencia');     // fecha de emisión del comprobante
            $table->date('fecha_comunicacion');   // fecha de la baja
            $table->unsignedInteger('correlativo');
            $table->string('identificador', 30);  // RA-YYYYMMDD-N
            $table->string('motivo');

            $table->string('ticket')->nullable();
            // pendiente | enviado | aceptado | observado | rechazado | simulado
            $table->string('estado', 12)->default('pendiente');
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('mensaje')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'identificador']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voided_documents');
    }
};
