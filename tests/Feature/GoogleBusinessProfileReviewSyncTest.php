<?php

namespace Tests\Feature;

use App\Models\GoogleBusinessProfileConnection;
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
                        'comment' => 'Perfecta mi compra muchisimas gracias',
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
        $this->assertSame('FIVE', $review->raw_payload['starRating']);
        $this->assertSame('Marco', $review->raw_payload['reviewer']['displayName']);
        $this->assertSame('kept', $review->raw_payload['customField']['nested']['value']);
        $this->assertSame('Gracias por tu reseña', $review->raw_payload['reviewReply']['comment']);
    }
}
