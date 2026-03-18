<?php

namespace App\Http\Controllers;

use App\Models\SalesLeaderboardEntry;
use App\Services\LeaderboardTrendService;
use App\Services\SalesforceLeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LeaderboardController extends Controller
{
    public function index(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        $search = trim((string) $request->query('search', ''));
        $leaderboardTablesReady = Schema::hasTable('sales_leaderboard_entries')
            && Schema::hasTable('salesforce_connections');

        $entries = new LengthAwarePaginator([], 0, 10);
        $entryItems = new Collection();
        $topEntries = new Collection();
        $hasLeaderboardData = false;

        if (Schema::hasTable('sales_leaderboard_entries')) {
            $topEntries = SalesLeaderboardEntry::query()
                ->with('user')
                ->orderBy('ranking_position')
                ->limit(3)
                ->get();

            $entriesQuery = SalesLeaderboardEntry::query()
                ->with('user')
                ->orderBy('ranking_position');

            if ($search !== '') {
                $entriesQuery->where(function ($query) use ($search) {
                    $query->where('seller_name', 'like', "%{$search}%")
                        ->orWhere('salesforce_user_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $entries = $entriesQuery
                ->paginate(10)
                ->withQueryString();
            $entryItems = collect($entries->items());
            $hasLeaderboardData = SalesLeaderboardEntry::query()->exists();
        }

        $salesforceConfigReady = filled(config('services.salesforce.client_id'))
            && filled(config('services.salesforce.client_secret'))
            && filled(config('services.salesforce.redirect_uri'));

        return view('leaderboard.index', [
            'entries' => $entries,
            'entryItems' => $entryItems,
            'topEntries' => $topEntries,
            'entryMovements' => $trendService->buildMovementMap($entryItems),
            'topEntryMovements' => $trendService->buildMovementMap($topEntries),
            'search' => $search,
            'hasLeaderboardData' => $hasLeaderboardData,
            'connection' => $service->getConnection(),
            'salesforceConfigReady' => $salesforceConfigReady,
            'leaderboardTablesReady' => $leaderboardTablesReady,
        ]);
    }
}
