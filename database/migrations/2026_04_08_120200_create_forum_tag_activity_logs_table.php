<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_tag_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->foreignId('target_forum_tag_id')->nullable()->constrained('forum_tags')->nullOnDelete();
            $table->string('target_name');
            $table->string('target_color', 7)->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_tag_activity_logs');
    }
};
