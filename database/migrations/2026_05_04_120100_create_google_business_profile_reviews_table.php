<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('google_business_profile_reviews')) {
            return;
        }

        Schema::create('google_business_profile_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealership_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_name')->nullable()->index();
            $table->string('location_title')->nullable();
            $table->string('review_name')->unique();
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_photo_url')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->string('reply_name')->nullable();
            $table->text('reply_comment')->nullable();
            $table->timestamp('reply_updated_at')->nullable();
            $table->timestamp('review_created_at')->nullable();
            $table->timestamp('review_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_business_profile_reviews');
    }
};
