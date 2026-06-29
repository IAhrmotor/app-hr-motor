<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\GoogleBusinessProfileConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBusinessProfileLatestReviewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_latest_reviews_from_google_with_pagination(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'HR Motor',
            'account_resource_name' => 'accounts/123',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/123/locations/zaragoza',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/123',
                        'accountName' => 'HR Motor',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/123/locations/zaragoza/reviews*' => Http::sequence()
                ->push([
                    'reviews' => [
                        [
                            'name' => 'reviews/3',
                            'reviewer' => ['displayName' => 'Luis'],
                            'starRating' => 'FIVE',
                            'comment' => 'Genial',
                            'createTime' => '2026-06-03T10:00:00Z',
                            'updateTime' => '2026-06-03T10:00:00Z',
                        ],
                        [
                            'name' => 'reviews/2',
                            'reviewer' => ['displayName' => 'Ana'],
                            'starRating' => 'FOUR',
                            'comment' => 'Bien',
                            'createTime' => '2026-06-02T10:00:00Z',
                            'updateTime' => '2026-06-02T10:00:00Z',
                        ],
                    ],
                    'nextPageToken' => 'page-2',
                ])
                ->push([
                    'reviews' => [
                        [
                            'name' => 'reviews/1',
                            'reviewer' => ['displayName' => 'Marta'],
                            'starRating' => 'THREE',
                            'comment' => 'Normal',
                            'createTime' => '2026-06-01T10:00:00Z',
                            'updateTime' => '2026-06-01T10:00:00Z',
                        ],
                    ],
                ]),
        ]);

        $this->artisan('google-business-profile:latest-reviews', [
            'location' => 'HR Motor || Zaragoza',
            '--limit' => 3,
        ])
            ->expectsOutput('Delegacion: HR Motor || Zaragoza')
            ->expectsOutput('Limite: 3')
            ->expectsOutput('Resenas: 3')
            ->expectsOutput('Media: 4.00')
            ->expectsOutputToContain('1. 2026-06-03 10:00:00 | 5 | Luis | Genial')
            ->expectsOutputToContain('2. 2026-06-02 10:00:00 | 4 | Ana | Bien')
            ->expectsOutputToContain('3. 2026-06-01 10:00:00 | 3 | Marta | Normal')
            ->assertExitCode(0);
    }
}
