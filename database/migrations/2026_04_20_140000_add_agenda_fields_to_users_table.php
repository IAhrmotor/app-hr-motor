<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('linkedin_url');
            $table->string('threecx_extension')->nullable()->unique()->after('phone');
            $table->string('enreach_phone')->nullable()->after('threecx_extension');
            $table->string('enreach_extension')->nullable()->unique()->after('enreach_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['threecx_extension']);
            $table->dropUnique(['enreach_extension']);
            $table->dropColumn([
                'phone',
                'threecx_extension',
                'enreach_phone',
                'enreach_extension',
            ]);
        });
    }
};
