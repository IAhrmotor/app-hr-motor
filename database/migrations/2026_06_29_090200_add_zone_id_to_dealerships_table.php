<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealerships', function (Blueprint $table): void {
            $table->foreignId('zone_id')->nullable()->after('salesforce_id')->constrained('zones')->nullOnDelete();
            $table->index(['zone_id', 'name'], 'dealerships_zone_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dealerships', function (Blueprint $table): void {
            $table->dropIndex('dealerships_zone_name_idx');
            $table->dropConstrainedForeignId('zone_id');
        });
    }
};
