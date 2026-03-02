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
        Schema::create('record_box', function (Blueprint $table) {
            $table->foreignId('archive_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->primary(['archive_record_id', 'box_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_box');
    }
};
