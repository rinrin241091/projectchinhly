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
        Schema::table('organization_user', function (Blueprint $table) {

            // thêm cột role để xác định quyền của user trong từng phông
            $table->string('role')
                ->default('viewer')
                ->after('organization_id');

            // index để filter nhanh hơn
            $table->index('role');

            // tránh gán cùng user vào cùng 1 phông nhiều lần
            $table->unique(['user_id', 'organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {

            $table->dropUnique(['user_id', 'organization_id']);
            $table->dropIndex(['role']);
            $table->dropColumn('role');

        });
    }
};