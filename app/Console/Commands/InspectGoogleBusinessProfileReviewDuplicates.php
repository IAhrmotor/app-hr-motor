<?php

namespace App\Console\Commands;

use App\Models\GoogleBusinessProfileReview;
use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InspectGoogleBusinessProfileReviewDuplicates extends Command
{
    protected $signature = 'google-business-profile:inspect-duplicate-reviews {--limit=25 : Maximum number of duplicate groups to display}';

    protected $description = 'Muestra grupos de reseñas duplicadas para poder inspeccionarlas antes de eliminarlas.';

    public function handle(GoogleBusinessProfileReviewService $service): int
    {
        $this->line('Inspeccionando grupos duplicados de Google Business Profile...');

        if (! Schema::hasTable('google_business_profile_reviews')) {
            $this->info('La tabla de reseñas no existe.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $rowsAnalyzed = 0;
        $seen = [];
        $groups = [];

        $query = GoogleBusinessProfileReview::query()
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
                'updated_at',
            ])
            ->orderByDesc('synced_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        foreach ($query->cursor() as $review) {
            $rowsAnalyzed++;
            $key = $service->buildDuplicateReviewKey($review);

            if ($key === '') {
                continue;
            }

            $summary = [
                'id' => $review->id,
                'review_name' => $review->review_name ?? '-',
                'location_name' => $review->location_name ?? '-',
                'location_title' => $review->location_title ?? '-',
                'reviewer_name' => $review->reviewer_name ?? '-',
                'rating' => $review->rating ?? 0,
                'review_created_at' => $review->review_created_at?->format('d/m/Y H:i') ?? '-',
            ];

            if (! isset($seen[$key])) {
                $seen[$key] = $summary;
                continue;
            }

            if (! isset($groups[$key])) {
                $groups[$key] = [$seen[$key]];
            }

            $groups[$key][] = $summary;
        }

        $groups = array_values(array_filter($groups, fn (array $group): bool => count($group) > 1));

        $this->info(sprintf('Filas analizadas: %d', $rowsAnalyzed));
        $this->info(sprintf('Grupos duplicados por huella canónica: %d', count($groups)));
        $this->line('');

        if ($groups === []) {
            $this->info('No hay grupos duplicados canónicos.');
            $this->line('');
            $this->info('Inspección completada.');

            return self::SUCCESS;
        }

        foreach (array_slice($groups, 0, $limit) as $index => $group) {
            $first = $group[0];

            $this->warn(sprintf(
                '%d) %d filas | delegación: %s | cliente: %s | rating: %s | fecha: %s',
                $index + 1,
                count($group),
                $first['location_title'] !== '-' ? $first['location_title'] : 'Sin asignar',
                $first['reviewer_name'] !== '-' ? $first['reviewer_name'] : 'Anónimo',
                $first['rating'],
                $first['review_created_at']
            ));

            foreach ($group as $review) {
                $this->line(sprintf(
                    '   - ID %d | review_name=%s | location_name=%s | reviewer=%s | rating=%s | fecha=%s',
                    $review['id'],
                    $review['review_name'],
                    $review['location_name'],
                    $review['reviewer_name'],
                    $review['rating'],
                    $review['review_created_at']
                ));
            }

            $this->line('');
        }

        $this->line('');
        $this->info('Inspección completada.');

        return self::SUCCESS;
    }
}
