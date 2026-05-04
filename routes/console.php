<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('salesforce:sync-leaderboard')->everyTenMinutes();
Schedule::command('google-business-profile:sync-reviews')->everyFiveMinutes();
