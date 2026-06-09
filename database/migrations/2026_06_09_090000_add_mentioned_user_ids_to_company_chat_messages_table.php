<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_chat_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_chat_messages', 'mentioned_user_ids')) {
                $table->json('mentioned_user_ids')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_chat_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('company_chat_messages', 'mentioned_user_ids')) {
                $table->dropColumn('mentioned_user_ids');
            }
        });
    }
};
