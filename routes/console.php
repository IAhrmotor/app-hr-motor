<?php

use App\Models\GoogleBusinessProfileReview;
use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('google-business-profile:debug-duplicate-reviews {--limit=20}', function (GoogleBusinessProfileReviewService $service) {
    if (! Schema::hasTable('google_business_profile_reviews')) {
        $this->info('La tabla de reseñas no existe.');

        return self::SUCCESS;
    }

    $limit = max(1, (int) $this->option('limit'));

    $rows = GoogleBusinessProfileReview::query()
        ->withoutSalamanca()
        ->select([
            'id',
            'dealership_id',
            'location_name',
            'location_title',
            'review_name',
            'reviewer_name',
            'rating',
            'comment',
            'reply_comment',
            'review_created_at',
            'synced_at',
        ])
        ->orderByDesc('synced_at')
        ->orderByDesc('id')
        ->get();

    $byCanonical = $rows
        ->groupBy(fn (GoogleBusinessProfileReview $review): string => implode('|', [
            (string) $review->dealership_id,
            $service->canonicalGoogleLocationKey($review->location_name),
            $service->canonicalGoogleReviewKey($review->review_name),
            Str::lower(trim((string) $review->location_title)),
            Str::lower(trim((string) $review->reviewer_name)),
            (string) $review->rating,
            (string) $review->review_created_at?->format('Y-m-d H:i:s'),
            Str::lower(trim((string) $review->comment)),
            Str::lower(trim((string) $review->reply_comment)),
        ]))
        ->filter(fn (Collection $group): bool => $group->count() > 1)
        ->sortByDesc(fn (Collection $group): int => $group->count())
        ->values();

    $this->info(sprintf('Filas analizadas: %d', $rows->count()));
    $this->info(sprintf('Grupos duplicados por huella canónica: %d', $byCanonical->count()));
    $this->line('');

    foreach ($byCanonical->take($limit) as $index => $group) {
        /** @var Collection<int, GoogleBusinessProfileReview> $group */
        $first = $group->first();

        $this->warn(sprintf(
            '%d) %d filas | delegación: %s | cliente: %s | rating: %s | fecha: %s',
            $index + 1,
            $group->count(),
            $first?->dealership?->name ?? $first?->location_title ?? 'Sin asignar',
            $first?->reviewer_name ?? 'Anónimo',
            $first?->rating ?? 0,
            $first?->review_created_at?->format('d/m/Y H:i') ?? 'sin fecha'
        ));

        foreach ($group as $review) {
            $this->line(sprintf(
                '   - ID %d | review_name=%s | location_name=%s | reviewer=%s | rating=%s | fecha=%s',
                $review->id,
                $review->review_name ?? '-',
                $review->location_name ?? '-',
                $review->reviewer_name ?? '-',
                $review->rating ?? 0,
                $review->review_created_at?->format('d/m/Y H:i') ?? '-'
            ));
        }

        $this->line('');
    }

    if ($byCanonical->isEmpty()) {
        $this->info('No hay grupos duplicados canónicos.');
    }

    return self::SUCCESS;
})->purpose('Muestra duplicados canónicos de reseñas de Google Business Profile.');

Artisan::command('google-business-profile:cleanup-duplicate-reviews', function (GoogleBusinessProfileReviewService $service) {
    $deletedCount = $service->dedupeDuplicateReviewRows();

    $this->info(sprintf('Limpieza completada. Filas duplicadas eliminadas: %d.', $deletedCount));

    return self::SUCCESS;
})->purpose('Elimina las reseñas duplicadas de Google Business Profile.');

Schedule::command('salesforce:sync-leaderboard')->everyTenMinutes();
Schedule::command('google-business-profile:sync-reviews')->everyFiveMinutes()->withoutOverlapping();
