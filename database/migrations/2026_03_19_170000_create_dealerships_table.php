<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealerships', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('salesforce_id')->nullable()->unique();
            $table->string('image_path')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->string('reviews_url')->nullable();
            $table->string('google_business_profile_location_name')->nullable()->unique();
            $table->string('google_business_profile_location_title')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealerships');
    }
};
