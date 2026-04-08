<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->default('#1d4ed8');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_tags');
    }
};
