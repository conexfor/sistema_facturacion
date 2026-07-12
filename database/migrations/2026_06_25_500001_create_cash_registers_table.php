<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('nombre');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
