<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_permission_grants', function (Blueprint $table): void {
            $table->id();
            $table->string('permission_key', 80);
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('group_id')
                ->nullable()
                ->constrained('admin_permission_groups')
                ->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['permission_key', 'user_id'], 'apg_permission_user_unique');
            $table->unique(['permission_key', 'group_id'], 'apg_permission_group_unique');
            $table->index(['permission_key', 'user_id'], 'apg_permission_user_idx');
            $table->index(['permission_key', 'group_id'], 'apg_permission_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permission_grants');
    }
};
