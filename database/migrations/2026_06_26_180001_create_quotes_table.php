<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // cotizacion | pedido
            $table->string('tipo', 12);
            $table->string('serie', 6);
            $table->unsignedInteger('correlativo');
            $table->date('fecha');
            $table->date('fecha_validez')->nullable();
            $table->string('moneda', 3)->default('PEN');

            $table->string('cliente_documento', 20)->nullable();
            $table->string('cliente_nombre')->default('Sin cliente');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('igv', 14, 2)->default(0);
            $table->decimal('descuento_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // pendiente | aprobada | convertida | anulada
            $table->string('estado', 12)->default('pendiente');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tipo', 'serie', 'correlativo'], 'quotes_doc_unique');
            $table->index(['company_id', 'tipo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
