<?php

namespace App\Http\Controllers;

use App\Http\Requests\InternalGoogleReviewCountRequest;
use App\Models\Dealership;
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

        $dealership = Dealership::query()
            ->where('google_business_profile_location_title', $location)
            ->orWhere('name', $location)
            ->first();

        if (! $dealership) {
            return response()->json([
                'message' => 'Location not found',
            ], 404);
        }

        $monthStart = Carbon::createFromFormat('m-y', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $reviewsQuery = GoogleBusinessProfileReview::query()
            ->whereBetween('review_created_at', [$monthStart, $monthEnd])
            ->where(function ($query) use ($dealership, $location): void {
                $query
                    ->where('dealership_id', $dealership->id)
                    ->orWhere('location_title', $location);
            });

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
