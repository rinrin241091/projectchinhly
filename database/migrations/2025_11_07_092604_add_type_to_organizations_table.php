<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->char('type', 20)
                ->nullable()
                ->comment('Loại phông: Đảng hoặc Chính quyền')
                ->after('name'); // thêm sau cột name (tùy bạn đặt)
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
