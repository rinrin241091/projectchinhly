<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archivals', function (Blueprint $table) {
            $table->string('identifier', 100)
                ->comment('Mã cơ quan lưu trữ')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('archivals', function (Blueprint $table) {
            $table->string('identifier', 20)
                ->comment('Mã cơ quan lưu trữ')
                ->change();
        });
    }
};