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
        Schema::create('boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_id')->constrained()->onDelete('cascade');
            $table->string('code')->required(); //Mã hộp hồ sơ
            $table->string('description')->required(); //Tên/Mô tả hộp
            $table->string('type')->nullable(); //Loại hộp
            $table->integer('record_count')->nullable(); // Số lượng hồ sơ
            $table->integer('page_count')->nullable(); // số trang hồ sơ trong hộp
            $table->string('location')->nullable();//Vị trí ghi tự do 
            $table->string('status')->nullable(); // Trạng thái
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boxes');
    }
};
