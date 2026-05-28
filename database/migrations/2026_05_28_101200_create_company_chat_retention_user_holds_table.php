<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_chat_retention_user_holds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('retention_hold')->default(true)->index();
            $table->text('retention_hold_reason');
            $table->timestamp('retention_hold_created_at')->nullable();
            $table->foreignId('retention_hold_created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('retention_hold_expires_at')->nullable()->index();
            $table->timestamp('retention_hold_deactivated_at')->nullable();
            $table->foreignId('retention_hold_deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('retention_hold_deactivation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'retention_hold']);
            $table->index(['retention_hold_created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_retention_user_holds');
    }
};
