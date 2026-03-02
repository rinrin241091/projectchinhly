<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caterogy_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: 'category_id')->constrained(table:'categories')->cascadeOnDelete();
            $table->foreignId(column: 'product_id') -> constrained(table:'products')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caterogy_product');
    }
};
