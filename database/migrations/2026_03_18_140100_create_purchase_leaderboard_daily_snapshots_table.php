<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_leaderboard_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->unsignedInteger('ranking_position');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('salesforce_user_id')->nullable()->index();
            $table->string('seller_name');
            $table->decimal('total_purchases', 15, 2)->default(0);
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_leaderboard_daily_snapshots');
    }
};
