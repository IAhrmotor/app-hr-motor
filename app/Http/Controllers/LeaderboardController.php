<?php

namespace App\Http\Controllers;

use App\Models\PurchaseLeaderboardDailySnapshot;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardDailySnapshot;
use App\Models\SalesLeaderboardEntry;
use App\Models\VehicleLeaderboardDailySnapshot;
use App\Models\VehicleLeaderboardEntry;
use App\Services\LeaderboardTrendService;
use App\Services\SalesforceLeaderboardService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
                'entity_label_plural' => 'comerciales',
                'search_placeholder' => 'Buscar comercial, email o delegacion',
                'show_dealership_leaderboard' => true,
                'dealership_title' => 'Ranking por delegaciones',
                'dealership_description' => 'Comparativa de ventas acumuladas por delegacion para fomentar la competicion entre equipos.',
                'dealership_empty_title' => 'Aun no hay datos de delegaciones',
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
            ],
            leaderboardTablesReady: $leaderboardTablesReady
        );
    }

    public function vehicles(Request $request, SalesforceLeaderboardService $service, LeaderboardTrendService $trendService)
    {
        $leaderboardTablesReady = Schema::hasTable('vehicle_leaderboard_entries')
            && Schema::hasTable('vehicle_leaderboard_daily_snapshots')
            && Schema::hasTable('salesforce_connections');

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

        return view('leaderboard.vehicles', [
            'connection' => $service->getConnection(),
            'salesforceConfigReady' => $salesforceConfigReady,
            'leaderboardTablesReady' => $leaderboardTablesReady,
            'emptyDescription' => $emptyDescription,
            'hotLeaderboard' => $this->buildVehicleLeaderboardViewData($request, $trendService, [
                'temperature' => 'hot',
                'title' => 'Coches calientes',
                'description' => 'Los vehiculos disponibles y en garantia que concentran mas interes comercial ahora mismo.',
                'search_param' => 'hot_search',
                'page_param' => 'hot_page',
                'route_name' => 'leaderboard.vehicles',
                'theme' => 'hot',
                'empty_title' => 'Aun no hay coches calientes',
                'search_placeholder' => 'Buscar coche caliente',
            ]),
            'coldLeaderboard' => $this->buildVehicleLeaderboardViewData($request, $trendService, [
                'temperature' => 'cold',
                'title' => 'Coches frios',
                'description' => 'Los vehiculos disponibles y en garantia con menos leads asociados en el ranking actual.',
                'search_param' => 'cold_search',
                'page_param' => 'cold_page',
                'route_name' => 'leaderboard.vehicles',
                'theme' => 'cold',
                'empty_title' => 'Aun no hay coches frios',
                'search_placeholder' => 'Buscar coche frio',
            ]),
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
            'dealershipLeaderboard' => $dealershipLeaderboard,
            'dealershipTitle' => $config['dealership_title'] ?? 'Ranking por delegaciones',
            'dealershipDescription' => $config['dealership_description'] ?? '',
            'dealershipEmptyTitle' => $config['dealership_empty_title'] ?? 'Aun no hay datos de delegaciones',
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

        if (Schema::hasTable($config['entry_table'])) {
            $allEntries = $config['entry_model']::query()
                ->with(['user.assignedDealership'])
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->orderBy('ranking_position')
                ->get();

            $aggregatedEntries = $this->aggregateEntriesByDealership($allEntries, $config['metric_field'], $search);
            $topEntries = $aggregatedEntries->take(3)->values();
            $entries = $this->paginateCollection($aggregatedEntries, 'dealership_page');
            $entryItems = collect($entries->items());
            $hasLeaderboardData = $aggregatedEntries->isNotEmpty();
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

    private function buildVehicleLeaderboardViewData(Request $request, LeaderboardTrendService $trendService, array $config): array
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

    private function excludedLeaderboardUserIds(): array
    {
        return config('services.salesforce.excluded_leaderboard_user_ids', []);
    }

    private function aggregateEntriesByDealership(Collection $entries, string $metricField, string $search): Collection
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

    private function paginateCollection(Collection $entries, string $pageParam): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage($pageParam);
        $perPage = 10;

        return new LengthAwarePaginator(
            $entries->forPage($currentPage, $perPage)->values(),
            $entries->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageParam,
            ]
        );
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
}
