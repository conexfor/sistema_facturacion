<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GRE 2.0 (guías de remisión electrónicas) se envía por la API REST de
     * SUNAT y requiere credenciales de cliente API (client_id/secret) que se
     * generan en el menú SOL, aparte del usuario/clave SOL. El envío es
     * asíncrono: SUNAT devuelve un ticket que luego se consulta para el CDR.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('gre_client_id')->nullable()->after('certificate_password');
            $table->text('gre_client_secret')->nullable()->after('gre_client_id');
        });

        Schema::table('despatches', function (Blueprint $table) {
            $table->string('sunat_ticket')->nullable()->after('sunat_estado');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['gre_client_id', 'gre_client_secret']);
        });

        Schema::table('despatches', function (Blueprint $table) {
            $table->dropColumn('sunat_ticket');
        });
    }
};
