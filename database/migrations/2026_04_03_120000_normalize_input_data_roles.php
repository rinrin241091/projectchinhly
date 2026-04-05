<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role', 'input_data')
                ->update(['role' => 'data_entry']);
        }

        if (Schema::hasTable('organization_user')) {
            DB::table('organization_user')
                ->where('role', 'input_data')
                ->update(['role' => 'data_entry']);

            DB::table('organization_user')
                ->where('role', 'editor')
                ->update(['role' => 'data_entry']);
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid lossy rollback of historical role data.
    }
};
