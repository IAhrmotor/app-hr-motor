<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('it_tickets')
            ->whereNull('assigned_to_user_id')
            ->update([
                'status' => 'new',
            ]);
    }

    public function down(): void
    {
        //
    }
};
