<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // Keep only one global admin (smallest id), convert remaining admins to teamlead.
        $primaryAdminId = DB::table('users')->where('role', 'admin')->min('id');

        if ($primaryAdminId !== null) {
            DB::table('users')
                ->where('role', 'admin')
                ->where('id', '!=', $primaryAdminId)
                ->update(['role' => 'teamlead']);
        }

        // Normalize organization-level role name: admin -> teamlead.
        DB::table('organization_user')
            ->where('role', 'admin')
            ->update(['role' => 'teamlead']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
