<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_group_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 50);
            $table->string('result', 50)->nullable();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('actor_name');
            $table->string('actor_email');
            $table->foreignId('company_chat_group_id')->nullable()->constrained('company_chat_groups')->nullOnDelete();
            $table->string('target_name');
            $table->text('target_description')->nullable();
            $table->json('changes')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['action', 'created_at'], 'ccgal_action_created_idx');
            $table->index(['actor_user_id', 'created_at'], 'ccgal_actor_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_group_activity_logs');
    }
};
