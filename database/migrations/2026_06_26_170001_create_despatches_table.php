<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->string('serie', 5)->default('T001');
            $table->unsignedInteger('correlativo');
            $table->date('fecha_emision');
            $table->date('fecha_traslado');

            // Catálogo 20 (motivo) y 18 (modalidad)
            $table->string('cod_traslado', 2)->default('01');
            $table->string('des_traslado');
            $table->string('mod_traslado', 2)->default('02'); // 01 público, 02 privado
            $table->decimal('peso_total', 12, 3)->default(0);
            $table->string('und_peso', 5)->default('KGM');
            $table->unsignedInteger('num_bultos')->default(1);

            // Destinatario
            $table->string('dest_tipo_doc', 2)->default('6');
            $table->string('dest_num_doc', 20)->nullable();
            $table->string('dest_nombre');

            // Puntos de partida y llegada
            $table->string('partida_ubigeo', 6)->nullable();
            $table->string('partida_direccion');
            $table->string('llegada_ubigeo', 6)->nullable();
            $table->string('llegada_direccion');

            // Transporte público (transportista)
            $table->string('transportista_doc', 20)->nullable();
            $table->string('transportista_nombre')->nullable();
            $table->string('transportista_mtc', 30)->nullable();

            // Transporte privado (vehículo + chofer)
            $table->string('vehiculo_placa', 20)->nullable();
            $table->string('chofer_tipo_doc', 2)->nullable();
            $table->string('chofer_num_doc', 20)->nullable();
            $table->string('chofer_licencia', 20)->nullable();
            $table->string('chofer_nombres')->nullable();
            $table->string('chofer_apellidos')->nullable();

            $table->string('observacion')->nullable();

            $table->string('sunat_estado', 15)->default('no_enviado');
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->string('sunat_hash')->nullable();
            $table->text('sunat_mensaje')->nullable();
            $table->timestamp('sunat_enviado_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'serie', 'correlativo'], 'despatches_doc_unique');
            $table->index(['company_id', 'fecha_emision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despatches');
    }
};
