<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealership_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->foreignId('target_dealership_id')->nullable()->constrained('dealerships')->nullOnDelete();
            $table->string('target_name');
            $table->string('target_salesforce_id')->nullable();
            $table->string('target_phone')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealership_activity_logs');
    }
};
