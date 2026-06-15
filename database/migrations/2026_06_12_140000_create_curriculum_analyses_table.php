<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('job_title');
            $table->string('location')->nullable();
            $table->longText('offer_description');
            $table->json('mandatory_requirements');
            $table->json('valuable_requirements');
            $table->unsignedTinyInteger('top_candidates_count')->default(5);
            $table->string('status')->default('queued');
            $table->unsignedSmallInteger('total_candidates')->default(0);
            $table->unsignedSmallInteger('processed_candidates')->default(0);
            $table->json('report_data')->nullable();
            $table->text('error_message')->nullable();
            $table->string('openai_model')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_analyses');
    }
};
