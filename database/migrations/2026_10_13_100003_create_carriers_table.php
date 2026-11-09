<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('ruc', 11);
            $table->string('razon_social');
            $table->string('nro_mtc', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'ruc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
