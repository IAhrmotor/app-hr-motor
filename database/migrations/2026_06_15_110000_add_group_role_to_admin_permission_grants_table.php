<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_permission_grants', function (Blueprint $table): void {
            $table->string('group_role', 80)->nullable()->after('group_id');
            $table->unique(['permission_key', 'group_role'], 'apg_permission_group_role_unique');
            $table->index(['permission_key', 'group_role'], 'apg_permission_group_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admin_permission_grants', function (Blueprint $table): void {
            $table->dropUnique('apg_permission_group_role_unique');
            $table->dropIndex('apg_permission_group_role_idx');
            $table->dropColumn('group_role');
        });
    }
};
