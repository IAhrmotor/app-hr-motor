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
                ->after('salesforce_user_id');
        });

        DB::table('users')
            ->whereNull('avatar_path')
            ->update(['avatar_path' => 'images/users/hrmotor-default-user-avatar.png']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
