<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('lote', 50);
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('cantidad', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'lote'], 'product_lots_unique');
            $table->index(['company_id', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lots');
    }
};
