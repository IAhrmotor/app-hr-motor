<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_chat_conversations')) {
            return;
        }

        Schema::table('company_chat_conversations', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_chat_conversations', 'retention_hold')) {
                $table->boolean('retention_hold')->default(false)->index('ccc_retention_hold_repair_idx');
            }

            if (! Schema::hasColumn('company_chat_conversations', 'retention_hold_reason')) {
                $table->text('retention_hold_reason')->nullable();
            }

            if (! Schema::hasColumn('company_chat_conversations', 'retention_hold_created_at')) {
                $table->timestamp('retention_hold_created_at')->nullable();
            }

            if (! Schema::hasColumn('company_chat_conversations', 'retention_hold_created_by')) {
                $table->foreignId('retention_hold_created_by')
                    ->nullable()
                    ->constrained('users', indexName: 'ccc_retention_created_by_repair_fk')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('company_chat_conversations', 'retention_hold_expires_at')) {
                $table->timestamp('retention_hold_expires_at')->nullable()->index('ccc_retention_expires_repair_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_chat_conversations')) {
            return;
        }

        Schema::table('company_chat_conversations', function (Blueprint $table): void {
            if (Schema::hasColumn('company_chat_conversations', 'retention_hold_created_by')) {
                $table->dropConstrainedForeignId('retention_hold_created_by');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('company_chat_conversations', 'retention_hold') ? 'retention_hold' : null,
                Schema::hasColumn('company_chat_conversations', 'retention_hold_reason') ? 'retention_hold_reason' : null,
                Schema::hasColumn('company_chat_conversations', 'retention_hold_created_at') ? 'retention_hold_created_at' : null,
                Schema::hasColumn('company_chat_conversations', 'retention_hold_expires_at') ? 'retention_hold_expires_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
