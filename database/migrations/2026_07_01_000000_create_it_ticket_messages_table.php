<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('it_ticket_id')->constrained('it_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['it_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_ticket_messages');
    }
};
