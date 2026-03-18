<?php

namespace App\Http\Controllers;

use App\Models\PurchaseLeaderboardDailySnapshot;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardDailySnapshot;
use App\Models\SalesLeaderboardEntry;
use App\Services\LeaderboardTrendService;
use App\Services\SalesforceLeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LeaderboardController extends Controller
{
    public function index()
    {
        return redirect()->route('leaderboard.sales');
    }

    public function sales(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        $leaderboardTablesReady = Schema::hasTable('sales_leaderboard_entries')
            && Schema::hasTable('sales_leaderboard_daily_snapshots')
            && Schema::hasTable('salesforce_connections');

        return $this->renderPage(
            request: $request,
            service: $service,
            trendService: $trendService,
            config: [
                'entry_model' => SalesLeaderboardEntry::class,
                'entry_table' => 'sales_leaderboard_entries',
                'snapshot_model' => SalesLeaderboardDailySnapshot::class,
                'snapshot_table' => 'sales_leaderboard_daily_snapshots',
                'search_param' => 'search',
                'page_param' => 'page',
                'route_name' => 'leaderboard.sales',
                'eyebrow' => 'Ventas',
                'title' => 'Ranking de ventas',
                'description' => 'Ranking de comerciales por numero de ventas del mes sincronizado desde Salesforce.',
                'metric_label' => 'Ventas',
                'metric_field' => 'total_sales',
                'empty_title' => 'Aun no hay datos de ventas',
            ],
            leaderboardTablesReady: $leaderboardTablesReady
        );
    }

    public function purchases(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        $leaderboardTablesReady = Schema::hasTable('purchase_leaderboard_entries')
            && Schema::hasTable('purchase_leaderboard_daily_snapshots')
            && Schema::hasTable('salesforce_connections');

        return $this->renderPage(
            request: $request,
            service: $service,
            trendService: $trendService,
            config: [
                'entry_model' => PurchaseLeaderboardEntry::class,
                'entry_table' => 'purchase_leaderboard_entries',
                'snapshot_model' => PurchaseLeaderboardDailySnapshot::class,
                'snapshot_table' => 'purchase_leaderboard_daily_snapshots',
                'search_param' => 'search',
                'page_param' => 'page',
                'route_name' => 'leaderboard.purchases',
                'eyebrow' => 'Compras',
                'title' => 'Ranking de compras',
                'description' => 'Ranking de comerciales por numero de compras de coches del mes sincronizado desde Salesforce.',
                'metric_label' => 'Compras',
                'metric_field' => 'total_purchases',
                'empty_title' => 'Aun no hay datos de compras',
            ],
            leaderboardTablesReady: $leaderboardTablesReady
        );
    }

    private function renderPage(
        Request $request,
        SalesforceLeaderboardService $service,
        LeaderboardTrendService $trendService,
        array $config,
        bool $leaderboardTablesReady
    ) {
        $leaderboard = $this->buildLeaderboardViewData($request, $trendService, $config);
        $salesforceConfigReady = filled(config('services.salesforce.client_id'))
            && filled(config('services.salesforce.client_secret'))
            && filled(config('services.salesforce.redirect_uri'));

        $emptyDescription = ! $leaderboardTablesReady
            ? 'Ejecuta primero las migraciones para activar el almacenamiento del ranking.'
            : ($service->getConnection()
                ? 'Ejecuta una sincronizacion para llenar el ranking.'
                : ($salesforceConfigReady
                    ? 'Completa la autorizacion OAuth en Salesforce y despues ejecuta la primera sincronizacion.'
                    : 'Completa la configuracion de Salesforce y despues autoriza la conexion.'));

        return view('leaderboard.show', [
            'leaderboard' => $leaderboard,
            'connection' => $service->getConnection(),
            'salesforceConfigReady' => $salesforceConfigReady,
            'leaderboardTablesReady' => $leaderboardTablesReady,
            'eyebrow' => $config['eyebrow'],
            'title' => $config['title'],
            'description' => $config['description'],
            'metricLabel' => $config['metric_label'],
            'metricField' => $config['metric_field'],
            'emptyTitle' => $config['empty_title'],
            'emptyDescription' => $emptyDescription,
        ]);
    }

    private function buildLeaderboardViewData(Request $request, LeaderboardTrendService $trendService, array $config): array
    {
        $search = trim((string) $request->query($config['search_param'], ''));
        $entries = new LengthAwarePaginator(
            [],
            0,
            10,
            1,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $config['page_param'],
            ]
        );
        $entryItems = new Collection();
        $topEntries = new Collection();
        $hasLeaderboardData = false;

        if (Schema::hasTable($config['entry_table'])) {
            $topEntries = $config['entry_model']::query()
                ->with('user')
                ->orderBy('ranking_position')
                ->limit(3)
                ->get();

            $entriesQuery = $config['entry_model']::query()
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
                ->paginate(10, ['*'], $config['page_param'])
                ->withQueryString();
            $entryItems = collect($entries->items());
            $hasLeaderboardData = $config['entry_model']::query()->exists();
        }

        return [
            'entries' => $entries,
            'entryItems' => $entryItems,
            'topEntries' => $topEntries,
            'entryMovements' => $trendService->buildMovementMap(
                $entryItems,
                $config['snapshot_model'],
                $config['snapshot_table']
            ),
            'topEntryMovements' => $trendService->buildMovementMap(
                $topEntries,
                $config['snapshot_model'],
                $config['snapshot_table']
            ),
            'search' => $search,
            'hasLeaderboardData' => $hasLeaderboardData,
            'searchParam' => $config['search_param'],
            'pageParam' => $config['page_param'],
            'routeName' => $config['route_name'],
        ];
    }
}
