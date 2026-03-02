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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('doc_type_id')->constrained()->onDelete('cascade');
            $table->string('document_code')->nullable();
            $table->text('description');           
            $table->text('author')->nullable();
            $table->string('page_number')->nullable();
            $table->date('document_date')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
