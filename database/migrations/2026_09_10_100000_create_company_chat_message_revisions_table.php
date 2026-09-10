<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_message_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_message_id')
                ->constrained('company_chat_messages')
                ->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamp('edited_at');
            $table->foreignId('edited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['company_chat_message_id', 'edited_at'],
                'ccmr_message_edited_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_message_revisions');
    }
};
