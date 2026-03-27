<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_leaderboard_entries', function (Blueprint $table) {
            $table->string('vehicle_image_url', 2048)->nullable()->after('vehicle_name');
        });

        Schema::table('vehicle_leaderboard_daily_snapshots', function (Blueprint $table) {
            $table->string('vehicle_image_url', 2048)->nullable()->after('vehicle_name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_leaderboard_daily_snapshots', function (Blueprint $table) {
            $table->dropColumn('vehicle_image_url');
        });

        Schema::table('vehicle_leaderboard_entries', function (Blueprint $table) {
            $table->dropColumn('vehicle_image_url');
        });
    }
};
