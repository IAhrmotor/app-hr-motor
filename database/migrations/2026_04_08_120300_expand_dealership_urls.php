<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE dealerships MODIFY google_maps_url VARCHAR(2048) NULL');
        DB::statement('ALTER TABLE dealerships MODIFY reviews_url VARCHAR(2048) NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE dealerships MODIFY google_maps_url VARCHAR(255) NULL');
        DB::statement('ALTER TABLE dealerships MODIFY reviews_url VARCHAR(255) NULL');
    }
};
