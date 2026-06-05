<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_message_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_message_id')->constrained('company_chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['company_chat_message_id', 'user_id'], 'ccmr_message_user_unique');
            $table->index(['user_id', 'read_at'], 'ccmr_user_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_message_reads');
    }
};
