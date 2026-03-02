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
        Schema::table('archive_record_items', function (Blueprint $table) {
            $table->integer('page_num')->nullable()->after('description'); // Thêm cột page_num
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_record_items', function (Blueprint $table) {
            $table->dropColumn('page_num'); // Xóa cột page_num nếu rollback
        });
    }
};