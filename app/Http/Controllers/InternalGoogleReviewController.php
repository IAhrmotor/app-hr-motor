<?php

namespace App\Http\Controllers;

use App\Http\Requests\InternalGoogleReviewCountRequest;
use App\Models\GoogleBusinessProfileReview;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class InternalGoogleReviewController extends Controller
{
    public function count(InternalGoogleReviewCountRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $location = $validated['location'];
        $month = $validated['month'];

        $locationQuery = GoogleBusinessProfileReview::query()
            ->withoutSalamanca()
            ->where('location_title', $location);

        if (! (clone $locationQuery)->exists()) {
            return response()->json([
                'message' => 'Location not found',
            ], 404);
        }

        $monthStart = Carbon::createFromFormat('m-y', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $reviewsQuery = (clone $locationQuery)
            ->whereBetween('review_created_at', [$monthStart, $monthEnd])
            ->orderBy('id');

        $reviewsCount = (clone $reviewsQuery)->count();
        $averageRating = $reviewsCount > 0
            ? round((float) $reviewsQuery->avg('rating'), 2)
            : null;

        return response()->json([
            'month' => $month,
            'location' => $location,
            'reviews_count' => $reviewsCount,
            'average_rating' => $averageRating,
        ]);
    }
}
