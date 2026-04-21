<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['threecx_extension']);
            $table->dropColumn('threecx_extension');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropUnique(['threecx_extension']);
            $table->dropColumn('threecx_extension');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('threecx_extension')->nullable()->unique()->after('phone');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('threecx_extension')->unique()->after('phone');
        });
    }
};
