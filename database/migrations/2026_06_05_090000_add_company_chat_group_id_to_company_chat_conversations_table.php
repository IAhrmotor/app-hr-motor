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

        Schema::table('company_chat_conversations', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_chat_conversations', 'company_chat_group_id')) {
                $table->foreignId('company_chat_group_id')
                    ->nullable()
                    ->after('user_two_id')
                    ->constrained('company_chat_groups')
                    ->cascadeOnDelete();
            }
        });

        DB::statement('ALTER TABLE company_chat_conversations MODIFY user_one_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE company_chat_conversations MODIFY user_two_id BIGINT UNSIGNED NULL');

        if (! $this->indexExists('company_chat_conversations', 'ccc_group_unique')) {
            Schema::table('company_chat_conversations', function (Blueprint $table): void {
                $table->unique(['company_chat_group_id'], 'ccc_group_unique');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildBackForSqlite();
            return;
        }

        if ($this->indexExists('company_chat_conversations', 'ccc_group_unique')) {
            Schema::table('company_chat_conversations', function (Blueprint $table): void {
                $table->dropUnique('ccc_group_unique');
            });
        }

        if (Schema::hasColumn('company_chat_conversations', 'company_chat_group_id')) {
            Schema::table('company_chat_conversations', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('company_chat_group_id');
            });
        }

        DB::statement('ALTER TABLE company_chat_conversations MODIFY user_one_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE company_chat_conversations MODIFY user_two_id BIGINT UNSIGNED NOT NULL');
    }

    private function rebuildForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('company_chat_conversations_tmp', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_one_id')->nullable()->constrained('users', indexName: 'ccc_user_one_fk')->cascadeOnDelete();
            $table->foreignId('user_two_id')->nullable()->constrained('users', indexName: 'ccc_user_two_fk')->cascadeOnDelete();
            $table->foreignId('company_chat_group_id')->nullable()->constrained('company_chat_groups')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_excerpt', 160)->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id'], 'ccc_user_pair_unique');
            $table->unique(['company_chat_group_id'], 'ccc_group_unique');
            $table->index(['last_message_at', 'updated_at'], 'ccc_last_message_updated_idx');
        });

        $rows = DB::table('company_chat_conversations')->orderBy('id')->get();

        foreach ($rows as $row) {
            DB::table('company_chat_conversations_tmp')->insert([
                'id' => $row->id,
                'user_one_id' => $row->user_one_id,
                'user_two_id' => $row->user_two_id,
                'company_chat_group_id' => null,
                'last_message_at' => $row->last_message_at,
                'last_message_excerpt' => $row->last_message_excerpt,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('company_chat_conversations');
        Schema::rename('company_chat_conversations_tmp', 'company_chat_conversations');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function rebuildBackForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('company_chat_conversations_tmp', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users', indexName: 'ccc_user_one_fk')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users', indexName: 'ccc_user_two_fk')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_excerpt', 160)->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id'], 'ccc_user_pair_unique');
            $table->index(['last_message_at', 'updated_at'], 'ccc_last_message_updated_idx');
        });

        $rows = DB::table('company_chat_conversations')->orderBy('id')->get();

        foreach ($rows as $row) {
            DB::table('company_chat_conversations_tmp')->insert([
                'id' => $row->id,
                'user_one_id' => $row->user_one_id,
                'user_two_id' => $row->user_two_id,
                'last_message_at' => $row->last_message_at,
                'last_message_excerpt' => $row->last_message_excerpt,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('company_chat_conversations');
        Schema::rename('company_chat_conversations_tmp', 'company_chat_conversations');

        DB::statement('PRAGMA foreign_keys = ON');
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
