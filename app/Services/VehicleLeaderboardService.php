<?php

namespace App\Services;

use App\Models\SalesforceConnection;
use App\Models\VehicleLeaderboardDailySnapshot;
use App\Models\VehicleLeaderboardEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class VehicleLeaderboardService
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

    public function sync(): Collection
    {
        $this->ensureRequiredTablesExist();

        $connection = $this->getConnection();

        if (! $connection) {
            throw new RuntimeException('No hay ninguna conexion de Salesforce configurada.');
        }

        $syncedAt = now();

        $temperatureQueries = [
            'hot' => config('services.salesforce.vehicle_hot_leaderboard_soql'),
            'cold' => config('services.salesforce.vehicle_cold_leaderboard_soql'),
        ];

        $recordsByTemperature = collect($temperatureQueries)->mapWithKeys(
            fn (?string $query, string $temperature): array => [
                $temperature => $this->runLeaderboardQuery($connection, (string) $query),
            ]
        );

        $vehicleImageMap = $this->resolveVehicleImageMap(
            $connection,
            $recordsByTemperature
                ->flatten(1)
                ->map(fn (array $record): ?string => $this->extractVehicleSalesforceId($record))
                ->filter()
                ->values()
                ->unique()
        );

        $entriesByTemperature = collect($temperatureQueries)->mapWithKeys(
            fn (?string $query, string $temperature): array => [
                $temperature => $this->mapEntries(
                    $recordsByTemperature[$temperature],
                    $temperature,
                    $syncedAt,
                    $vehicleImageMap
                ),
            ]
        );

        DB::transaction(function () use ($entriesByTemperature, $connection, $syncedAt): void {
            VehicleLeaderboardEntry::query()->delete();

            foreach ($entriesByTemperature as $entries) {
                if ($entries->isNotEmpty()) {
                    VehicleLeaderboardEntry::query()->insert($entries->all());
                }
            }

            VehicleLeaderboardDailySnapshot::query()
                ->whereDate('snapshot_date', $syncedAt->toDateString())
                ->delete();

            foreach ($entriesByTemperature as $entries) {
                if ($entries->isEmpty()) {
                    continue;
                }

                VehicleLeaderboardDailySnapshot::query()->insert(
                    $entries->map(fn (array $entry): array => [
                        'snapshot_date' => $syncedAt->toDateString(),
                        'temperature' => $entry['temperature'],
                        'ranking_position' => $entry['ranking_position'],
                        'vehicle_salesforce_id' => $entry['vehicle_salesforce_id'],
                        'vehicle_name' => $entry['vehicle_name'],
                        'vehicle_commercial_name' => $entry['vehicle_commercial_name'],
                        'vehicle_plate' => $entry['vehicle_plate'],
                        'vehicle_image_url' => $entry['vehicle_image_url'],
                        'total_leads' => $entry['total_leads'],
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

        return VehicleLeaderboardEntry::query()
            ->orderBy('temperature')
            ->orderBy('ranking_position')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function runLeaderboardQuery(SalesforceConnection $connection, string $query): array
    {
        $records = [];
        $nextUrl = sprintf(
            '%s/services/data/%s/query',
            rtrim($connection->instance_url, '/'),
            self::API_VERSION
        );

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
            throw new RuntimeException('La conexion de Salesforce no tiene un access token valido.');
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

        $metadata = $connection->metadata ?? [];
        $metadata['identity_url'] = $response['id'] ?? ($metadata['identity_url'] ?? null);
        $metadata['issued_at'] = $response['issued_at'] ?? ($metadata['issued_at'] ?? null);
        $metadata['signature'] = $response['signature'] ?? ($metadata['signature'] ?? null);

        $connection->forceFill([
            'provider' => self::PROVIDER,
            'instance_url' => $response['instance_url'] ?? $connection->instance_url,
            'access_token' => $response['access_token'] ?? $connection->access_token,
            'refresh_token' => $response['refresh_token'] ?? $connection->refresh_token,
            'token_type' => $response['token_type'] ?? $connection->token_type,
            'scope' => $response['scope'] ?? $connection->scope,
            'metadata' => $metadata,
        ])->save();

        return $connection->fresh();
    }

    private function mapEntries(array $records, string $temperature, $syncedAt, Collection $vehicleImageMap): Collection
    {
        return collect($records)
            ->values()
            ->map(function (array $record, int $index) use ($temperature, $syncedAt, $vehicleImageMap): array {
                $vehicleSalesforceId = $this->extractVehicleSalesforceId($record);

                return [
                    'temperature' => $temperature,
                    'ranking_position' => $index + 1,
                    'vehicle_salesforce_id' => $vehicleSalesforceId,
                    'vehicle_name' => $this->extractVehicleName($record),
                    'vehicle_commercial_name' => $this->extractVehicleCommercialName($record),
                    'vehicle_plate' => $this->extractVehiclePlate($record),
                    'vehicle_image_url' => $vehicleSalesforceId ? $vehicleImageMap->get($vehicleSalesforceId) : null,
                    'total_leads' => $this->extractTotalLeads($record),
                    'synced_at' => $syncedAt,
                    'created_at' => $syncedAt,
                    'updated_at' => $syncedAt,
                ];
            });
    }

    private function extractVehicleSalesforceId(array $record): ?string
    {
        return $this->stringOrNull(
            $record['vehicleId']
                ?? $record['VehicleId']
                ?? $record['LEA_BUS_Vehiculo_de_interes__c']
        );
    }

    private function extractVehicleName(array $record): string
    {
        return $this->stringOrNull(
            $record['vehicleName']
                ?? $record['VehicleName']
                ?? data_get($record, 'LEA_BUS_Vehiculo_de_interes__r.Name')
                ?? $record['Name']
        ) ?? 'Vehiculo sin nombre';
    }

    private function extractVehicleCommercialName(array $record): ?string
    {
        return $this->stringOrNull(
            $record['vehicleCommercialName']
                ?? $record['VehicleCommercialName']
                ?? data_get($record, 'LEA_BUS_Vehiculo_de_interes__r.NombreComercial__c')
        );
    }

    private function extractVehiclePlate(array $record): ?string
    {
        return $this->stringOrNull(
            $record['vehiclePlate']
                ?? $record['VehiclePlate']
                ?? data_get($record, 'LEA_BUS_Vehiculo_de_interes__r.PRO_TEX_Matricula__c')
        );
    }

    private function extractTotalLeads(array $record): int
    {
        return (int) (
            $record['totalLeads']
            ?? $record['TotalLeads']
            ?? $record['expr0']
            ?? 0
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveVehicleImageMap(SalesforceConnection $connection, Collection $vehicleIds): Collection
    {
        if ($vehicleIds->isEmpty()) {
            return collect();
        }

        $quotedVehicleIds = $vehicleIds
            ->map(fn (string $id): string => "'" . str_replace("'", "\\'", $id) . "'")
            ->implode(', ');

        $documentLinks = $this->runLeaderboardQuery(
            $connection,
            "SELECT LinkedEntityId, ContentDocumentId, ContentDocument.CreatedDate
             FROM ContentDocumentLink
             WHERE LinkedEntityId IN ({$quotedVehicleIds})
             ORDER BY LinkedEntityId ASC, ContentDocument.CreatedDate ASC"
        );

        $firstDocumentByVehicle = collect($documentLinks)
            ->filter(fn (array $record): bool => filled($this->stringOrNull($record['LinkedEntityId'] ?? null)))
            ->groupBy(fn (array $record): string => (string) $record['LinkedEntityId'])
            ->map(fn (Collection $records): ?string => $this->stringOrNull($records->first()['ContentDocumentId'] ?? null))
            ->filter();

        if ($firstDocumentByVehicle->isEmpty()) {
            return collect();
        }

        $quotedDocumentIds = $firstDocumentByVehicle
            ->values()
            ->unique()
            ->map(fn (string $id): string => "'" . str_replace("'", "\\'", $id) . "'")
            ->implode(', ');

        $contentVersions = $this->runLeaderboardQuery(
            $connection,
            "SELECT Id, ContentDocumentId, FileType
             FROM ContentVersion
             WHERE ContentDocumentId IN ({$quotedDocumentIds})
             AND IsLatest = true"
        );

        $versionMap = collect($contentVersions)
            ->filter(function (array $record): bool {
                $fileType = Str::upper((string) ($record['FileType'] ?? ''));

                return in_array($fileType, ['JPG', 'JPEG', 'PNG', 'WEBP'], true);
            })
            ->mapWithKeys(function (array $record): array {
                $documentId = (string) ($record['ContentDocumentId'] ?? '');
                $versionId = $this->stringOrNull($record['Id'] ?? null);

                return $documentId !== '' && $versionId
                    ? [$documentId => $versionId]
                    : [];
            });

        $versionIds = $versionMap->values()->unique();

        if ($versionIds->isEmpty()) {
            return collect();
        }

        $quotedVersionIds = $versionIds
            ->map(fn (string $id): string => "'" . str_replace("'", "\\'", $id) . "'")
            ->implode(', ');

        $contentDistributions = $this->runLeaderboardQuery(
            $connection,
            "SELECT ContentVersionId, DistributionPublicUrl, ContentDownloadUrl
             FROM ContentDistribution
             WHERE ContentVersionId IN ({$quotedVersionIds})"
        );

        $distributionMap = collect($contentDistributions)
            ->mapWithKeys(function (array $record): array {
                $versionId = $this->stringOrNull($record['ContentVersionId'] ?? null);
                $url = $this->stringOrNull($record['DistributionPublicUrl'] ?? null)
                    ?? $this->stringOrNull($record['ContentDownloadUrl'] ?? null);

                return $versionId && $url ? [$versionId => $url] : [];
            });

        return $firstDocumentByVehicle
            ->map(function (string $documentId) use ($versionMap, $distributionMap): ?string {
                $versionId = $versionMap->get($documentId);

                return $versionId ? $distributionMap->get($versionId) : null;
            })
            ->filter();
    }

    private function ensureRequiredTablesExist(): void
    {
        if (
            ! Schema::hasTable('salesforce_connections')
            || ! Schema::hasTable('vehicle_leaderboard_entries')
            || ! Schema::hasTable('vehicle_leaderboard_daily_snapshots')
        ) {
            throw new RuntimeException('Faltan las tablas del leaderboard de vehiculos. Ejecuta las migraciones antes de conectar Salesforce.');
        }
    }
}
