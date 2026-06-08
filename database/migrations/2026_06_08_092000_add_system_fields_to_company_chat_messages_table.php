<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildForSqlite();

            return;
        }

        Schema::table('company_chat_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_chat_messages', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('read_at');
            }
        });

        DB::statement('ALTER TABLE company_chat_messages MODIFY sender_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildBackForSqlite();

            return;
        }

        DB::statement('ALTER TABLE company_chat_messages MODIFY sender_id BIGINT UNSIGNED NOT NULL');

        if (Schema::hasColumn('company_chat_messages', 'is_system')) {
            Schema::table('company_chat_messages', function (Blueprint $table): void {
                $table->dropColumn('is_system');
            });
        }
    }

    private function rebuildForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('company_chat_messages_tmp', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_conversation_id')->constrained('company_chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $rows = DB::table('company_chat_messages')->orderBy('id')->get();

        foreach ($rows as $row) {
            DB::table('company_chat_messages_tmp')->insert([
                'id' => $row->id,
                'company_chat_conversation_id' => $row->company_chat_conversation_id,
                'sender_id' => $row->sender_id,
                'body' => $row->body,
                'attachments' => $row->attachments,
                'read_at' => $row->read_at,
                'is_system' => (bool) ($row->is_system ?? false),
                'edited_at' => $row->edited_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }

        Schema::drop('company_chat_messages');
        Schema::rename('company_chat_messages_tmp', 'company_chat_messages');

        Schema::table('company_chat_messages', function (Blueprint $table): void {
            $table->index(['company_chat_conversation_id', 'created_at'], 'ccm_conversation_created_idx');
            $table->index(['sender_id', 'read_at'], 'ccm_sender_read_idx');
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function rebuildBackForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('company_chat_messages_tmp', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_chat_conversation_id')->constrained('company_chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $rows = DB::table('company_chat_messages')->orderBy('id')->get();

        foreach ($rows as $row) {
            if ($row->sender_id === null) {
                continue;
            }

            DB::table('company_chat_messages_tmp')->insert([
                'id' => $row->id,
                'company_chat_conversation_id' => $row->company_chat_conversation_id,
                'sender_id' => $row->sender_id,
                'body' => $row->body,
                'attachments' => $row->attachments,
                'read_at' => $row->read_at,
                'edited_at' => $row->edited_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);
        }

        Schema::drop('company_chat_messages');
        Schema::rename('company_chat_messages_tmp', 'company_chat_messages');

        Schema::table('company_chat_messages', function (Blueprint $table): void {
            $table->index(['company_chat_conversation_id', 'created_at'], 'ccm_conversation_created_idx');
            $table->index(['sender_id', 'read_at'], 'ccm_sender_read_idx');
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
