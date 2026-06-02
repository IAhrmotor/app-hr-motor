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
            $table->boolean('retention_hold')->default(true);
            $table->text('retention_hold_reason');
            $table->timestamp('retention_hold_created_at')->nullable();
            $table->foreignId('retention_hold_created_by')->nullable();
            $table->foreign('retention_hold_created_by', 'uch_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamp('retention_hold_expires_at')->nullable();
            $table->timestamp('retention_hold_deactivated_at')->nullable();
            $table->foreignId('retention_hold_deactivated_by')->nullable();
            $table->foreign('retention_hold_deactivated_by', 'uch_deactivated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->text('retention_hold_deactivation_reason')->nullable();
            $table->timestamps();

            $table->index('retention_hold', 'uch_retention_idx');
            $table->index('retention_hold_expires_at', 'uch_expires_at_idx');
            $table->index(['user_id', 'retention_hold'], 'uch_user_retention_idx');
            $table->index(['retention_hold_created_by', 'created_at'], 'uch_created_by_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_chat_retention_user_holds');
    }
};
