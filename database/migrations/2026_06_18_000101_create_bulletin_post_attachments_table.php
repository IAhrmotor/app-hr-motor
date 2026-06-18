<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_post_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulletin_post_id')->constrained('bulletin_posts')->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bulletin_post_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_post_attachments');
    }
};
