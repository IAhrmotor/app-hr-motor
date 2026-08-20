<?php

use App\Services\MonthlyMagazineActivityLogWriter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(MonthlyMagazineActivityLogWriter::class)->cleanupHistoricalRecords();
    }

    public function down(): void
    {
        // No se restaura el ruido filtrado.
    }
};
