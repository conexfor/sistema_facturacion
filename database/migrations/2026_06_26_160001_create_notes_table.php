<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            // credito | debito
            $table->string('tipo', 10);
            // 07 (nota crédito) | 08 (nota débito)
            $table->string('tipo_doc', 2);
            $table->string('serie', 5);
            $table->unsignedInteger('correlativo');

            $table->timestamp('fecha')->useCurrent();
            $table->string('moneda', 3)->default('PEN');
            // Catálogo 09 (crédito) / 10 (débito)
            $table->string('cod_motivo', 2);
            $table->string('des_motivo');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('igv', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->string('sunat_estado', 15)->default('no_enviado');
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->string('sunat_hash')->nullable();
            $table->text('sunat_mensaje')->nullable();
            $table->timestamp('sunat_enviado_at')->nullable();

            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tipo_doc', 'serie', 'correlativo'], 'notes_comprobante_unique');
            $table->index(['company_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
