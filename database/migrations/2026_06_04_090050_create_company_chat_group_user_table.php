<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_group_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_group_id')->constrained('company_chat_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_chat_group_id', 'user_id'], 'ccgu_group_user_unique');
            $table->index(['user_id', 'company_chat_group_id'], 'ccgu_user_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_group_user');
    }
};
