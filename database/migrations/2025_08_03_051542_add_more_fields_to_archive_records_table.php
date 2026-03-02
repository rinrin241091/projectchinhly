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
    Schema::table('archive_records', function (Blueprint $table) {
        $table->string('preservation_duration')->nullable(); // Thời hạn bảo quản
        $table->integer('page_count')->nullable(); // Số lượng tờ
        $table->string('condition')->nullable(); // Tình trạng
        $table->text('note')->nullable(); // Ghi chú
    });
}

public function down(): void
{
    Schema::table('archive_records', function (Blueprint $table) {
        $table->dropColumn(['preservation_duration', 'page_count', 'condition', 'note']);
    });
}
};
