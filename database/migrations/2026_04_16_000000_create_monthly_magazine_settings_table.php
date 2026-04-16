<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_magazine_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('tag_label', 80)->default('Abril');
            $table->string('pdf_path');
            $table->string('original_filename')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_magazine_settings');
    }
};
