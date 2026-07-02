<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_tickets', function (Blueprint $table): void {
            $table->foreignId('ticket_tool_id')
                ->nullable()
                ->after('user_id')
                ->constrained('ticket_tools')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('it_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ticket_tool_id');
        });
    }
};
