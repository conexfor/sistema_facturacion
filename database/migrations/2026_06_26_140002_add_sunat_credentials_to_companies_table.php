<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('sol_user')->nullable()->after('sunat_modo');
            $table->text('sol_pass')->nullable()->after('sol_user');
            $table->string('certificate_path')->nullable()->after('sol_pass');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['sol_user', 'sol_pass', 'certificate_path']);
        });
    }
};
