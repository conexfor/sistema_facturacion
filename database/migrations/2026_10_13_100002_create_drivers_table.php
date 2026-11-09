<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('tipo_doc', 2)->default('1');
            $table->string('num_doc', 20);
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('licencia', 20);
            $table->date('licencia_vence')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'tipo_doc', 'num_doc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
