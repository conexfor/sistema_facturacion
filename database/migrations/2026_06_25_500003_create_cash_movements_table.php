<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha')->useCurrent();
            // ingreso | egreso
            $table->string('tipo', 10);
            // venta | cobro | pago_compra | gasto | retiro | deposito | ajuste | otro
            $table->string('categoria', 20)->default('otro');
            $table->string('concepto');
            $table->decimal('monto', 14, 2);
            $table->string('metodo_pago', 15)->default('efectivo');

            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->string('referencia')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'cash_session_id'], 'cash_mov_cs_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
