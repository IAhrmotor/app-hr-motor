<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_retention_hold_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_conversation_id')->constrained('company_chat_conversations', indexName: 'ccrha_conv_fk')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users', indexName: 'ccrha_admin_fk')->nullOnDelete();
            $table->string('action', 40)->index('ccrha_action_idx');
            $table->text('reason')->nullable();
            $table->text('previous_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('previous_expires_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source', 60)->default('web-admin');
            $table->timestamps();

            $table->index(['company_chat_conversation_id', 'action'], 'ccrha_conv_action_idx');
            $table->index(['admin_user_id', 'created_at'], 'ccrha_admin_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_retention_hold_audits');
    }
};
