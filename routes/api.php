<?php

use App\Http\Controllers\InternalGoogleReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.basic_auth')
    ->prefix('internal/google-reviews')
    ->group(function (): void {
        Route::get('/count', [InternalGoogleReviewController::class, 'count'])
            ->name('internal.google-reviews.count');
    });
