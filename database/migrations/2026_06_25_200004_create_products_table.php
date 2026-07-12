<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('codigo', 50);
            $table->string('codigo_barras', 50)->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            // bien | servicio
            $table->string('tipo', 10)->default('bien');
            $table->decimal('precio_compra', 12, 4)->default(0);
            $table->decimal('precio_venta', 12, 4)->default(0);
            $table->boolean('afecto_igv')->default(true);
            // Para inventario valorizado: costo promedio ponderado actual
            $table->decimal('costo_promedio', 12, 4)->default(0);
            $table->decimal('stock_minimo', 15, 4)->default(0);
            // Los servicios no controlan stock
            $table->boolean('controla_stock')->default(true);
            $table->string('imagen_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'codigo']);
            $table->index(['company_id', 'codigo_barras']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
