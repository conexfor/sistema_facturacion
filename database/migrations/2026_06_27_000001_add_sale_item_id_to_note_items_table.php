<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('note_items', function (Blueprint $table) {
            $table->foreignId('sale_item_id')->nullable()->after('product_id')
                ->constrained('sale_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('note_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_item_id');
        });
    }
};
