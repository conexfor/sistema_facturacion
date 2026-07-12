<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // no_enviado | enviando | aceptado | observado | rechazado | anulado | simulado
            $table->string('sunat_estado', 15)->default('no_enviado')->after('estado');
            $table->string('sunat_xml_path')->nullable()->after('sunat_estado');
            $table->string('sunat_cdr_path')->nullable()->after('sunat_xml_path');
            $table->string('sunat_hash')->nullable()->after('sunat_cdr_path');
            $table->string('sunat_ticket')->nullable()->after('sunat_hash');
            $table->text('sunat_mensaje')->nullable()->after('sunat_ticket');
            $table->timestamp('sunat_enviado_at')->nullable()->after('sunat_mensaje');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'sunat_estado', 'sunat_xml_path', 'sunat_cdr_path',
                'sunat_hash', 'sunat_ticket', 'sunat_mensaje', 'sunat_enviado_at',
            ]);
        });
    }
};
