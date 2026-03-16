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
        Schema::table('borrowings', function (Blueprint $table) {
            $table->timestamp('overdue_notified_at')->nullable()->after('return_requested_at');
            $table->timestamp('due_soon_notified_at')->nullable()->after('overdue_notified_at');
            $table->index('due_soon_notified_at');
            $table->index('overdue_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropIndex(['due_soon_notified_at']);
            $table->dropIndex(['overdue_notified_at']);
            $table->dropColumn(['due_soon_notified_at', 'overdue_notified_at']);
        });
    }
};
