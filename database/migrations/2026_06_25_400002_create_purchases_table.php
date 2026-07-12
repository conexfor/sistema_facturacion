<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // factura | boleta | recibo | nota | ticket
            $table->string('tipo_comprobante', 15)->default('factura');
            $table->string('serie', 10)->nullable();
            $table->string('numero', 20)->nullable();

            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('moneda', 3)->default('PEN');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('igv', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('monto_pagado', 14, 2)->default(0);

            // pagada | pendiente | parcial
            $table->string('estado_pago', 12)->default('pendiente');
            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'fecha']);
            $table->index(['company_id', 'estado_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
