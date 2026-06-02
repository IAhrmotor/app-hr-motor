<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_conversation_id')->constrained('company_chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['company_chat_conversation_id', 'created_at'], 'ccm_conversation_created_idx');
            $table->index(['sender_id', 'read_at'], 'ccm_sender_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_messages');
    }
};
