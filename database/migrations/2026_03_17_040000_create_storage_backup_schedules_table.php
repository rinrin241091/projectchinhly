<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_backup_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('storage_id')->constrained('storages')->cascadeOnDelete();
            $table->time('backup_time');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->unique('storage_id');
            $table->index(['is_active', 'backup_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_backup_schedules');
    }
};
