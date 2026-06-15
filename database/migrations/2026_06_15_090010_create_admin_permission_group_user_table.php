<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_permission_group_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_permission_group_id')
                ->constrained('admin_permission_groups')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_permission_group_id', 'user_id'], 'apgu_group_user_unique');
            $table->index(['user_id', 'admin_permission_group_id'], 'apgu_user_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permission_group_user');
    }
};
