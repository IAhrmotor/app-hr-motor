<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_retention_logs', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('executed_at')->index();
            $table->timestamp('cutoff')->index();
            $table->string('status', 20)->index();
            $table->unsignedInteger('deleted_count')->default(0);
            $table->json('affected_user_ids')->nullable();
            $table->json('affected_users')->nullable();
            $table->unsignedInteger('error_count')->default(0);
            $table->text('error_summary')->nullable();
            $table->json('errors')->nullable();
            $table->string('source', 50)->default('cron');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_retention_logs');
    }
};
