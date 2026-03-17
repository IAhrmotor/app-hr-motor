<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ranking_position');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('salesforce_user_id')->nullable()->index();
            $table->string('seller_name');
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_leaderboard_entries');
    }
};
