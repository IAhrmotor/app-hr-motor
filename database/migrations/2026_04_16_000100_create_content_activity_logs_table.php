<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('content_type');
            $table->string('action');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->string('target_name');
            $table->string('target_reference')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_type', 'action', 'created_at'], 'content_activity_logs_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_activity_logs');
    }
};
