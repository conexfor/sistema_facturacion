<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despatch_id')->constrained('despatches')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 14, 4);
            $table->string('unidad', 5)->default('NIU');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despatch_items');
    }
};
