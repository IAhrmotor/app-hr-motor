<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('extra_role')->nullable()->after('role');
        });

        DB::table('users')
            ->where('role', User::ROLE_COMMERCIAL)
            ->update([
                'extra_role' => User::ROLE_COMMERCIAL,
                'role' => User::ROLE_USER,
            ]);

        DB::table('users')
            ->where('role', User::ROLE_STORE_MANAGER)
            ->update([
                'extra_role' => User::ROLE_STORE_MANAGER,
                'role' => User::ROLE_USER,
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('extra_role', User::ROLE_COMMERCIAL)
            ->update([
                'role' => User::ROLE_COMMERCIAL,
            ]);

        DB::table('users')
            ->where('extra_role', User::ROLE_STORE_MANAGER)
            ->update([
                'role' => User::ROLE_STORE_MANAGER,
            ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('extra_role');
        });
    }
};
