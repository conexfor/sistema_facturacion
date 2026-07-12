<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // boleta | factura | nota_venta
            $table->string('tipo_comprobante', 15)->default('boleta');
            $table->string('serie', 5);
            $table->unsignedInteger('correlativo');
            // Snapshot de datos del cliente al momento de la venta
            $table->string('cliente_documento', 20)->nullable();
            $table->string('cliente_nombre')->default('Consumidor final');

            $table->timestamp('fecha')->useCurrent();
            $table->string('moneda', 3)->default('PEN');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('igv', 14, 2)->default(0);
            $table->decimal('descuento_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // pagada | pendiente | anulada
            $table->string('estado', 12)->default('pagada');
            // efectivo | tarjeta | transferencia | yape | plin | credito
            $table->string('metodo_pago', 15)->default('efectivo');
            $table->decimal('monto_recibido', 14, 2)->default(0);
            $table->decimal('vuelto', 14, 2)->default(0);

            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tipo_comprobante', 'serie', 'correlativo'], 'sales_comprobante_unique');
            $table->index(['company_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
