<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->string('temperature', 10)->index();
            $table->unsignedInteger('ranking_position');
            $table->string('vehicle_salesforce_id')->nullable()->index();
            $table->string('vehicle_name');
            $table->unsignedInteger('total_leads')->default(0);
            $table->timestamp('synced_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_leaderboard_entries');
    }
};
