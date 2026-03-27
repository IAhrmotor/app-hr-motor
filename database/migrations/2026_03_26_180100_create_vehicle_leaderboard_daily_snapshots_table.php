<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_leaderboard_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->string('temperature', 10)->index();
            $table->unsignedInteger('ranking_position');
            $table->string('vehicle_salesforce_id')->nullable()->index();
            $table->string('vehicle_name');
            $table->unsignedInteger('total_leads')->default(0);
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_leaderboard_daily_snapshots');
    }
};
