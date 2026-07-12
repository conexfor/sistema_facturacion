<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_apertura')->useCurrent();
            $table->decimal('monto_apertura', 14, 2)->default(0);
            $table->timestamp('fecha_cierre')->nullable();
            // Saldo de efectivo esperado al cierre (apertura + ingresos - egresos)
            $table->decimal('monto_esperado', 14, 2)->nullable();
            // Efectivo realmente contado en el arqueo
            $table->decimal('monto_contado', 14, 2)->nullable();
            $table->decimal('diferencia', 14, 2)->nullable();

            // abierta | cerrada
            $table->string('estado', 10)->default('abierta');
            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
