<?php

namespace Tests\Feature;

use App\Models\GoogleBusinessProfileConnection;
use App\Models\Dealership;
use App\Models\GoogleBusinessProfileReview;
use App\Services\GoogleBusinessProfileReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBusinessProfileReviewSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_persists_reviews_with_nested_raw_payload_without_throwing_array_to_string_errors(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'HR Motor',
            'account_resource_name' => 'accounts/113830072386405282091',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/113830072386405282091',
                        'accountName' => 'HR Motor',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/113830072386405282091/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/113830072386405282091/locations/11867554981017401239',
                        'title' => 'HR Motor || Zaragoza',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/113830072386405282091/locations/11867554981017401239/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/113830072386405282091/locations/11867554981017401239/reviews/AbFvOqtest',
                        'reviewer' => [
                            'displayName' => 'Marco',
                            'profilePhotoUrl' => 'https://example.test/photo.jpg',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => "Perfecta mi compra muchisimas gracias\n\n(Translated by Google)\nMy purchase was perfect, thank you so much",
                        'reviewReply' => [
                            'name' => 'accounts/113830072386405282091/locations/11867554981017401239/reviews/AbFvOqtest/reply',
                            'comment' => 'Gracias por tu reseña',
                            'updateTime' => '2026-05-04T16:14:34Z',
                        ],
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                        'customField' => [
                            'nested' => [
                                'value' => 'kept',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(1, $reviews);

        $review = GoogleBusinessProfileReview::query()->firstOrFail();

        $this->assertSame('accounts/113830072386405282091/locations/11867554981017401239/reviews/AbFvOqtest', $review->review_name);
        $this->assertIsArray($review->raw_payload);
        $this->assertSame('Perfecta mi compra muchisimas gracias', $review->raw_payload['comment']);
        $this->assertSame('Perfecta mi compra muchisimas gracias', $review->comment);
        $this->assertSame('FIVE', $review->raw_payload['starRating']);
        $this->assertSame('Marco', $review->raw_payload['reviewer']['displayName']);
        $this->assertSame('kept', $review->raw_payload['customField']['nested']['value']);
        $this->assertSame('Gracias por tu reseña', $review->raw_payload['reviewReply']['comment']);
    }
    public function test_sync_for_a_single_dealership_only_fetches_that_dealership_reviews(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'HR Motor',
            'account_resource_name' => 'accounts/113830072386405282091',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor || Zaragoza',
            'google_business_profile_location_name' => 'accounts/113830072386405282091/locations/11867554981017401239',
            'google_business_profile_location_title' => 'HR Motor || Zaragoza',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/113830072386405282091',
                        'accountName' => 'HR Motor',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/113830072386405282091/locations/11867554981017401239/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/113830072386405282091/locations/11867554981017401239/reviews/AbFvOqsingle',
                        'reviewer' => [
                            'displayName' => 'Laura',
                        ],
                        'starRating' => 'FOUR',
                        'comment' => 'Muy buena atencion',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync($dealership);

        $this->assertCount(1, $reviews);
        $this->assertSame($dealership->id, $reviews->firstOrFail()->dealership_id);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/113830072386405282091/locations/11867554981017401239/reviews/AbFvOqsingle',
            'dealership_id' => $dealership->id,
        ]);
    }

    public function test_sync_skips_locations_that_refer_to_salamanca(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/117678944517959788740',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/117678944517959788740',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/117678944517959788740/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/zaragoza',
                        'title' => 'HR Motor || Zaragoza',
                    ],
                    [
                        'name' => 'accounts/117678944517959788740/locations/salamanca',
                        'title' => 'HR Motor || Salamanca',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/zaragoza/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/visible-review',
                        'reviewer' => [
                            'displayName' => 'Visible',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Visible review',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/salamanca/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/salamanca/reviews/hidden-review',
                        'reviewer' => [
                            'displayName' => 'Hidden',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Hidden review',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(1, $reviews);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/zaragoza/reviews/visible-review',
        ]);
        $this->assertDatabaseMissing('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/salamanca/reviews/hidden-review',
        ]);
        Http::assertNotSent(function ($request): bool {
            return str_contains($request->url(), '/locations/salamanca/reviews');
        });
    }

    public function test_sync_prefers_location_group_account_when_multiple_accounts_match_the_same_name(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/111',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/111',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'ORGANIZATION',
                    ],
                    [
                        'name' => 'accounts/222',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/222/locations*' => Http::response([
                'locations' => [],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/111/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/111/locations/should-not-be-used',
                        'title' => 'Wrong account',
                    ],
                ],
            ]),
        ]);

        app(GoogleBusinessProfileReviewService::class)->sync();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/222/locations');
        });
    }

    public function test_sync_can_match_a_dealership_by_location_locality_when_the_title_is_generic(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/117678944517959788740',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'HR Motor || Bilbao',
            'google_maps_url' => 'https://maps.google.com/?q=bilbao',
            'reviews_url' => 'https://example.com/resenas/bilbao',
            'phone' => '+34 000 000 000',
            'salesforce_id' => 'sf-bilbao',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/117678944517959788740',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/117678944517959788740/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/bilbao',
                        'title' => 'HR Motor',
                        'storefrontAddress' => [
                            'locality' => 'Bilbao',
                            'administrativeArea' => 'Bizkaia',
                            'postalCode' => '48001',
                        ],
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/bilbao/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/bilbao/reviews/visible-by-locality',
                        'reviewer' => [
                            'displayName' => 'Cliente',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Muy buena atención',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(1, $reviews);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/bilbao/reviews/visible-by-locality',
            'dealership_id' => $dealership->id,
        ]);
        $this->assertSame('accounts/117678944517959788740/locations/bilbao', $dealership->fresh()->google_business_profile_location_name);
        $this->assertSame('HR Motor', $dealership->fresh()->google_business_profile_location_title);
    }

    public function test_sync_prefers_the_location_title_over_a_broader_province_match_when_the_name_is_close_but_not_exact(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/117678944517959788740',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        $dealership = Dealership::query()->create([
            'name' => 'Villareal/Almassora',
            'google_maps_url' => 'https://maps.google.com/?q=villarreal',
            'reviews_url' => 'https://example.com/resenas/villarreal',
            'phone' => '+34 000 000 001',
            'salesforce_id' => 'sf-villarreal',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/117678944517959788740',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/117678944517959788740/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/villarreal',
                        'title' => 'HR Motor || Villarreal',
                        'storefrontAddress' => [
                            'locality' => 'Villarreal',
                            'administrativeArea' => 'Castellón',
                            'postalCode' => '12540',
                        ],
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/villarreal/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/villarreal/reviews/visible-villarreal',
                        'reviewer' => [
                            'displayName' => 'Cliente',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Muy buena atención',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(1, $reviews);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/villarreal/reviews/visible-villarreal',
            'dealership_id' => $dealership->id,
        ]);
        $this->assertSame('accounts/117678944517959788740/locations/villarreal', $dealership->fresh()->google_business_profile_location_name);
        $this->assertSame('HR Motor || Villarreal', $dealership->fresh()->google_business_profile_location_title);
    }

    public function test_sync_prefers_exact_location_title_matches_over_broader_city_or_province_matches(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/117678944517959788740',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        $elche = Dealership::query()->create([
            'name' => 'Elche',
            'google_maps_url' => 'https://maps.google.com/?q=elche',
            'reviews_url' => 'https://example.com/resenas/elche',
            'phone' => '+34 000 000 002',
            'salesforce_id' => 'sf-elche',
        ]);

        $malagaCentro = Dealership::query()->create([
            'name' => 'Malaga Centro',
            'google_maps_url' => 'https://maps.google.com/?q=malaga-centro',
            'reviews_url' => 'https://example.com/resenas/malaga-centro',
            'phone' => '+34 000 000 003',
            'salesforce_id' => 'sf-malaga-centro',
        ]);

        $alcoy = Dealership::query()->create([
            'name' => 'Alcoy',
            'google_maps_url' => 'https://maps.google.com/?q=alcoy',
            'reviews_url' => 'https://example.com/resenas/alcoy',
            'phone' => '+34 000 000 004',
            'salesforce_id' => 'sf-alcoy',
        ]);

        $provinceMatch = Dealership::query()->create([
            'name' => 'Alicante',
            'google_maps_url' => 'https://maps.google.com/?q=alicante',
            'reviews_url' => 'https://example.com/resenas/alicante',
            'phone' => '+34 000 000 005',
            'salesforce_id' => 'sf-alicante',
        ]);

        $malaga = Dealership::query()->create([
            'name' => 'Malaga',
            'google_maps_url' => 'https://maps.google.com/?q=malaga',
            'reviews_url' => 'https://example.com/resenas/malaga',
            'phone' => '+34 000 000 006',
            'salesforce_id' => 'sf-malaga',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/117678944517959788740',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/117678944517959788740/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/elche',
                        'title' => 'HR Motor || Elche',
                        'storefrontAddress' => [
                            'locality' => 'Elche',
                            'administrativeArea' => 'Alicante',
                            'postalCode' => '03201',
                        ],
                    ],
                    [
                        'name' => 'accounts/117678944517959788740/locations/malaga-centro',
                        'title' => 'HR Motor || Málaga Centro',
                        'storefrontAddress' => [
                            'locality' => 'Málaga',
                            'administrativeArea' => 'Málaga',
                            'postalCode' => '29001',
                        ],
                    ],
                    [
                        'name' => 'accounts/117678944517959788740/locations/alcoy',
                        'title' => 'HR Motor || Alcoy',
                        'storefrontAddress' => [
                            'locality' => 'Alcoy',
                            'administrativeArea' => 'Alicante',
                            'postalCode' => '03801',
                        ],
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/elche/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/elche/reviews/elche-review',
                        'reviewer' => [
                            'displayName' => 'Cliente Elche',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Muy buena atención en Elche',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/malaga-centro/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-review',
                        'reviewer' => [
                            'displayName' => 'Cliente Málaga',
                        ],
                        'starRating' => 'FOUR',
                        'comment' => 'Muy buena atención en Málaga Centro',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/alcoy/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/alcoy/reviews/alcoy-review',
                        'reviewer' => [
                            'displayName' => 'Cliente Alcoy',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Muy buena atención en Alcoy',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(3, $reviews);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/elche/reviews/elche-review',
            'dealership_id' => $elche->id,
        ]);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-review',
            'dealership_id' => $malagaCentro->id,
        ]);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/alcoy/reviews/alcoy-review',
            'dealership_id' => $alcoy->id,
        ]);
        $this->assertDatabaseMissing('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/elche/reviews/elche-review',
            'dealership_id' => $provinceMatch->id,
        ]);
        $this->assertDatabaseMissing('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-review',
            'dealership_id' => $malaga->id,
        ]);
    }

    public function test_sync_ignores_stale_google_business_profile_fields_when_matching_dealerships(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/117678944517959788740',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        $madridLike = Dealership::query()->create([
            'name' => 'Alcobendas',
            'google_business_profile_location_title' => 'HR Motor || Málaga',
            'google_maps_url' => 'https://maps.google.com/?q=alcobendas',
            'reviews_url' => 'https://example.com/resenas/alcobendas',
            'phone' => '+34 000 000 007',
            'salesforce_id' => 'sf-alcobendas',
        ]);

        $malagaCentro = Dealership::query()->create([
            'name' => 'Málaga Centro',
            'google_maps_url' => 'https://maps.google.com/?q=malaga-centro',
            'reviews_url' => 'https://example.com/resenas/malaga-centro',
            'phone' => '+34 000 000 003',
            'salesforce_id' => 'sf-malaga-centro-2',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/117678944517959788740',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/117678944517959788740/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/malaga-centro',
                        'title' => 'HR Motor || Málaga Centro',
                        'storefrontAddress' => [
                            'locality' => 'Málaga',
                            'administrativeArea' => 'Málaga',
                            'postalCode' => '29001',
                        ],
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/malaga-centro/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-centro-review',
                        'reviewer' => [
                            'displayName' => 'Cliente Málaga Centro',
                        ],
                        'starRating' => 'FOUR',
                        'comment' => 'Buena atención en Málaga Centro',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(1, $reviews);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-centro-review',
            'dealership_id' => $malagaCentro->id,
        ]);
        $this->assertDatabaseMissing('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-centro-review',
            'dealership_id' => $madridLike->id,
        ]);
    }

    public function test_sync_explicitly_matches_malaga_centro_to_the_malaga_centro_dealership(): void
    {
        config()->set('services.google_business_profile.account_group_name', 'Tiendas HR Motor');

        GoogleBusinessProfileConnection::query()->create([
            'provider' => 'google_business_profile',
            'account_name' => 'Tiendas HR Motor',
            'account_resource_name' => 'accounts/117678944517959788740',
            'access_token' => 'dummy-access-token',
            'refresh_token' => 'dummy-refresh-token',
            'token_type' => 'Bearer',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'metadata' => [],
        ]);

        $malaga = Dealership::query()->create([
            'name' => 'Málaga',
            'google_maps_url' => 'https://maps.google.com/?q=malaga',
            'reviews_url' => 'https://example.com/resenas/malaga',
            'phone' => '+34 000 000 008',
            'salesforce_id' => 'sf-malaga-3',
        ]);

        $malagaCentro = Dealership::query()->create([
            'name' => 'Málaga Centro',
            'google_maps_url' => 'https://maps.google.com/?q=malaga-centro',
            'reviews_url' => 'https://example.com/resenas/malaga-centro',
            'phone' => '+34 000 000 009',
            'salesforce_id' => 'sf-malaga-centro-3',
        ]);

        Http::fake([
            'https://mybusinessaccountmanagement.googleapis.com/v1/accounts*' => Http::response([
                'accounts' => [
                    [
                        'name' => 'accounts/117678944517959788740',
                        'accountName' => 'Tiendas HR Motor',
                        'type' => 'LOCATION_GROUP',
                    ],
                ],
            ]),
            'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/117678944517959788740/locations*' => Http::response([
                'locations' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/malaga-centro',
                        'title' => 'HR Motor || Málaga Centro',
                        'storefrontAddress' => [
                            'locality' => 'Málaga',
                            'administrativeArea' => 'Málaga',
                            'postalCode' => '29001',
                        ],
                    ],
                ],
            ]),
            'https://mybusiness.googleapis.com/v4/accounts/117678944517959788740/locations/malaga-centro/reviews*' => Http::response([
                'reviews' => [
                    [
                        'name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-centro-review',
                        'reviewer' => [
                            'displayName' => 'Cliente Málaga Centro',
                        ],
                        'starRating' => 'FIVE',
                        'comment' => 'Muy buena atención en Málaga Centro',
                        'createTime' => '2026-05-04T11:24:53Z',
                        'updateTime' => '2026-05-04T11:31:16Z',
                    ],
                ],
            ]),
        ]);

        $reviews = app(GoogleBusinessProfileReviewService::class)->sync();

        $this->assertCount(1, $reviews);
        $this->assertDatabaseHas('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-centro-review',
            'dealership_id' => $malagaCentro->id,
        ]);
        $this->assertDatabaseMissing('google_business_profile_reviews', [
            'review_name' => 'accounts/117678944517959788740/locations/malaga-centro/reviews/malaga-centro-review',
            'dealership_id' => $malaga->id,
        ]);
    }
}
