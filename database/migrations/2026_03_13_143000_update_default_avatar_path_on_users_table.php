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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')
                ->default('images/users/hrmotor-default-user-avatar.png')
                ->change();
        });

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
        if (! Schema::hasColumn('users', 'avatar_path')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')
                ->default('images/users/default-avatar.svg')
                ->change();
        });

        DB::table('users')
            ->where('avatar_path', 'images/users/hrmotor-default-user-avatar.png')
            ->update(['avatar_path' => 'images/users/default-avatar.svg']);
    }
};
