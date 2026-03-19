<?php

use App\Models\Dealership;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('dealership_id')->nullable()->after('dealership')->constrained('dealerships')->nullOnDelete();
        });

        $dealershipNames = DB::table('users')
            ->whereNotNull('dealership')
            ->where('dealership', '!=', '')
            ->distinct()
            ->orderBy('dealership')
            ->pluck('dealership');

        foreach ($dealershipNames as $dealershipName) {
            $dealership = Dealership::query()->firstOrCreate([
                'name' => $dealershipName,
            ]);

            DB::table('users')
                ->where('dealership', $dealershipName)
                ->update(['dealership_id' => $dealership->id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealership_id');
        });
    }
};
