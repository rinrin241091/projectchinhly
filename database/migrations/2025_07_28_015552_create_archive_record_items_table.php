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
        Schema::create('archive_record_items', function (Blueprint $table) {
            $table->id();
            $table->string('archive_record_item_code');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('title'); // Tên tài liệu/mục lục
            $table->text('description')->nullable();
            $table->string('document_date')->nullable(); // Ngày tài liệu (nếu có)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_record_items');
    }
};
// <td>
//     {{ $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : 'N/A' }}<br>
//     {{ $record->end_date ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y') : 'N/A' }}
// </td>
