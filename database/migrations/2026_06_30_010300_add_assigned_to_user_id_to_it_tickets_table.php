<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_tickets', function (Blueprint $table): void {
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('it_tickets', function (Blueprint $table): void {
            $table->dropIndex(['assigned_to_user_id', 'status']);
            $table->dropConstrainedForeignId('assigned_to_user_id');
        });
    }
};
