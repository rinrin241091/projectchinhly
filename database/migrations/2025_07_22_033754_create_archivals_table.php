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
        Schema::create('archivals', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 20)->unique()->comment('Mã cơ quan lưu trữ');
            $table->string('name', 255)->comment('Tên cơ quan lưu trữ');
            $table->string('address', 255)->nullable()->comment('Địa chỉ');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->string('email', 100)->nullable()->comment('Email liên hệ');
            $table->string('manager', 100)->nullable()->comment('Tên người phụ trách');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivals');
    }
};
