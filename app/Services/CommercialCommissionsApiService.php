<?php

namespace App\Services;

use App\Exceptions\CommercialCommissionsApiUnavailableException;
use App\Exceptions\CommercialCommissionsNetworkException;
use App\Exceptions\CommercialCommissionsRateLimitException;
use App\Exceptions\CommercialNotFoundException;
use App\Exceptions\InvalidCommercialCommissionParametersException;
use App\Exceptions\InvalidCommercialCommissionsResponseException;
use App\Exceptions\MissingSalesforceUserIdException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CommercialCommissionsApiService
{
    /**
     * @return array{commercial_id:?string, month:string, month_label:?string, economic_status:mixed, has_data:bool, final_commission:?float, row:?array}
     */
    public function get(User|string $userOrSalesforceId, string $month): array
    {
        $salesforceUserId = $userOrSalesforceId instanceof User
            ? $userOrSalesforceId->salesforce_user_id
            : $userOrSalesforceId;

        if (! is_string($salesforceUserId) || trim($salesforceUserId) === '') {
            throw new MissingSalesforceUserIdException();
        }

        $this->validateMonth($month);

        $url = config('services.commercial_commissions.url');
        $username = config('services.commercial_commissions.username');
        $password = config('services.commercial_commissions.password');
        $timeout = max(1, (int) config('services.commercial_commissions.timeout', 15));

        if (! is_string($url) || trim($url) === '' || ! is_string($username) || ! is_string($password)) {
            Log::error('Commercial commissions API is not configured.');

            throw new CommercialCommissionsApiUnavailableException();
        }

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout($timeout)
                ->get($url, [
                    'salesforce_id' => trim($salesforceUserId),
                    'month' => $month,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Commercial commissions API connection failed.', [
                'exception' => $exception::class,
            ]);

            throw new CommercialCommissionsNetworkException(previous: $exception);
        }

        return $this->normalizeResponse($response, $month);
    }

    private function validateMonth(string $month): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new InvalidCommercialCommissionParametersException('El mes debe tener el formato YYYY-MM.');
        }

        try {
            $requestedMonth = CarbonImmutable::createFromFormat('!Y-m', $month);
        } catch (\Throwable) {
            throw new InvalidCommercialCommissionParametersException('El mes indicado no es válido.');
        }

        if (! $requestedMonth || $requestedMonth->startOfMonth()->greaterThan(CarbonImmutable::now()->startOfMonth())) {
            throw new InvalidCommercialCommissionParametersException('No se pueden consultar meses futuros.');
        }
    }

    private function normalizeResponse(Response $response, string $requestedMonth): array
    {
        if ($response->status() === 404) {
            throw new CommercialNotFoundException();
        }

        if ($response->status() === 422) {
            throw new InvalidCommercialCommissionParametersException();
        }

        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');
            $retryAfterSeconds = is_numeric($retryAfter) ? max(0, (int) $retryAfter) : null;

            throw new CommercialCommissionsRateLimitException($retryAfterSeconds);
        }

        if ($response->status() === 503) {
            throw new CommercialCommissionsApiUnavailableException();
        }

        if ($response->failed()) {
            Log::warning('Commercial commissions API returned an unexpected status.', [
                'status' => $response->status(),
            ]);

            throw new CommercialCommissionsApiUnavailableException();
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new InvalidCommercialCommissionsResponseException();
        }

        $row = data_get($payload, 'row');
        $hasData = data_get($payload, 'has_data');

        if (! is_bool($hasData)) {
            $hasData = is_array($row);
        }

        if ($row !== null && ! is_array($row)) {
            throw new InvalidCommercialCommissionsResponseException();
        }

        $finalCommission = data_get($row, 'final_commission');

        if ($hasData && $finalCommission !== null && ! is_numeric($finalCommission)) {
            throw new InvalidCommercialCommissionsResponseException();
        }

        return [
            'commercial_id' => data_get($payload, 'commercial_id'),
            'month' => data_get($payload, 'month', $requestedMonth),
            'month_label' => data_get($payload, 'month_label'),
            'economic_status' => data_get($payload, 'economic_status'),
            'has_data' => $hasData,
            'final_commission' => ! $hasData || $finalCommission === null ? null : (float) $finalCommission,
            'row' => $row,
        ];
    }
}
