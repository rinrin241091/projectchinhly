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
            $table->unsignedBigInteger('storage_id')->nullable()->after('organization_id');

            // Nếu có bảng `storages`, bạn có thể thêm khóa ngoại:
            $table->foreign('storage_id')->references('id')->on('storages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('archive_records', function (Blueprint $table) {
            $table->dropColumn('storage_id');
        });
    }
};
