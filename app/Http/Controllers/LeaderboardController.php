<?php

namespace App\Http\Controllers;

use App\Models\SalesLeaderboardEntry;
use App\Services\SalesforceLeaderboardService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LeaderboardController extends Controller
{
    public function index(SalesforceLeaderboardService $service)
    {
        $entries = Schema::hasTable('sales_leaderboard_entries')
            ? SalesLeaderboardEntry::query()
                ->with('user')
                ->orderBy('ranking_position')
                ->get()
            : new Collection();

        $salesforceConfigReady = filled(config('services.salesforce.client_id'))
            && filled(config('services.salesforce.client_secret'))
            && filled(config('services.salesforce.redirect_uri'));

        return view('leaderboard.index', [
            'entries' => $entries,
            'connection' => $service->getConnection(),
            'salesforceConfigReady' => $salesforceConfigReady,
            'leaderboardTablesReady' => Schema::hasTable('sales_leaderboard_entries')
                && Schema::hasTable('salesforce_connections'),
        ]);
    }
}
