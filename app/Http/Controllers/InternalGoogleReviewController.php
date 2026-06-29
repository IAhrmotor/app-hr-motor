<?php

namespace App\Http\Controllers;

use App\Http\Requests\InternalGoogleReviewCountRequest;
use App\Models\GoogleBusinessProfileReview;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class InternalGoogleReviewController extends Controller
{
    public function count(InternalGoogleReviewCountRequest $request): JsonResponse
    {
        $summary = $this->buildMonthlyLocationReviewSummary($request);

        return response()->json([
            'month' => $summary['month'],
            'location' => $summary['location'],
            'reviews_count' => $summary['reviews_count'],
            'average_rating' => $summary['average_rating'],
        ]);
    }

    public function reviews(InternalGoogleReviewCountRequest $request): JsonResponse
    {
        $summary = $this->buildMonthlyLocationReviewSummary($request);

        return response()->json([
            'month' => $summary['month'],
            'location' => $summary['location'],
            'reviews_count' => $summary['reviews_count'],
            'average_rating' => $summary['average_rating'],
            'reviews' => $summary['reviews']->map(function (GoogleBusinessProfileReview $review): array {
                return [
                    'id' => $review->id,
                    'review_name' => $review->review_name,
                    'reviewer_name' => $review->reviewer_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'location_name' => $review->location_name,
                    'location_title' => $review->location_title,
                    'reply_name' => $review->reply_name,
                    'reply_comment' => $review->reply_comment,
                    'reply_updated_at' => $review->reply_updated_at?->toISOString(),
                    'review_created_at' => $review->review_created_at?->toISOString(),
                    'review_updated_at' => $review->review_updated_at?->toISOString(),
                ];
            })->values(),
        ]);
    }

    /**
     * @return array{
     *     month:string,
     *     location:string,
     *     reviews_count:int,
     *     average_rating:?float,
     *     reviews:\Illuminate\Support\Collection<int, GoogleBusinessProfileReview>
     * }
     */
    private function buildMonthlyLocationReviewSummary(InternalGoogleReviewCountRequest $request): array
    {
        $validated = $request->validated();
        $location = $validated['location'];
        $month = $validated['month'];

        $locationQuery = GoogleBusinessProfileReview::query()
            ->withoutSalamanca()
            ->where('location_title', $location);

        if (! (clone $locationQuery)->exists()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Location not found',
            ], 404));
        }

        $monthStart = Carbon::createFromFormat('m-y', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $reviewsQuery = (clone $locationQuery)
            ->whereBetween('review_created_at', [$monthStart, $monthEnd])
            ->orderBy('review_created_at')
            ->orderBy('id');

        $reviews = $reviewsQuery->get();
        $reviewsCount = $reviews->count();
        $averageRating = $reviewsCount > 0
            ? round((float) $reviews->avg('rating'), 2)
            : null;

        return [
            'month' => $month,
            'location' => $location,
            'reviews_count' => $reviewsCount,
            'average_rating' => $averageRating,
            'reviews' => $reviews,
        ];
    }
}
