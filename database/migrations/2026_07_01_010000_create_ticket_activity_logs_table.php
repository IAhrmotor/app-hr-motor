<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('it_ticket_id')->constrained('it_tickets')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email')->nullable();
            $table->string('event', 50)->index();
            $table->string('title');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['it_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_activity_logs');
    }
};
