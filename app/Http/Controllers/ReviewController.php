<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use App\Jobs\SyncGoogleBusinessProfileReviewsJob;
use App\Models\GoogleBusinessProfileConnection;
use App\Models\GoogleBusinessProfileMonthlySnapshot;
use App\Models\GoogleBusinessProfileReview;
use App\Services\GoogleBusinessProfileReviewService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $dealershipSort = $this->normalizeDealershipSort($request->string('dealership_sort')->toString());

        $payload = Cache::remember($this->reviewsIndexCacheKey(), now()->addMinutes(3), function (): array {
            return [
                'connection' => $this->getConnection(),
                'dealershipSummaries' => $this->buildDealershipSummaries(),
                'locationSummaries' => $this->buildLocationSummaries(),
                'stats' => $this->buildStats(),
            ];
        });

        $dealershipSummaries = $this->sortDealershipSummaries($payload['dealershipSummaries'], $dealershipSort);

        $latestUnanswered = $this->reviewTableExists()
            ? GoogleBusinessProfileReview::query()
                ->withoutSalamanca()
                ->with('dealership')
                ->whereHas('dealership', function ($query): void {
                    $query->withoutSalamanca();
                })
                ->whereNull('reply_comment')
                ->orderByDesc('review_created_at')
                ->orderByDesc('id')
                ->limit(8)
                ->get()
            : collect();

        $latestReviews = $this->reviewTableExists()
            ? GoogleBusinessProfileReview::query()
                ->withoutSalamanca()
                ->with('dealership')
                ->whereHas('dealership', function ($query): void {
                    $query->withoutSalamanca();
                })
                ->orderByDesc('review_created_at')
                ->orderByDesc('id')
                ->limit(18)
                ->get()
            : collect();

        return view('reviews.index', [
            'connection' => $payload['connection'],
            'dealershipSummaries' => $dealershipSummaries,
            'locationSummaries' => $payload['locationSummaries'],
            'latestUnanswered' => $latestUnanswered,
            'latestReviews' => $latestReviews,
            'stats' => $payload['stats'],
            'dealershipSort' => $dealershipSort,
        ]);
    }
    public function all(Request $request): View|JsonResponse
    {
        $reviewsQuery = $this->reviewTableExists()
            ? $this->reviewsQuery($request)->with('dealership')
            : null;

        $reviewsRatingDistribution = $this->buildReviewsRatingDistribution($reviewsQuery);

        $reviewsPaginator = $reviewsQuery
            ? (clone $reviewsQuery)
                ->paginate(10)
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

        $tableData = [
            'reviews' => $reviewsPaginator,
            'dealerships' => Dealership::query()
                ->withoutSalamanca()
                ->orderBy('name')
                ->get(),
            'filters' => $request->only(['dealership_id', 'status', 'sort', 'search', 'date_from', 'date_to']),
            'reviewsRatingDistribution' => $reviewsRatingDistribution,
        ];

        if ($request->boolean('ajax')) {
            try {
                return response()->json([
                    'html' => view('reviews.partials.activity-results', $tableData)->render(),
                ]);
            } catch (Throwable $exception) {
                report($exception);

                logger()->error('Google Business Profile reviews AJAX render failed.', [
                    'filters' => $tableData['filters'],
                    'message' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'No se ha podido filtrar la tabla de reseñas.',
                ], 500);
            }
        }

        return view('reviews.all', $tableData);
    }

    public function show(Request $request, Dealership $dealership): View
    {
        abort_unless($this->isVisibleDealership($dealership), 404);

        $reviewsQuery = GoogleBusinessProfileReview::query()
            ->withoutSalamanca()
            ->with('dealership')
            ->where('dealership_id', $dealership->id)
            ->when($request->filled('status'), function ($query) use ($request): void {
                if ($request->string('status')->toString() === 'unanswered') {
                    $query->whereNull('reply_comment');
                }
            });

        $reviews = $this->reviewTableExists()
            ? (clone $reviewsQuery)
                ->orderByDesc('review_created_at')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
            : collect();

        $stats = $this->reviewTableExists()
            ? $this->buildStatsFromReviewQuery($reviewsQuery)
            : $this->buildStats();

        $snapshots = $this->monthlySnapshotsTableExists()
            ? GoogleBusinessProfileMonthlySnapshot::query()
                ->where('dealership_id', $dealership->id)
                ->orderBy('snapshot_month')
                ->get()
            : collect();

        return view('reviews.show', [
            'dealership' => $dealership,
            'reviews' => $reviews,
            'snapshots' => $snapshots,
            'stats' => $stats,
        ]);
    }

    public function location(Request $request, string $locationKey): View
    {
        $locationName = $this->decodeLocationKey($locationKey);

        abort_unless(filled($locationName), 404);
        abort_unless($this->isVisibleLocationName($locationName), 404);

        $reviewsQuery = GoogleBusinessProfileReview::query()
                ->withoutSalamanca()
                ->with('dealership')
                ->where('location_name', $locationName)
                ->when($request->filled('status'), function ($query) use ($request): void {
                    if ($request->string('status')->toString() === 'unanswered') {
                        $query->whereNull('reply_comment');
                    }
                });

        $reviews = $this->reviewTableExists()
            ? (clone $reviewsQuery)
                ->orderByDesc('review_created_at')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
            : collect();

        $locationTitle = $reviews->first()?->location_title ?? $locationName;
        $location = (object) [
            'id' => null,
            'name' => $locationTitle,
            'google_business_profile_location_title' => $locationTitle,
            'google_business_profile_location_name' => $locationName,
        ];
        $snapshots = collect();
        $stats = $this->reviewTableExists()
            ? $this->buildStatsFromReviewQuery($reviewsQuery)
            : $this->buildStats();

        return view('reviews.show', [
            'dealership' => $location,
            'reviews' => $reviews,
            'snapshots' => $snapshots,
            'stats' => $stats,
        ]);
    }

    public function reports(): View
    {
        $snapshots = $this->monthlySnapshotsTableExists()
            ? GoogleBusinessProfileMonthlySnapshot::query()
                ->whereHas('dealership', function ($query): void {
                    $query->withoutSalamanca();
                })
                ->with('dealership')
                ->orderBy('snapshot_month')
                ->orderBy('dealership_id')
                ->get()
            : collect();

        $grouped = $snapshots->groupBy(fn (GoogleBusinessProfileMonthlySnapshot $snapshot): string => $snapshot->snapshot_month?->format('Y-m') ?? 'sin-fecha');

        return view('reviews.reports', [
            'snapshots' => $snapshots,
            'groupedSnapshots' => $grouped,
        ]);
    }
    public function refresh(Request $request, GoogleBusinessProfileReviewService $service, ?Dealership $dealership = null): RedirectResponse
    {
        try {
            SyncGoogleBusinessProfileReviewsJob::dispatch($dealership?->id);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage());
        }

        if ($dealership) {
            return back()->with('success', "Sincronizaci\u{F3}n en curso para {$dealership->name}. En breve se actualizar\u{E1}n sus rese\u{F1}as.");
        }

        return back()->with('success', "Sincronizaci\u{F3}n en curso. Se actualizar\u{E1}n las rese\u{F1}as en segundo plano.");
    }

    public function reply(Request $request, GoogleBusinessProfileReviewService $service, GoogleBusinessProfileReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $service->replyToReview($review->review_name, $validated['comment']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', "No se ha podido responder a la rese\u{F1}a.");
        }

        return back()->with('success', "Respuesta publicada correctamente.");
    }

    private function getConnection(): ?GoogleBusinessProfileConnection
    {
        try {
            return app(GoogleBusinessProfileReviewService::class)->getConnection();
        } catch (Throwable) {
            return null;
        }
    }

    private function reviewTableExists(): bool
    {
        try {
            return Schema::hasTable('google_business_profile_reviews');
        } catch (Throwable) {
            return false;
        }
    }

    private function monthlySnapshotsTableExists(): bool
    {
        try {
            return Schema::hasTable('google_business_profile_monthly_snapshots');
        } catch (Throwable) {
            return false;
        }
    }

    private function reviewsQuery(Request $request)
    {
        $query = GoogleBusinessProfileReview::query()->withoutSalamanca();

        $query->when($request->filled('dealership_id'), function ($builder) use ($request): void {
            $builder->where('dealership_id', $request->integer('dealership_id'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request): void {
            $search = trim((string) $request->string('search'));

            $builder->where(function ($subquery) use ($search): void {
                $subquery->where('reviewer_name', 'like', '%' . $search . '%')
                    ->orWhere('comment', 'like', '%' . $search . '%')
                    ->orWhere('reply_comment', 'like', '%' . $search . '%')
                    ->orWhere('location_title', 'like', '%' . $search . '%')
                    ->orWhere('location_name', 'like', '%' . $search . '%')
                    ->orWhereHas('dealership', function (EloquentBuilder $dealershipQuery) use ($search): void {
                        $dealershipQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        });

        $query->when($request->filled('status'), function ($builder) use ($request): void {
            $status = $request->string('status')->toString();

            if ($status === 'answered') {
                $builder->whereNotNull('reply_comment');
            }

            if ($status === 'unanswered') {
                $builder->whereNull('reply_comment');
            }
        });

        $query->when($request->filled('date_from'), function ($builder) use ($request): void {
            $builder->whereDate('review_created_at', '>=', Carbon::parse($request->string('date_from'))->toDateString());
        });

        $query->when($request->filled('date_to'), function ($builder) use ($request): void {
            $builder->whereDate('review_created_at', '<=', Carbon::parse($request->string('date_to'))->toDateString());
        });

        $sort = $request->string('sort')->toString();
        if ($sort === 'rating_asc') {
            $query->orderBy('rating');
        } elseif ($sort === 'rating_desc') {
            $query->orderByDesc('rating');
        } else {
            $query->orderByDesc('review_created_at')->orderByDesc('id');
        }

        return $query;
    }

    /**
     * @return array{total:int,red:int,orange:int,green:int,red_percent:float,orange_percent:float,green_percent:float}
     */
    private function buildReviewsRatingDistribution(?EloquentBuilder $query): array
    {
        if (! $query) {
            return [
                'total' => 0,
                'red' => 0,
                'orange' => 0,
                'green' => 0,
                'red_percent' => 0.0,
                'orange_percent' => 0.0,
                'green_percent' => 0.0,
            ];
        }

        $total = (clone $query)->count();
        $red = (clone $query)->whereBetween('rating', [1, 2])->count();
        $orange = (clone $query)->where('rating', 3)->count();
        $green = (clone $query)->whereBetween('rating', [4, 5])->count();

        return [
            'total' => $total,
            'red' => $red,
            'orange' => $orange,
            'green' => $green,
            'red_percent' => $total > 0 ? round(($red / $total) * 100, 1) : 0.0,
            'orange_percent' => $total > 0 ? round(($orange / $total) * 100, 1) : 0.0,
            'green_percent' => $total > 0 ? round(($green / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDealershipSummaries(): Collection
    {
        $dealerships = Dealership::query()
            ->withoutSalamanca()
            ->whereNotNull('google_business_profile_location_name')
            ->orderBy('name')
            ->get()
            ->groupBy('google_business_profile_location_name')
            ->map(fn (Collection $dealerships): ?Dealership => $dealerships->sortBy('id')->first())
            ->filter()
            ->values();

        return $dealerships->map(function (Dealership $dealership): array {
            $dealershipReviewsQuery = GoogleBusinessProfileReview::query()
                ->withoutSalamanca()
                ->where('dealership_id', $dealership->id);

                $monthStart = now()->startOfMonth();
                $monthEnd = now()->endOfMonth();
                $monthlyReviewsQuery = (clone $dealershipReviewsQuery)
                    ->whereBetween('review_created_at', [$monthStart, $monthEnd]);

                $snapshot = $this->monthlySnapshotsTableExists()
                    ? GoogleBusinessProfileMonthlySnapshot::query()
                        ->where('dealership_id', $dealership->id)
                        ->orderByDesc('snapshot_month')
                        ->first()
                    : null;

                return [
                    'dealership' => $dealership,
                    'total_reviews' => $dealershipReviewsQuery->count(),
                    'average_rating' => round((float) $dealershipReviewsQuery->avg('rating'), 2),
                    'monthly_reviews' => $monthlyReviewsQuery->count(),
                    'monthly_average_rating' => round((float) $monthlyReviewsQuery->avg('rating'), 2),
                    'unanswered_reviews' => (clone $dealershipReviewsQuery)->whereNull('reply_comment')->count(),
                    'snapshot' => $snapshot,
            ];
        });
    }

    private function sortDealershipSummaries(Collection $summaries, string $sort): Collection
    {
        $sorted = match ($sort) {
            'reviews_asc' => $summaries->sortBy(fn (array $summary): int => (int) ($summary['total_reviews'] ?? 0)),
            'reviews_desc' => $summaries->sortByDesc(fn (array $summary): int => (int) ($summary['total_reviews'] ?? 0)),
            'rating_asc' => $summaries->sortBy(fn (array $summary): float => (float) ($summary['average_rating'] ?? 0)),
            'rating_desc' => $summaries->sortByDesc(fn (array $summary): float => (float) ($summary['average_rating'] ?? 0)),
            'monthly_rating_asc' => $summaries->sortBy(fn (array $summary): float => (float) ($summary['monthly_average_rating'] ?? 0)),
            'monthly_rating_desc' => $summaries->sortByDesc(fn (array $summary): float => (float) ($summary['monthly_average_rating'] ?? 0)),
            default => $summaries->sortBy(function (array $summary): string {
                return Str::ascii(Str::lower((string) data_get($summary, 'dealership.name', '')));
            }),
        };

        return $sorted->values();
    }

    private function normalizeDealershipSort(string $sort): string
    {
        return in_array($sort, [
            'alpha',
            'reviews_asc',
            'reviews_desc',
            'rating_asc',
            'rating_desc',
            'monthly_rating_asc',
            'monthly_rating_desc',
        ], true) ? $sort : 'alpha';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLocationSummaries(): Collection
    {
        if (! $this->reviewTableExists()) {
            return collect();
        }

        $linkedLocationNames = Dealership::query()
            ->withoutSalamanca()
            ->whereNotNull('google_business_profile_location_name')
            ->pluck('google_business_profile_location_name')
            ->filter()
            ->map(fn (string $value): string => $this->canonicalGoogleLocationKey($value))
            ->unique()
            ->values()
            ->all();

        $locationNames = GoogleBusinessProfileReview::query()
            ->withoutSalamanca()
            ->whereNull('dealership_id')
            ->whereNotNull('location_name')
            ->distinct()
            ->pluck('location_name')
            ->filter()
            ->reject(fn (string $locationName): bool => in_array($this->canonicalGoogleLocationKey($locationName), $linkedLocationNames, true))
            ->values();

        $locationSummaries = $locationNames->map(function (string $locationName): array {
            $locationReviewsQuery = GoogleBusinessProfileReview::query()
                ->withoutSalamanca()
                ->whereNull('dealership_id')
                ->where('location_name', $locationName);

            $locationTitle = (clone $locationReviewsQuery)
                ->orderByDesc('review_created_at')
                ->orderByDesc('id')
                ->value('location_title') ?? $locationName;
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();
            $monthlyReviewsQuery = (clone $locationReviewsQuery)
                ->whereBetween('review_created_at', [$monthStart, $monthEnd]);

            return [
                'key' => $this->encodeLocationKey($locationName),
                'location_name' => $locationName,
                'location_title' => $locationTitle,
                'total_reviews' => $locationReviewsQuery->count(),
                'average_rating' => round((float) $locationReviewsQuery->avg('rating'), 2),
                'monthly_reviews' => $monthlyReviewsQuery->count(),
                'monthly_average_rating' => round((float) $monthlyReviewsQuery->avg('rating'), 2),
                'unanswered_reviews' => (clone $locationReviewsQuery)->whereNull('reply_comment')->count(),
            ];
            })
            ->values();

        return $locationSummaries->sortByDesc('total_reviews')->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(?Collection $reviews = null): array
    {
        if ($reviews !== null) {
            return [
                'total_reviews' => $reviews->count(),
                'average_rating' => round((float) $reviews->avg('rating'), 2),
                'monthly_reviews' => $reviews->filter(fn (GoogleBusinessProfileReview $review): bool => optional($review->review_created_at)->isCurrentMonth())->count(),
                'monthly_average_rating' => round((float) $reviews->filter(fn (GoogleBusinessProfileReview $review): bool => optional($review->review_created_at)->isCurrentMonth())->avg('rating'), 2),
                'unanswered_reviews' => $reviews->filter(fn (GoogleBusinessProfileReview $review): bool => ! $review->isAnswered())->count(),
            ];
        }

        if (! $this->reviewTableExists()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'monthly_reviews' => 0,
                'monthly_average_rating' => 0,
                'unanswered_reviews' => 0,
            ];
        }

        $query = GoogleBusinessProfileReview::query()->withoutSalamanca();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyQuery = GoogleBusinessProfileReview::query()
            ->withoutSalamanca()
            ->whereBetween('review_created_at', [$monthStart, $monthEnd]);

        return [
            'total_reviews' => $query->count(),
            'average_rating' => round((float) $query->avg('rating'), 2),
            'monthly_reviews' => $monthlyQuery->count(),
            'monthly_average_rating' => round((float) $monthlyQuery->avg('rating'), 2),
            'unanswered_reviews' => (clone $query)->whereNull('reply_comment')->count(),
        ];
    }

    /**
     * @param  EloquentBuilder<GoogleBusinessProfileReview>  $query
     * @return array<string, mixed>
     */
    private function buildStatsFromReviewQuery(EloquentBuilder $query): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyQuery = (clone $query)->whereBetween('review_created_at', [$monthStart, $monthEnd]);

        return [
            'total_reviews' => (clone $query)->count(),
            'average_rating' => round((float) (clone $query)->avg('rating'), 2),
            'monthly_reviews' => $monthlyQuery->count(),
            'monthly_average_rating' => round((float) $monthlyQuery->avg('rating'), 2),
            'unanswered_reviews' => (clone $query)->whereNull('reply_comment')->count(),
        ];
    }

    private function encodeLocationKey(string $locationName): string
    {
        return rtrim(strtr(base64_encode($locationName), '+/', '-_'), '=');
    }

    private function decodeLocationKey(string $locationKey): ?string
    {
        $decoded = base64_decode(strtr($locationKey, '-_', '+/'), true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }

    private function isVisibleDealership(Dealership $dealership): bool
    {
        $normalizedValues = [
            $this->normalizeTextForFilter($dealership->name),
            $this->normalizeTextForFilter($dealership->google_business_profile_location_name),
            $this->normalizeTextForFilter($dealership->google_business_profile_location_title),
        ];

        foreach ($normalizedValues as $normalizedValue) {
            if ($normalizedValue !== '' && str_contains($normalizedValue, 'salamanca')) {
                return false;
            }
        }

        return true;
    }

    private function isVisibleLocationName(string $locationName): bool
    {
        return ! str_contains($this->normalizeTextForFilter($locationName), 'salamanca');
    }

    private function normalizeTextForFilter(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function canonicalGoogleLocationKey(?string $locationName): string
    {
        $locationName = trim((string) $locationName);

        if ($locationName === '') {
            return '';
        }

        if (preg_match('#(?:accounts/[^/]+/)?locations/([^/]+)$#i', $locationName, $matches) === 1) {
            return 'locations/' . strtolower($matches[1]);
        }

        return strtolower($locationName);
    }

    private function reviewsIndexCacheKey(): string
    {
        return 'reviews.index.dashboard.v1';
    }

}




