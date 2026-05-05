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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $connection = $this->getConnection();

        $dealershipSummaries = $this->buildDealershipSummaries();
        $locationSummaries = $this->buildLocationSummaries();
        $latestUnanswered = $this->reviewTableExists()
            ? GoogleBusinessProfileReview::query()
                ->withoutSalamanca()
                ->with('dealership')
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
                ->orderByDesc('review_created_at')
                ->orderByDesc('id')
                ->limit(18)
                ->get()
            : collect();
        $stats = $this->buildStats();
        $dealerships = Dealership::query()
            ->withoutSalamanca()
            ->orderBy('name')
            ->get();

        return view('reviews.index', [
            'connection' => $connection,
            'dealershipSummaries' => $dealershipSummaries,
            'locationSummaries' => $locationSummaries,
            'latestUnanswered' => $latestUnanswered,
            'latestReviews' => $latestReviews,
            'stats' => $stats,
            'dealerships' => $dealerships,
            'filters' => $request->only(['dealership_id', 'status', 'sort', 'search']),
        ]);
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
            return back()->with('success', 'Sincronización en curso para ' . $dealership->name . '. En breve se actualizarán sus reseñas.');
        }

        return back()->with('success', 'Sincronización en curso. Se actualizarán las reseñas en segundo plano.');
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

            return back()->with('error', 'No se ha podido responder a la reseÃƒÂ±a.');
        }

        return back()->with('success', 'Respuesta publicada correctamente.');
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
                    ->orWhere('location_title', 'like', '%' . $search . '%');
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

}



