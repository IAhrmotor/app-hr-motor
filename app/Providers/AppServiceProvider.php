<?php

namespace App\Providers;

use App\Models\User;
use App\Services\NotificationBadgeBroadcaster;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        if (! app()->environment('local')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('chat-api', function (Request $request): Limit {
            $identifier = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(60)->by($identifier);
        });

        DatabaseNotification::created(function (DatabaseNotification $notification): void {
            $notifiable = $notification->notifiable;

            if (! $notifiable instanceof User) {
                return;
            }

            app(NotificationBadgeBroadcaster::class)->broadcast($notifiable);
        });
    }
}
