<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->foreignId('target_bulletin_post_id')->nullable()->constrained('bulletin_posts')->nullOnDelete();
            $table->string('target_name');
            $table->string('target_reference')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at'], 'bulletin_activity_logs_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_activity_logs');
    }
};
