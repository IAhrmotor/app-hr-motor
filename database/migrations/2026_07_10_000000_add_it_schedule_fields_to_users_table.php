<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('it_monday_start', 5)->nullable()->after('enreach_extension');
            $table->string('it_monday_end', 5)->nullable()->after('it_monday_start');
            $table->string('it_tuesday_start', 5)->nullable()->after('it_monday_end');
            $table->string('it_tuesday_end', 5)->nullable()->after('it_tuesday_start');
            $table->string('it_wednesday_start', 5)->nullable()->after('it_tuesday_end');
            $table->string('it_wednesday_end', 5)->nullable()->after('it_wednesday_start');
            $table->string('it_thursday_start', 5)->nullable()->after('it_wednesday_end');
            $table->string('it_thursday_end', 5)->nullable()->after('it_thursday_start');
            $table->string('it_friday_start', 5)->nullable()->after('it_thursday_end');
            $table->string('it_friday_end', 5)->nullable()->after('it_friday_start');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'it_monday_start',
                'it_monday_end',
                'it_tuesday_start',
                'it_tuesday_end',
                'it_wednesday_start',
                'it_wednesday_end',
                'it_thursday_start',
                'it_thursday_end',
                'it_friday_start',
                'it_friday_end',
            ]);
        });
    }
};
