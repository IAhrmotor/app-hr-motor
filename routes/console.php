<?php

use App\Models\GoogleBusinessProfileReview;
use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('google-business-profile:debug-duplicate-reviews {--limit=20}', function (GoogleBusinessProfileReviewService $service) {
    $this->line('Inspeccionando grupos duplicados de Google Business Profile...');

    Artisan::call('google-business-profile:inspect-duplicate-reviews', [
        '--limit' => $this->option('limit'),
    ], $this->output);

    return self::SUCCESS;
})->purpose('Muestra duplicados canonicos de reseñas de Google Business Profile.');

Artisan::command('google-business-profile:cleanup-duplicate-reviews', function (GoogleBusinessProfileReviewService $service) {
    $this->line('Iniciando limpieza de duplicados de Google Business Profile...');

    $deletedCount = $service->dedupeDuplicateReviewRows();

    $this->info(sprintf('Limpieza completada. Filas duplicadas eliminadas: %d.', $deletedCount));

    return self::SUCCESS;
})->purpose('Elimina las reseñas duplicadas de Google Business Profile.');

Schedule::command('salesforce:sync-leaderboard')->everyTenMinutes();
Schedule::command('google-business-profile:sync-reviews')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('chat:purge-expired-messages')
    ->dailyAt('05:30')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
