<?php

namespace App\Http\Controllers;

use App\Models\PurchaseLeaderboardDailySnapshot;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\Dealership;
use App\Models\SalesforceConnection;
use App\Models\SalesLeaderboardDailySnapshot;
use App\Models\SalesLeaderboardEntry;
use App\Models\VehicleLeaderboardDailySnapshot;
use App\Models\VehicleLeaderboardEntry;
use App\Models\Zone;
use App\Services\LeaderboardTrendService;
use App\Services\SalesforceLeaderboardService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LeaderboardController extends Controller
{
    public function index()
    {
        abort_unless(app_can_access_rankings(), 403);

        return redirect()->route('leaderboard.sales');
    }

    public function sales(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        abort_unless(app_can_access_rankings(), 403);

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
                'entity_label_plural' => 'comerciales',
                'search_placeholder' => 'Buscar comercial, email o delegacion',
                'show_dealership_leaderboard' => true,
                'dealership_title' => 'Ranking por delegaciones',
                'dealership_description' => 'Comparativa de ventas acumuladas por delegacion para fomentar la competicion entre equipos.',
                'dealership_empty_title' => 'Aun no hay datos de delegaciones',
                'show_zone_leaderboard' => true,
                'zone_title' => 'Ranking por zonas',
                'zone_description' => 'Comparativa de ventas acumuladas por zona a partir de las delegaciones asignadas en cada una.',
                'zone_empty_title' => 'Aun no hay datos de zonas',
            ],
            leaderboardTablesReady: $leaderboardTablesReady
        );
    }

    public function purchases(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        abort_unless(app_can_access_rankings(), 403);

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
                'description' => 'Ranking de comerciales por numero de compras del mes sincronizado desde Salesforce.',
                'metric_label' => 'Compras',
                'metric_field' => 'total_purchases',
                'empty_title' => 'Aun no hay datos de compras',
                'entity_label_plural' => 'comerciales',
                'search_placeholder' => 'Buscar comercial, email o delegacion',
                'show_dealership_leaderboard' => true,
                'dealership_title' => 'Ranking por delegaciones',
                'dealership_description' => 'Comparativa de compras acumuladas por delegacion para fomentar la competicion entre equipos.',
                'dealership_empty_title' => 'Aun no hay datos de delegaciones',
                'show_zone_leaderboard' => true,
                'zone_title' => 'Ranking por zonas',
                'zone_description' => 'Comparativa de compras acumuladas por zona a partir de las delegaciones asignadas en cada una.',
                'zone_empty_title' => 'Aun no hay datos de zonas',
            ],
            leaderboardTablesReady: $leaderboardTablesReady
        );
    }

    public function vehicles(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        abort_unless(app_can_access_rankings(), 403);

        $connection = $service->getConnection();
        $leaderboardTablesReady = Schema::hasTable('vehicle_leaderboard_entries')
            && Schema::hasTable('vehicle_leaderboard_daily_snapshots')
            && Schema::hasTable('salesforce_connections');

        $salesforceConfigReady = filled(config('services.salesforce.client_id'))
            && filled(config('services.salesforce.client_secret'))
            && filled(config('services.salesforce.redirect_uri'));

        $emptyDescription = ! $leaderboardTablesReady
            ? 'Ejecuta primero las migraciones para activar el almacenamiento del ranking.'
            : ($connection
                ? 'Ejecuta una sincronización para llenar el ranking.'
                : ($salesforceConfigReady
                    ? 'Completa la autorización OAuth en Salesforce y después ejecuta la primera sincronización.'
                    : 'Completa la configuración de Salesforce y después autoriza la conexión.'));

        return view('leaderboard.vehicles', [
            'connection' => $connection,
            'salesforceConfigReady' => $salesforceConfigReady,
            'leaderboardTablesReady' => $leaderboardTablesReady,
            'emptyDescription' => $emptyDescription,
            'demoMode' => $connection === null,
            'hotLeaderboard' => $this->buildVehicleLeaderboardViewData($request, $trendService, [
                'temperature' => 'hot',
                'title' => 'Coches calientes',
                'description' => 'Los vehículos disponibles y en garantía que concentran más interés comercial ahora mismo.',
                'search_param' => 'hot_search',
                'page_param' => 'hot_page',
                'route_name' => 'leaderboard.vehicles',
                'theme' => 'hot',
                'empty_title' => 'Aún no hay coches calientes',
                'search_placeholder' => 'Buscar coche caliente',
            ], $connection),
            'coldLeaderboard' => $this->buildVehicleLeaderboardViewData($request, $trendService, [
                'temperature' => 'cold',
                'title' => 'Coches fríos',
                'description' => 'Los vehículos disponibles y en garantía con menos leads asociados en el ranking actual.',
                'search_param' => 'cold_search',
                'page_param' => 'cold_page',
                'route_name' => 'leaderboard.vehicles',
                'theme' => 'cold',
                'empty_title' => 'Aún no hay coches fríos',
                'search_placeholder' => 'Buscar coche frío',
            ], $connection),
        ]);
    }

    private function renderPage(
        Request $request,
        SalesforceLeaderboardService $service,
        LeaderboardTrendService $trendService,
        array $config,
        bool $leaderboardTablesReady
    ) {
        $leaderboard = $this->buildLeaderboardViewData($request, $trendService, $config);
        $dealershipLeaderboard = ($config['show_dealership_leaderboard'] ?? false)
            ? $this->buildDealershipLeaderboardViewData($request, $trendService, $config)
            : null;
        $zoneLeaderboard = ($config['show_zone_leaderboard'] ?? false)
            ? $this->buildZoneLeaderboardViewData($request, $trendService, $config)
            : null;
        $salesforceConfigReady = filled(config('services.salesforce.client_id'))
            && filled(config('services.salesforce.client_secret'))
            && filled(config('services.salesforce.redirect_uri'));

        $emptyDescription = ! $leaderboardTablesReady
            ? 'Ejecuta primero las migraciones para activar el almacenamiento del ranking.'
            : ($service->getConnection()
                ? 'Ejecuta una sincronización para llenar el ranking.'
                : ($salesforceConfigReady
                    ? 'Completa la autorización OAuth en Salesforce y después ejecuta la primera sincronización.'
                    : 'Completa la configuración de Salesforce y después autoriza la conexión.'));

        if ($request->boolean('ajax')) {
            $section = $request->query('section', 'leaderboard');
            $aggregateType = 'user';
            $sectionLeaderboard = $leaderboard;

            if ($section === 'dealership' && $dealershipLeaderboard !== null) {
                $aggregateType = 'dealership';
                $sectionLeaderboard = $dealershipLeaderboard;
            } elseif ($section === 'zone' && $zoneLeaderboard !== null) {
                $aggregateType = 'zone';
                $sectionLeaderboard = $zoneLeaderboard;
            }

            return response()->json([
                'html' => view('leaderboard.partials.section', [
                    'leaderboard' => $sectionLeaderboard,
                    'eyebrow' => $config['eyebrow'],
                    'title' => match ($aggregateType) {
                        'dealership' => $config['dealership_title'] ?? 'Ranking por delegaciones',
                        'zone' => $config['zone_title'] ?? 'Ranking por zonas',
                        default => $config['title'],
                    },
                    'description' => match ($aggregateType) {
                        'dealership' => $config['dealership_description'] ?? '',
                        'zone' => $config['zone_description'] ?? '',
                        default => $config['description'],
                    },
                    'metricLabel' => $config['metric_label'],
                    'metricField' => $config['metric_field'],
                    'emptyTitle' => match ($aggregateType) {
                        'dealership' => $config['dealership_empty_title'] ?? 'Aun no hay datos de delegaciones',
                        'zone' => $config['zone_empty_title'] ?? 'Aun no hay datos de zonas',
                        default => $config['empty_title'],
                    },
                    'emptyDescription' => $emptyDescription,
                    'entityLabelPlural' => match ($aggregateType) {
                        'dealership' => 'delegaciones',
                        'zone' => 'zonas',
                        default => $config['entity_label_plural'] ?? 'comerciales',
                    },
                    'searchPlaceholder' => match ($aggregateType) {
                        'dealership' => 'Buscar delegacion',
                        'zone' => 'Buscar zona',
                        default => $config['search_placeholder'] ?? 'Buscar comercial, email o delegacion',
                    },
                    'aggregateType' => $aggregateType,
                    'searchParam' => $sectionLeaderboard['searchParam'],
                    'pageParam' => $sectionLeaderboard['pageParam'],
                ])->render(),
            ]);
        }

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
            'dealershipLeaderboard' => $dealershipLeaderboard,
            'dealershipTitle' => $config['dealership_title'] ?? 'Ranking por delegaciones',
            'dealershipDescription' => $config['dealership_description'] ?? '',
            'dealershipEmptyTitle' => $config['dealership_empty_title'] ?? 'Aun no hay datos de delegaciones',
            'zoneLeaderboard' => $zoneLeaderboard,
            'zoneTitle' => $config['zone_title'] ?? 'Ranking por zonas',
            'zoneDescription' => $config['zone_description'] ?? '',
            'zoneEmptyTitle' => $config['zone_empty_title'] ?? 'Aun no hay datos de zonas',
            'entityLabelPlural' => $config['entity_label_plural'] ?? 'comerciales',
            'searchPlaceholder' => $config['search_placeholder'] ?? 'Buscar comercial, email o delegacion',
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
            $allEntries = $config['entry_model']::query()
                ->with(['user.assignedDealership'])
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->orderBy('ranking_position')
                ->get();

            $topEntries = $allEntries->take(3)->values();

            $entriesQuery = $config['entry_model']::query()
                ->with(['user.assignedDealership'])
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->orderBy('ranking_position');

            if ($search !== '') {
                $entriesQuery->where(function ($query) use ($search) {
                    $query->where('seller_name', 'like', "%{$search}%")
                        ->orWhere('salesforce_user_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('dealership', 'like', "%{$search}%");
                        });
                });
            }

            $entries = $entriesQuery
                ->paginate(10, ['*'], $config['page_param'])
                ->withQueryString();
            $entryItems = collect($entries->items());
            $hasLeaderboardData = $allEntries->isNotEmpty();
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

    private function buildDealershipLeaderboardViewData(Request $request, LeaderboardTrendService $trendService, array $config): array
    {
        $search = trim((string) $request->query('dealership_search', ''));
        $entries = new LengthAwarePaginator(
            [],
            0,
            10,
            1,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'dealership_page',
            ]
        );
        $entryItems = new Collection();
        $topEntries = new Collection();
        $hasLeaderboardData = false;
        $dealerships = new Collection();

        if (Schema::hasTable($config['entry_table'])) {
            $allEntries = $config['entry_model']::query()
                ->with(['user.assignedDealership'])
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->orderBy('ranking_position')
                ->get();

            $dealerships = Schema::hasTable('dealerships')
                ? Dealership::query()->select(['id', 'name', 'image_path'])->orderBy('name')->get()
                : new Collection();

            $aggregatedEntries = $this->aggregateEntriesByDealership(
                $allEntries,
                $config['metric_field'],
                $search,
                $config['entry_model'],
                $dealerships
            );
            $topEntries = $aggregatedEntries->take(3)->values();
            $entries = $this->paginateCollection($aggregatedEntries, 'dealership_page');
            $entryItems = collect($entries->items());
            $hasLeaderboardData = $allEntries->isNotEmpty() || $dealerships->isNotEmpty();
        }

        return [
            'entries' => $entries,
            'entryItems' => $entryItems,
            'topEntries' => $topEntries,
            'entryMovements' => $trendService->buildDealershipMovementMap(
                $entryItems,
                $config['snapshot_model'],
                $config['snapshot_table'],
                $config['metric_field']
            ),
            'topEntryMovements' => $trendService->buildDealershipMovementMap(
                $topEntries,
                $config['snapshot_model'],
                $config['snapshot_table'],
                $config['metric_field']
            ),
            'search' => $search,
            'hasLeaderboardData' => $hasLeaderboardData,
            'searchParam' => 'dealership_search',
            'pageParam' => 'dealership_page',
            'routeName' => $config['route_name'],
        ];
    }

    private function buildZoneLeaderboardViewData(Request $request, LeaderboardTrendService $trendService, array $config): array
    {
        $search = trim((string) $request->query('zone_search', ''));
        $entries = new LengthAwarePaginator(
            [],
            0,
            10,
            1,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'zone_page',
            ]
        );
        $entryItems = new Collection();
        $topEntries = new Collection();
        $hasLeaderboardData = false;
        $zones = new Collection();

        if (Schema::hasTable($config['entry_table'])) {
            $allEntries = $config['entry_model']::query()
                ->with(['user.assignedDealership.zone'])
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->orderBy('ranking_position')
                ->get();

            $zones = Schema::hasTable('zones')
                ? Zone::query()->select(['id', 'name'])->orderBy('name')->get()
                : new Collection();

            $aggregatedEntries = $this->aggregateEntriesByZone(
                $allEntries,
                $config['metric_field'],
                $search,
                $config['entry_model'],
                $zones
            );
            $topEntries = $aggregatedEntries->take(3)->values();
            $entries = $this->paginateCollection($aggregatedEntries, 'zone_page');
            $entryItems = collect($entries->items());
            $hasLeaderboardData = $allEntries->isNotEmpty() || $zones->isNotEmpty();
        }

        return [
            'entries' => $entries,
            'entryItems' => $entryItems,
            'topEntries' => $topEntries,
            'entryMovements' => $trendService->buildZoneMovementMap(
                $entryItems,
                $config['snapshot_model'],
                $config['snapshot_table'],
                $config['metric_field']
            ),
            'topEntryMovements' => $trendService->buildZoneMovementMap(
                $topEntries,
                $config['snapshot_model'],
                $config['snapshot_table'],
                $config['metric_field']
            ),
            'search' => $search,
            'hasLeaderboardData' => $hasLeaderboardData,
            'searchParam' => 'zone_search',
            'pageParam' => 'zone_page',
            'routeName' => $config['route_name'],
        ];
    }

    private function buildVehicleLeaderboardViewData(
        Request $request,
        LeaderboardTrendService $trendService,
        array $config,
        ?SalesforceConnection $connection = null
    ): array
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

        if ($connection === null) {
            $demoLeaderboard = $this->buildDemoVehicleLeaderboardViewData($request, $config);

            return array_merge($demoLeaderboard, [
                'entryMovements' => $trendService->buildMovementMap(
                    $demoLeaderboard['entryItems'],
                    VehicleLeaderboardDailySnapshot::class,
                    'vehicle_leaderboard_daily_snapshots'
                ),
                'topEntryMovements' => $trendService->buildMovementMap(
                    $demoLeaderboard['topEntries'],
                    VehicleLeaderboardDailySnapshot::class,
                    'vehicle_leaderboard_daily_snapshots'
                ),
            ]);
        }

        if (Schema::hasTable('vehicle_leaderboard_entries')) {
            $allEntries = VehicleLeaderboardEntry::query()
                ->where('temperature', $config['temperature'])
                ->orderBy('ranking_position')
                ->get();

            $topEntries = $allEntries->take(3)->values();

            $entriesQuery = VehicleLeaderboardEntry::query()
                ->where('temperature', $config['temperature'])
                ->orderBy('ranking_position');

            if ($search !== '') {
                $entriesQuery->where(function ($query) use ($search) {
                    $query->where('vehicle_name', 'like', "%{$search}%")
                        ->orWhere('vehicle_commercial_name', 'like', "%{$search}%")
                        ->orWhere('vehicle_plate', 'like', "%{$search}%");
                });
            }

            $entries = $entriesQuery
                ->paginate(10, ['*'], $config['page_param'])
                ->withQueryString();
            $entryItems = collect($entries->items());
            $hasLeaderboardData = $allEntries->isNotEmpty();
        }

        return [
            'title' => $config['title'],
            'description' => $config['description'],
            'theme' => $config['theme'],
            'entries' => $entries,
            'entryItems' => $entryItems,
            'topEntries' => $topEntries,
            'entryMovements' => $trendService->buildMovementMap(
                $entryItems,
                VehicleLeaderboardDailySnapshot::class,
                'vehicle_leaderboard_daily_snapshots'
            ),
            'topEntryMovements' => $trendService->buildMovementMap(
                $topEntries,
                VehicleLeaderboardDailySnapshot::class,
                'vehicle_leaderboard_daily_snapshots'
            ),
            'search' => $search,
            'hasLeaderboardData' => $hasLeaderboardData,
            'searchParam' => $config['search_param'],
            'pageParam' => $config['page_param'],
            'routeName' => $config['route_name'],
            'emptyTitle' => $config['empty_title'],
            'searchPlaceholder' => $config['search_placeholder'],
        ];
    }

    private function buildDemoVehicleLeaderboardViewData(Request $request, array $config): array
    {
        $search = trim((string) $request->query($config['search_param'], ''));
        $demoEntries = $this->demoVehicleEntries($config['temperature']);

        if ($search !== '') {
            $needle = Str::lower($search);

            $demoEntries = $demoEntries
                ->filter(function (VehicleLeaderboardEntry $entry) use ($needle): bool {
                    return str_contains(Str::lower((string) $entry->vehicle_name), $needle)
                        || str_contains(Str::lower((string) $entry->vehicle_commercial_name), $needle)
                        || str_contains(Str::lower((string) $entry->vehicle_plate), $needle);
                })
                ->values();
        }

        $entries = $this->paginateCollection($demoEntries, $config['page_param']);
        $entryItems = collect($entries->items());
        $topEntries = $demoEntries->take(3)->values();
        $tableEntries = $demoEntries->slice(3)->values();

        return [
            'title' => $config['title'],
            'description' => $config['description'],
            'theme' => $config['theme'],
            'entries' => $entries,
            'entryItems' => $entryItems,
            'topEntries' => $topEntries,
            'tableEntries' => $tableEntries,
            'search' => $search,
            'hasLeaderboardData' => true,
            'searchParam' => $config['search_param'],
            'pageParam' => $config['page_param'],
            'routeName' => $config['route_name'],
            'emptyTitle' => $config['empty_title'],
            'searchPlaceholder' => $config['search_placeholder'],
        ];
    }

    private function demoVehicleEntries(string $temperature): Collection
    {
        $rows = $temperature === 'hot'
            ? [
                ['Demo Auto 01', 'Demo Auto 01 Sport', '0001 DMO', 42, 'demo-hot-01'],
                ['Demo Auto 02', 'Demo Auto 02 Premium', '0002 DMO', 39, 'demo-hot-02'],
                ['Demo Auto 03', 'Demo Auto 03 Hybrid', '0003 DMO', 36, 'demo-hot-03'],
                ['Demo Auto 04', 'Demo Auto 04 SUV', '0004 DMO', 33, 'demo-hot-04'],
                ['Demo Auto 05', 'Demo Auto 05 Touring', '0005 DMO', 30, 'demo-hot-05'],
                ['Demo Auto 06', 'Demo Auto 06 Compact', '0006 DMO', 27, 'demo-hot-06'],
                ['Demo Auto 07', 'Demo Auto 07 Urban', '0007 DMO', 24, 'demo-hot-07'],
                ['Demo Auto 08', 'Demo Auto 08 Tech', '0008 DMO', 21, 'demo-hot-08'],
                ['Demo Auto 09', 'Demo Auto 09 Line', '0009 DMO', 18, 'demo-hot-09'],
                ['Demo Auto 10', 'Demo Auto 10 Plus', '0010 DMO', 15, 'demo-hot-10'],
            ]
            : [
                ['Demo Auto 11', 'Demo Auto 11 Eco', '0011 DMO', 3, 'demo-cold-01'],
                ['Demo Auto 12', 'Demo Auto 12 City', '0012 DMO', 3, 'demo-cold-02'],
                ['Demo Auto 13', 'Demo Auto 13 Basic', '0013 DMO', 2, 'demo-cold-03'],
                ['Demo Auto 14', 'Demo Auto 14 Entry', '0014 DMO', 2, 'demo-cold-04'],
                ['Demo Auto 15', 'Demo Auto 15 Comfort', '0015 DMO', 2, 'demo-cold-05'],
                ['Demo Auto 16', 'Demo Auto 16 Flex', '0016 DMO', 1, 'demo-cold-06'],
                ['Demo Auto 17', 'Demo Auto 17 Easy', '0017 DMO', 1, 'demo-cold-07'],
                ['Demo Auto 18', 'Demo Auto 18 Pure', '0018 DMO', 1, 'demo-cold-08'],
                ['Demo Auto 19', 'Demo Auto 19 Base', '0019 DMO', 0, 'demo-cold-09'],
                ['Demo Auto 20', 'Demo Auto 20 Start', '0020 DMO', 0, 'demo-cold-10'],
            ];

        return collect($rows)->map(function (array $row, int $index) use ($temperature): VehicleLeaderboardEntry {
            [$vehicleName, $vehicleCommercialName, $vehiclePlate, $totalLeads, $vehicleId] = $row;

            $entry = new VehicleLeaderboardEntry();
            $entry->forceFill([
                'id' => $vehicleId,
                'temperature' => $temperature,
                'ranking_position' => $index + 1,
                'vehicle_salesforce_id' => $vehicleId,
                'vehicle_name' => $vehicleName,
                'vehicle_commercial_name' => $vehicleCommercialName,
                'vehicle_plate' => $vehiclePlate,
                'vehicle_image_url' => null,
                'total_leads' => $totalLeads,
                'synced_at' => now(),
            ]);

            return $entry;
        })->values();
    }

    private function excludedLeaderboardUserIds(): array
    {
        return config('services.salesforce.excluded_leaderboard_user_ids', []);
    }

    private function aggregateEntriesByDealership(
        Collection $entries,
        string $metricField,
        string $search,
        string $entryModelClass,
        Collection $dealerships = new Collection()
    ): Collection
    {
        $aggregatedEntries = $entries
            ->groupBy(fn (Model $entry): string => $this->resolveDealershipGroupKey($entry))
            ->map(function (Collection $dealershipEntries) use ($metricField) {
                $representative = $dealershipEntries->first();
                $dealership = $representative->user?->assignedDealership;
                $dealershipName = $this->resolveDealershipName($representative);
                $totalMetric = (float) $dealershipEntries->sum($metricField);
                $commercialCount = $dealershipEntries->pluck('user_id')->filter()->unique()->count();

                $entry = new ($representative::class)();
                $entry->forceFill([
                    'id' => 'dealership:' . ($dealership?->getKey() ?? Str::slug($dealershipName, '-')),
                    'ranking_position' => 0,
                    'seller_name' => $dealershipName,
                    'salesforce_user_id' => null,
                    $metricField => $totalMetric,
                    'dealership_id' => $dealership?->getKey(),
                    'dealership_name' => $dealershipName,
                    'dealership_image_url' => $dealership?->image_url,
                    'commercial_count' => $commercialCount,
                ]);

                return $entry;
            })
            ->values();

        if ($dealerships->isNotEmpty()) {
            $existingDealershipIds = $aggregatedEntries
                ->pluck('dealership_id')
                ->filter()
                ->map(fn ($dealershipId) => (string) $dealershipId)
                ->all();
            $existingDealershipNames = $aggregatedEntries
                ->pluck('dealership_name')
                ->filter()
                ->map(fn ($dealershipName) => Str::lower(trim((string) $dealershipName)))
                ->all();

            $missingEntries = $dealerships
                ->reject(function (Dealership $dealership) use ($existingDealershipIds, $existingDealershipNames): bool {
                    return in_array((string) $dealership->id, $existingDealershipIds, true)
                        || in_array(Str::lower($dealership->name), $existingDealershipNames, true);
                })
                ->map(function (Dealership $dealership) use ($entryModelClass, $metricField): Model {
                    $entry = new $entryModelClass();
                    $entry->forceFill([
                        'id' => 'dealership:' . $dealership->id,
                        'ranking_position' => 0,
                        'user_id' => null,
                        'salesforce_user_id' => null,
                        'seller_name' => $dealership->name,
                        $metricField => 0,
                        'dealership_id' => $dealership->id,
                        'dealership_name' => $dealership->name,
                        'dealership_image_url' => $dealership->image_url,
                        'commercial_count' => 0,
                        'synced_at' => now(),
                    ]);

                    return $entry;
                });

            $aggregatedEntries = $aggregatedEntries->concat($missingEntries)->values();
        }

        $aggregatedEntries = $aggregatedEntries
            ->sort(function (Model $left, Model $right) use ($metricField) {
                $metricComparison = (float) $right->getAttribute($metricField) <=> (float) $left->getAttribute($metricField);

                if ($metricComparison !== 0) {
                    return $metricComparison;
                }

                return strcmp(
                    (string) $left->getAttribute('dealership_name'),
                    (string) $right->getAttribute('dealership_name')
                );
            })
            ->values()
            ->map(function (Model $entry, int $index) {
                $entry->setAttribute('ranking_position', $index + 1);

                return $entry;
            });

        if ($search === '') {
            return $aggregatedEntries;
        }

        $needle = Str::lower($search);

        return $aggregatedEntries
            ->filter(fn (Model $entry): bool => str_contains(Str::lower((string) $entry->getAttribute('dealership_name')), $needle))
            ->values();
    }

    private function aggregateEntriesByZone(
        Collection $entries,
        string $metricField,
        string $search,
        string $entryModelClass,
        Collection $zones = new Collection()
    ): Collection
    {
        $aggregatedEntries = $entries
            ->groupBy(fn (Model $entry): string => $this->resolveZoneGroupKey($entry))
            ->map(function (Collection $zoneEntries) use ($metricField) {
                $representative = $zoneEntries->first();
                $zone = $representative->user?->assignedDealership?->zone;
                $zoneName = $this->resolveZoneName($representative);
                $totalMetric = (float) $zoneEntries->sum($metricField);
                $dealershipCount = $zoneEntries
                    ->map(fn (Model $entry): string => $this->resolveDealershipIdentifier($entry))
                    ->unique()
                    ->count();

                $entry = new ($representative::class)();
                $entry->forceFill([
                    'id' => 'zone:' . ($zone?->getKey() ?? Str::slug($zoneName, '-')),
                    'ranking_position' => 0,
                    'seller_name' => $zoneName,
                    'salesforce_user_id' => null,
                    $metricField => $totalMetric,
                    'zone_id' => $zone?->getKey(),
                    'zone_name' => $zoneName,
                    'dealership_name' => $zoneName,
                    'dealership_image_url' => null,
                    'commercial_count' => $dealershipCount,
                ]);

                return $entry;
            })
            ->values();

        if ($zones->isNotEmpty()) {
            $existingZoneIds = $aggregatedEntries
                ->pluck('zone_id')
                ->filter()
                ->map(fn ($zoneId) => (string) $zoneId)
                ->all();
            $existingZoneNames = $aggregatedEntries
                ->pluck('zone_name')
                ->filter()
                ->map(fn ($zoneName) => Str::lower(trim((string) $zoneName)))
                ->all();

            $missingEntries = $zones
                ->reject(function (Zone $zone) use ($existingZoneIds, $existingZoneNames): bool {
                    return in_array((string) $zone->id, $existingZoneIds, true)
                        || in_array(Str::lower($zone->name), $existingZoneNames, true);
                })
                ->map(function (Zone $zone) use ($entryModelClass, $metricField): Model {
                    $entry = new $entryModelClass();
                    $entry->forceFill([
                        'id' => 'zone:' . $zone->id,
                        'ranking_position' => 0,
                        'user_id' => null,
                        'salesforce_user_id' => null,
                        'seller_name' => $zone->name,
                        $metricField => 0,
                        'zone_id' => $zone->id,
                        'zone_name' => $zone->name,
                        'dealership_name' => $zone->name,
                        'dealership_image_url' => null,
                        'commercial_count' => 0,
                        'synced_at' => now(),
                    ]);

                    return $entry;
                });

            $aggregatedEntries = $aggregatedEntries->concat($missingEntries)->values();
        }

        $aggregatedEntries = $aggregatedEntries
            ->sort(function (Model $left, Model $right) use ($metricField) {
                $metricComparison = (float) $right->getAttribute($metricField) <=> (float) $left->getAttribute($metricField);

                if ($metricComparison !== 0) {
                    return $metricComparison;
                }

                return strcmp(
                    (string) $left->getAttribute('zone_name'),
                    (string) $right->getAttribute('zone_name')
                );
            })
            ->values()
            ->map(function (Model $entry, int $index) {
                $entry->setAttribute('ranking_position', $index + 1);

                return $entry;
            });

        if ($search === '') {
            return $aggregatedEntries;
        }

        $needle = Str::lower($search);

        return $aggregatedEntries
            ->filter(fn (Model $entry): bool => str_contains(Str::lower((string) $entry->getAttribute('zone_name')), $needle))
            ->values();
    }

    private function paginateCollection(Collection $entries, string $pageParam): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage($pageParam);
        $perPage = 10;

        return (new LengthAwarePaginator(
            $entries->forPage($currentPage, $perPage)->values(),
            $entries->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageParam,
            ]
        ))->withQueryString();
    }

    private function resolveDealershipName(Model $entry): string
    {
        $dealership = trim((string) ($entry->user?->resolved_dealership_name ?? ''));

        return $dealership !== '' ? $dealership : 'Sin delegacion asignada';
    }

    private function resolveDealershipGroupKey(Model $entry): string
    {
        $dealershipId = $entry->user?->assignedDealership?->getKey();

        if ($dealershipId !== null) {
            return 'id:' . $dealershipId;
        }

        return 'name:' . $this->resolveDealershipName($entry);
    }

    private function resolveZoneName(Model $entry): string
    {
        $zone = trim((string) ($entry->user?->assignedDealership?->zone?->name ?? ''));

        return $zone !== '' ? $zone : 'Sin zona asignada';
    }

    private function resolveZoneGroupKey(Model $entry): string
    {
        $zoneId = $entry->user?->assignedDealership?->zone?->getKey();

        if ($zoneId !== null) {
            return 'id:' . $zoneId;
        }

        return 'name:' . $this->resolveZoneName($entry);
    }

    private function resolveDealershipIdentifier(Model $entry): string
    {
        $dealershipId = $entry->user?->assignedDealership?->getKey();

        if ($dealershipId !== null) {
            return 'id:' . $dealershipId;
        }

        return 'name:' . Str::lower($this->resolveDealershipName($entry));
    }
}
