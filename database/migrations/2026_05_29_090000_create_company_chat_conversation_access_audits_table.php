<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_conversation_access_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_chat_conversation_id')->nullable()->index('ccca_conversation_id_index');
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete()->index('ccca_admin_user_id_index');
            $table->string('admin_email');
            $table->string('action', 80);
            $table->string('conversation_type', 40)->nullable();
            $table->json('affected_user_ids')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('accessed_at')->useCurrent()->index('ccca_accessed_at_index');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('result', 20)->index('ccca_result_index');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_conversation_access_audits');
    }
};
