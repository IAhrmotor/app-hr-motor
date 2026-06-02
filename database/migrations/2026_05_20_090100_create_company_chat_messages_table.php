<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_chat_messages')) {
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

            return;
        }

        Schema::table('company_chat_messages', function (Blueprint $table): void {
            if (! $this->indexExists('company_chat_messages', 'ccm_conversation_created_idx')) {
                $table->index(['company_chat_conversation_id', 'created_at'], 'ccm_conversation_created_idx');
            }

            if (! $this->indexExists('company_chat_messages', 'ccm_sender_read_idx')) {
                $table->index(['sender_id', 'read_at'], 'ccm_sender_read_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_messages');
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(1) AS aggregate
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?',
            [$table, $index]
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }
};
