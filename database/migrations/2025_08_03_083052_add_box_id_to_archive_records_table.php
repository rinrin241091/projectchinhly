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
            $table->unsignedBigInteger('box_id')->nullable()->after('organization_id');

            // Nếu có bảng `boxes`, bạn có thể thêm foreign key như sau:
             $table->foreign('box_id')->references('id')->on('boxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('archive_records', function (Blueprint $table) {
            $table->dropColumn('box_id');
        });
    }
};
