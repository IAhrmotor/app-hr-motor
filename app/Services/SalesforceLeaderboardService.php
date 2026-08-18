<?php

namespace App\Services;

use App\Models\SalesLeaderboardEntry;
use App\Models\SalesLeaderboardDailySnapshot;
use App\Models\SalesforceConnection;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SalesforceLeaderboardService
{
    private const PROVIDER = 'salesforce';

    private const API_VERSION = 'v61.0';

    public function getConnection(): ?SalesforceConnection
    {
        if (! Schema::hasTable('salesforce_connections')) {
            return null;
        }

        return SalesforceConnection::query()
            ->where('provider', self::PROVIDER)
            ->first();
    }

    public function saveAuthorizationCodeTokens(string $code): SalesforceConnection
    {
        $this->ensureRequiredTablesExist();

        $response = Http::asForm()
            ->post(config('services.salesforce.token_url'), [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.salesforce.client_id'),
                'client_secret' => config('services.salesforce.client_secret'),
                'redirect_uri' => config('services.salesforce.redirect_uri'),
                'code' => $code,
            ])
            ->throw()
            ->json();

        return $this->persistTokens($response);
    }

    public function sync(): Collection
    {
        $this->ensureRequiredTablesExist();

        $connection = $this->getConnection();

        if (! $connection) {
            throw new RuntimeException('No hay ninguna conexión de Salesforce configurada.');
        }

        $records = $this->runLeaderboardQuery($connection);
        $syncedAt = now();

        $entries = collect($records)
            ->reject(fn (array $record): bool => $this->isExcludedSalesforceUserId($this->extractSalesforceUserId($record)))
            ->map(function (array $record, int $index) use ($syncedAt): ?array {
                $salesforceUserId = $this->extractSalesforceUserId($record);
                $user = $salesforceUserId
                    ? User::query()->where('salesforce_user_id', $salesforceUserId)->first()
                    : null;

                if ($user && ! $user->isRankedCommercial()) {
                    return null;
                }

                return [
                    'ranking_position' => $index + 1,
                    'user_id' => $user?->id,
                    'salesforce_user_id' => $salesforceUserId,
                    'seller_name' => $this->extractSellerName($record, $user),
                    'total_sales' => $this->extractTotalSales($record),
                    'synced_at' => $syncedAt,
                    'created_at' => $syncedAt,
                    'updated_at' => $syncedAt,
                ];
            })
            ->filter()
            ->values();

        $entries = $this->appendCommercialUsersWithoutSales($entries, $syncedAt);
        $entries = $this->normalizeRankingPositions($entries);

        DB::transaction(function () use ($entries, $connection, $syncedAt): void {
            SalesLeaderboardEntry::query()->delete();

            if ($entries->isNotEmpty()) {
                SalesLeaderboardEntry::query()->insert($entries->all());
            }

            SalesLeaderboardDailySnapshot::query()
                ->whereDate('snapshot_date', $syncedAt->toDateString())
                ->delete();

            if ($entries->isNotEmpty()) {
                SalesLeaderboardDailySnapshot::query()->insert(
                    $entries->map(fn (array $entry): array => [
                        'snapshot_date' => $syncedAt->toDateString(),
                        'ranking_position' => $entry['ranking_position'],
                        'user_id' => $entry['user_id'],
                        'salesforce_user_id' => $entry['salesforce_user_id'],
                        'seller_name' => $entry['seller_name'],
                        'total_sales' => $entry['total_sales'],
                        'captured_at' => $syncedAt,
                        'created_at' => $syncedAt,
                        'updated_at' => $syncedAt,
                    ])->all()
                );
            }

            $connection->forceFill([
                'last_synced_at' => $syncedAt,
            ])->save();
        });

        return SalesLeaderboardEntry::query()
            ->with('user')
            ->orderBy('ranking_position')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function runLeaderboardQuery(SalesforceConnection $connection): array
    {
        $records = [];
        $nextUrl = sprintf(
            '%s/services/data/%s/query',
            rtrim($connection->instance_url, '/'),
            self::API_VERSION
        );
        $query = config('services.salesforce.leaderboard_soql');

        do {
            $response = $this->requestWithAutoRefresh($connection, function (string $accessToken) use ($nextUrl, $query) {
                $request = Http::withToken($accessToken);

                return str_contains($nextUrl, '/query/')
                    ? $request->get($nextUrl)
                    : $request->get($nextUrl, ['q' => $query]);
            });

            $payload = $response->throw()->json();
            $records = [...$records, ...($payload['records'] ?? [])];
            $nextRecordsUrl = $payload['nextRecordsUrl'] ?? null;
            $nextUrl = $nextRecordsUrl
                ? rtrim($connection->instance_url, '/') . $nextRecordsUrl
                : null;
        } while ($nextUrl);

        return $records;
    }

    private function requestWithAutoRefresh(SalesforceConnection $connection, callable $request)
    {
        $accessToken = $connection->access_token;

        if (blank($accessToken) && filled($connection->refresh_token)) {
            $connection = $this->refreshAccessToken($connection);
            $accessToken = $connection->access_token;
        }

        if (blank($accessToken)) {
            throw new RuntimeException('La conexión de Salesforce no tiene un access token válido.');
        }

        $response = $request($accessToken);

        if ($response->status() !== 401 || blank($connection->refresh_token)) {
            return $response;
        }

        $connection = $this->refreshAccessToken($connection);

        return $request($connection->access_token);
    }

    private function refreshAccessToken(SalesforceConnection $connection): SalesforceConnection
    {
        $response = Http::asForm()
            ->post(config('services.salesforce.token_url'), [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.salesforce.client_id'),
                'client_secret' => config('services.salesforce.client_secret'),
                'refresh_token' => $connection->refresh_token,
            ])
            ->throw()
            ->json();

        return $this->persistTokens($response, $connection);
    }

    private function persistTokens(array $payload, ?SalesforceConnection $connection = null): SalesforceConnection
    {
        $connection ??= SalesforceConnection::query()->firstOrNew([
            'provider' => self::PROVIDER,
        ]);

        $metadata = $connection->metadata ?? [];
        $metadata['identity_url'] = $payload['id'] ?? ($metadata['identity_url'] ?? null);
        $metadata['issued_at'] = $payload['issued_at'] ?? ($metadata['issued_at'] ?? null);
        $metadata['signature'] = $payload['signature'] ?? ($metadata['signature'] ?? null);

        $connection->forceFill([
            'provider' => self::PROVIDER,
            'instance_url' => $payload['instance_url'] ?? $connection->instance_url,
            'access_token' => $payload['access_token'] ?? $connection->access_token,
            'refresh_token' => $payload['refresh_token'] ?? $connection->refresh_token,
            'token_type' => $payload['token_type'] ?? $connection->token_type,
            'scope' => $payload['scope'] ?? $connection->scope,
            'metadata' => $metadata,
        ])->save();

        return $connection->fresh();
    }

    private function extractSalesforceUserId(array $record): ?string
    {
        return $this->stringOrNull(
            $record['ownerId']
                ?? $record['OwnerId']
                ?? data_get($record, 'Owner.Id')
        );
    }

    private function extractSellerName(array $record, ?User $user): string
    {
        return $this->stringOrNull(
            $record['ownerName']
                ?? $record['OwnerName']
                ?? data_get($record, 'Owner.Name')
                ?? $user?->name
        ) ?? 'Comercial sin nombre';
    }

    private function extractTotalSales(array $record): float
    {
        $value = $record['totalSales']
            ?? $record['TotalSales']
            ?? $record['expr0']
            ?? $record['Amount']
            ?? 0;

        return (float) $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function appendCommercialUsersWithoutSales(Collection $entries, $syncedAt): Collection
    {
        $existingUserIds = $entries
            ->pluck('user_id')
            ->filter()
            ->values()
            ->all();

        $existingSalesforceUserIds = $entries
            ->pluck('salesforce_user_id')
            ->filter()
            ->values()
            ->all();

        $missingCommercials = User::query()
            ->where('role', User::ROLE_USER)
            ->whereIn('extra_role', [User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER])
            ->where(function ($query) use ($existingUserIds, $existingSalesforceUserIds): void {
                $query->whereNotIn('id', $existingUserIds)
                    ->where(function ($userQuery) use ($existingSalesforceUserIds): void {
                        $userQuery->whereNull('salesforce_user_id')
                            ->orWhere('salesforce_user_id', '')
                            ->orWhereNotIn('salesforce_user_id', $existingSalesforceUserIds);
                    });
            })
            ->orderBy('name')
            ->get()
            ->reject(fn (User $user): bool => $this->isExcludedSalesforceUserId($user->salesforce_user_id));

        $nextRankingPosition = $entries->count() + 1;

        $missingEntries = $missingCommercials->map(function (User $user) use (&$nextRankingPosition, $syncedAt): array {
            return [
                'ranking_position' => $nextRankingPosition++,
                'user_id' => $user->id,
                'salesforce_user_id' => $user->salesforce_user_id,
                'seller_name' => $user->name,
                'total_sales' => 0,
                'synced_at' => $syncedAt,
                'created_at' => $syncedAt,
                'updated_at' => $syncedAt,
            ];
        });

        return $entries->concat($missingEntries)->values();
    }

    private function normalizeRankingPositions(Collection $entries): Collection
    {
        return $entries
            ->values()
            ->map(function (array $entry, int $index): array {
                $entry['ranking_position'] = $index + 1;

                return $entry;
            });
    }

    private function excludedLeaderboardUserIds(): array
    {
        return config('services.salesforce.excluded_leaderboard_user_ids', []);
    }

    private function isExcludedSalesforceUserId(?string $salesforceUserId): bool
    {
        return $salesforceUserId !== null
            && in_array($salesforceUserId, $this->excludedLeaderboardUserIds(), true);
    }

    private function ensureRequiredTablesExist(): void
    {
        if (
            ! Schema::hasTable('salesforce_connections')
            || ! Schema::hasTable('sales_leaderboard_entries')
            || ! Schema::hasTable('sales_leaderboard_daily_snapshots')
        ) {
            throw new RuntimeException('Faltan las tablas del leaderboard. Ejecuta las migraciones antes de conectar Salesforce.');
        }
    }
}
