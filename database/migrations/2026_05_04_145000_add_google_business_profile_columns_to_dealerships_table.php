<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealerships', function (Blueprint $table): void {
            if (! Schema::hasColumn('dealerships', 'google_business_profile_location_name')) {
                $table->string('google_business_profile_location_name')->nullable()->unique()->after('reviews_url');
            }

            if (! Schema::hasColumn('dealerships', 'google_business_profile_location_title')) {
                $table->string('google_business_profile_location_title')->nullable()->after('google_business_profile_location_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealerships', function (Blueprint $table): void {
            if (Schema::hasColumn('dealerships', 'google_business_profile_location_title')) {
                $table->dropColumn('google_business_profile_location_title');
            }

            if (Schema::hasColumn('dealerships', 'google_business_profile_location_name')) {
                $table->dropUnique('dealerships_google_business_profile_location_name_unique');
                $table->dropColumn('google_business_profile_location_name');
            }
        });
    }
};
