<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_tag_forum_thread', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('forum_thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->foreignId('forum_tag_id')->constrained('forum_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['forum_thread_id', 'forum_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_tag_forum_thread');
    }
};
