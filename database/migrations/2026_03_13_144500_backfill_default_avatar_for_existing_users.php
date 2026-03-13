<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('avatar_path')
            ->orWhere('avatar_path', '')
            ->orWhere('avatar_path', 'images/users/default-avatar.svg')
            ->update(['avatar_path' => 'images/users/hrmotor-default-user-avatar.png']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos este backfill para no perder datos ya normalizados.
    }
};
