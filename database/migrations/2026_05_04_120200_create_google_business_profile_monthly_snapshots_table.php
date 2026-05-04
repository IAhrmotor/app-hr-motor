<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('google_business_profile_monthly_snapshots')) {
            return;
        }

        Schema::create('google_business_profile_monthly_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealership_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_month');
            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('average_rating', 4, 2)->nullable();
            $table->unsignedInteger('monthly_reviews')->default(0);
            $table->decimal('monthly_average_rating', 4, 2)->nullable();
            $table->unsignedInteger('unanswered_reviews')->default(0);
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->unique(['dealership_id', 'snapshot_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_business_profile_monthly_snapshots');
    }
};
