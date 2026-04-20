<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('link_url')->nullable();
            $table->json('target_roles');
            $table->unsignedInteger('recipient_count');
            $table->timestamp('created_at')->nullable();

            $table->index(['created_at', 'actor_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_activity_logs');
    }
};
