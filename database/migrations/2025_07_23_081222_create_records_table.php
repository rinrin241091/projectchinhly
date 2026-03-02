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
        Schema::create('record_files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference_code')->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('record_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_files');
    }
};
