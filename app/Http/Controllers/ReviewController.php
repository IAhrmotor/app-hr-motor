<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileConnection;
use App\Models\GoogleBusinessProfileMonthlySnapshot;
use App\Models\GoogleBusinessProfileReview;
use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $connection = $this->getConnection();
        $reviews = $this->reviewsQuery($request)
            ->with('dealership')
            ->get();

        $dealershipSummaries = $this->buildDealershipSummaries($reviews);
        $latestUnanswered = $reviews
            ->filter(fn (GoogleBusinessProfileReview $review): bool => ! $review->isAnswered())
            ->take(8)
            ->values();
        $latestReviews = $reviews->take(18);
        $stats = $this->buildStats($reviews);

        return view('reviews.index', [
            'connection' => $connection,
            'dealershipSummaries' => $dealershipSummaries,
            'latestUnanswered' => $latestUnanswered,
            'latestReviews' => $latestReviews,
            'stats' => $stats,
            'dealerships' => Dealership::query()->orderBy('name')->get(),
            'filters' => $request->only(['dealership_id', 'status', 'sort', 'search']),
        ]);
    }

    public function show(Request $request, Dealership $dealership): View
    {
        $reviews = GoogleBusinessProfileReview::query()
            ->with('dealership')
            ->where('dealership_id', $dealership->id)
            ->when($request->filled('status'), function ($query) use ($request): void {
                if ($request->string('status')->toString() === 'unanswered') {
                    $query->whereNull('reply_comment');
                }
            })
            ->orderByDesc('review_created_at')
            ->orderByDesc('id')
            ->get();

        $snapshots = GoogleBusinessProfileMonthlySnapshot::query()
            ->where('dealership_id', $dealership->id)
            ->orderBy('snapshot_month')
            ->get();

        $stats = $this->buildStats($reviews);

        return view('reviews.show', [
            'dealership' => $dealership,
            'reviews' => $reviews,
            'snapshots' => $snapshots,
            'stats' => $stats,
        ]);
    }

    public function reports(): View
    {
        $snapshots = GoogleBusinessProfileMonthlySnapshot::query()
            ->with('dealership')
            ->orderBy('snapshot_month')
            ->orderBy('dealership_id')
            ->get();

        $grouped = $snapshots->groupBy(fn (GoogleBusinessProfileMonthlySnapshot $snapshot): string => $snapshot->snapshot_month?->format('Y-m') ?? 'sin-fecha');

        return view('reviews.reports', [
            'snapshots' => $snapshots,
            'groupedSnapshots' => $grouped,
        ]);
    }

    public function refresh(GoogleBusinessProfileReviewService $service): RedirectResponse
    {
        try {
            $service->sync();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Reseñas sincronizadas correctamente.');
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

            return back()->with('error', 'No se ha podido responder a la reseña.');
        }

        return back()->with('success', 'Respuesta publicada correctamente.');
    }

    private function getConnection(): ?GoogleBusinessProfileConnection
    {
        return app(GoogleBusinessProfileReviewService::class)->getConnection();
    }

    private function reviewsQuery(Request $request)
    {
        $query = GoogleBusinessProfileReview::query();

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
    private function buildDealershipSummaries(Collection $reviews): Collection
    {
        return Dealership::query()
            ->orderBy('name')
            ->get()
            ->map(function (Dealership $dealership) use ($reviews): array {
                $dealershipReviews = $reviews->where('dealership_id', $dealership->id);
                $monthlyReviews = $dealershipReviews->filter(
                    fn (GoogleBusinessProfileReview $review): bool => optional($review->review_created_at)->isCurrentMonth()
                );

                $snapshot = GoogleBusinessProfileMonthlySnapshot::query()
                    ->where('dealership_id', $dealership->id)
                    ->orderByDesc('snapshot_month')
                    ->first();

                return [
                    'dealership' => $dealership,
                    'total_reviews' => $dealershipReviews->count(),
                    'average_rating' => round((float) $dealershipReviews->avg('rating'), 2),
                    'monthly_reviews' => $monthlyReviews->count(),
                    'monthly_average_rating' => round((float) $monthlyReviews->avg('rating'), 2),
                    'unanswered_reviews' => $dealershipReviews->filter(fn (GoogleBusinessProfileReview $review): bool => ! $review->isAnswered())->count(),
                    'snapshot' => $snapshot,
                ];
            });
    }

    /**
     * @param  Collection<int, GoogleBusinessProfileReview>  $reviews
     * @return array<string, mixed>
     */
    private function buildStats(Collection $reviews): array
    {
        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => round((float) $reviews->avg('rating'), 2),
            'monthly_reviews' => $reviews->filter(fn (GoogleBusinessProfileReview $review): bool => optional($review->review_created_at)->isCurrentMonth())->count(),
            'monthly_average_rating' => round((float) $reviews->filter(fn (GoogleBusinessProfileReview $review): bool => optional($review->review_created_at)->isCurrentMonth())->avg('rating'), 2),
            'unanswered_reviews' => $reviews->filter(fn (GoogleBusinessProfileReview $review): bool => ! $review->isAnswered())->count(),
        ];
    }
}
