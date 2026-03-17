<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disaster_sync_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('target_ip');
            $table->time('sync_time');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sync_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disaster_sync_schedules');
    }
};
