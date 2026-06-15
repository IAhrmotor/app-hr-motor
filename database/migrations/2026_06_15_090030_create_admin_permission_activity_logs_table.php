<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_permission_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 80);
            $table->string('result', 32)->default('success');
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('target_type', 32)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name')->nullable();
            $table->string('permission_key', 80)->nullable();
            $table->string('scope', 32)->nullable();
            $table->json('changes')->nullable();
            $table->string('reason', 2000)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permission_activity_logs');
    }
};
