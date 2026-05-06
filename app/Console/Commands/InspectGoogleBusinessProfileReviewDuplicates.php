<?php

namespace App\Console\Commands;

use App\Models\GoogleBusinessProfileReview;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InspectGoogleBusinessProfileReviewDuplicates extends Command
{
    protected $signature = 'google-business-profile:inspect-duplicate-reviews {--limit=25 : Maximum number of duplicate groups to display}';

    protected $description = 'Muestra grupos de reseñas duplicadas para poder inspeccionarlas antes de eliminarlas.';

    public function handle(): int
    {
        if (! $this->reviewTableExists()) {
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
                'reply_updated_at',
                'review_created_at',
                'review_updated_at',
                'synced_at',
                'updated_at',
            ])
            ->orderByDesc('synced_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $byReviewName = $rows
            ->filter(fn (GoogleBusinessProfileReview $review): bool => filled($review->review_name))
            ->groupBy('review_name')
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->sortByDesc(fn (Collection $group): int => $group->count())
            ->values();

        $byFingerprint = $rows
            ->groupBy(fn (GoogleBusinessProfileReview $review): string => $service->buildDuplicateReviewKey($review))
            ->filter(fn (Collection $group, string $fingerprint): bool => $fingerprint !== '' && $group->count() > 1)
            ->sortByDesc(fn (Collection $group): int => $group->count())
            ->values();

        $this->info(sprintf('Filas analizadas: %d', $rows->count()));
        $this->info(sprintf('Grupos duplicados por review_name: %d', $byReviewName->count()));
        $this->info(sprintf('Grupos duplicados por huella visible: %d', $byFingerprint->count()));

        $this->line('');
        $this->warn('Duplicados por review_name');
        $this->renderGroups($byReviewName->take($limit));

        $this->line('');
        $this->warn('Duplicados por huella visible');
        $this->renderGroups($byFingerprint->take($limit));

        return self::SUCCESS;
    }

    private function renderGroups(Collection $groups): void
    {
        if ($groups->isEmpty()) {
            $this->line('No hay grupos duplicados que mostrar.');

            return;
        }

        foreach ($groups as $index => $group) {
            /** @var Collection<int, GoogleBusinessProfileReview> $group */
            $first = $group->first();

            $this->line(sprintf(
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
    }

    private function normalizeFingerprintValue(mixed $value): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = Str::lower($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function reviewTableExists(): bool
    {
        return Schema::hasTable('google_business_profile_reviews');
    }
}
