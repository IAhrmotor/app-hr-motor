<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_chat_conversations', function (Blueprint $table): void {
            $table->boolean('retention_hold')->default(false)->index('ccc_retention_hold_idx')->after('last_message_excerpt');
            $table->text('retention_hold_reason')->nullable()->after('retention_hold');
            $table->timestamp('retention_hold_created_at')->nullable()->after('retention_hold_reason');
            $table->foreignId('retention_hold_created_by')->nullable()->constrained('users', indexName: 'ccc_ret_hold_created_by_fk')->nullOnDelete()->after('retention_hold_created_at');
            $table->timestamp('retention_hold_expires_at')->nullable()->index('ccc_ret_hold_expires_idx')->after('retention_hold_created_by');
        });
    }

    public function down(): void
    {
        Schema::table('company_chat_conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('retention_hold_created_by');
            $table->dropColumn([
                'retention_hold',
                'retention_hold_reason',
                'retention_hold_created_at',
                'retention_hold_expires_at',
            ]);
        });
    }
};
