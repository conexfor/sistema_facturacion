<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha')->useCurrent();
            // entrada | salida | ajuste_entrada | ajuste_salida | transferencia_entrada | transferencia_salida
            $table->string('tipo', 30);
            $table->string('motivo')->nullable();
            $table->decimal('cantidad', 15, 4);
            $table->decimal('costo_unitario', 12, 4)->default(0);
            // Saldos tras el movimiento (kardex valorizado)
            $table->decimal('saldo_cantidad', 15, 4)->default(0);
            $table->decimal('saldo_costo_promedio', 12, 4)->default(0);
            $table->decimal('saldo_valor', 15, 4)->default(0);
            $table->string('referencia')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'product_id', 'warehouse_id', 'fecha'], 'inv_mov_cpwf_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
