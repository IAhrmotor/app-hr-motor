<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users', indexName: 'ccc_user_one_fk')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users', indexName: 'ccc_user_two_fk')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_excerpt', 160)->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id'], 'ccc_user_pair_unique');
            $table->index(['last_message_at', 'updated_at'], 'ccc_last_message_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_conversations');
    }
};
