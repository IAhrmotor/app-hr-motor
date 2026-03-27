<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_leaderboard_entries', function (Blueprint $table) {
            $table->string('vehicle_commercial_name')->nullable()->after('vehicle_name');
            $table->string('vehicle_plate', 50)->nullable()->after('vehicle_commercial_name');
        });

        Schema::table('vehicle_leaderboard_daily_snapshots', function (Blueprint $table) {
            $table->string('vehicle_commercial_name')->nullable()->after('vehicle_name');
            $table->string('vehicle_plate', 50)->nullable()->after('vehicle_commercial_name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_leaderboard_daily_snapshots', function (Blueprint $table) {
            $table->dropColumn(['vehicle_commercial_name', 'vehicle_plate']);
        });

        Schema::table('vehicle_leaderboard_entries', function (Blueprint $table) {
            $table->dropColumn(['vehicle_commercial_name', 'vehicle_plate']);
        });
    }
};
