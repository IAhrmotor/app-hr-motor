<?php

namespace Tests\Feature;

use App\Models\User;
use App\Exceptions\MissingSalesforceUserIdException;
use App\Services\CommercialCommissionsApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CommercialCommissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set([
            'services.commercial_commissions.url' => 'https://informes.test/api/comisiones_comercial',
            'services.commercial_commissions.username' => 'api-user',
            'services.commercial_commissions.password' => 'api-password',
            'services.commercial_commissions.timeout' => 7,
        ]);
    }

    public function test_commercial_can_query_own_commission_and_request_contains_basic_auth_and_parameters(): void
    {
        $commercial = $this->commercial();

        Http::fake([
            '*' => Http::response([
                'commercial_id' => $commercial->salesforce_user_id,
                'month' => '2026-07',
                'month_label' => 'Julio 2026',
                'economic_status' => 'ok',
                'has_data' => true,
                'row' => ['final_commission' => 1250.50],
            ]),
        ]);

        $response = $this->actingAs($commercial)->get(route('profile.commissions', ['month' => '2026-07']));

        $response->assertOk()->assertJsonPath('data.final_commission', 1250.5);
        Http::assertSent(function ($request) use ($commercial): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->url() === 'https://informes.test/api/comisiones_comercial?salesforce_id='
                . urlencode($commercial->salesforce_user_id) . '&month=2026-07'
                && $query['salesforce_id'] === $commercial->salesforce_user_id
                && $query['month'] === '2026-07'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('api-user:api-password'));
        });
    }

    public function test_commercial_profiles_can_query_only_their_own_commissions(): void
    {
        $profiles = [
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
            User::ROLE_AREA_MANAGER,
        ];

        $users = collect($profiles)->map(function (string $extraRole, int $index): User {
            return $this->commercial([
                'email' => "commission-profile-{$index}@example.com",
                'extra_role' => $extraRole,
                'salesforce_user_id' => "SF-PROFILE-{$index}",
            ]);
        });

        Http::fake(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return Http::response([
                'commercial_id' => $query['salesforce_id'],
                'month' => $query['month'],
                'has_data' => true,
                'row' => ['final_commission' => 10],
            ]);
        });

        foreach ($users as $user) {
            $this->actingAs($user)
                ->get(route('profile.commissions', ['month' => '2026-07']))
                ->assertOk()
                ->assertJsonPath('data.commercial_id', $user->salesforce_user_id);
        }

        Http::assertSentCount(3);
        foreach ($users as $user) {
            Http::assertSent(function ($request) use ($user): bool {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return $query['salesforce_id'] === $user->salesforce_user_id
                    && $query['month'] === '2026-07';
            });
        }
    }

    public function test_hr_newcars_can_query_own_commissions(): void
    {
        $user = $this->commercial(['extra_role' => User::ROLE_HR_NEWCARS]);

        Http::fake(['*' => Http::response([
            'commercial_id' => $user->salesforce_user_id,
            'month' => '2026-07',
            'has_data' => true,
            'row' => ['final_commission' => 10],
        ])]);

        $this->actingAs($user)
            ->get(route('profile.commissions', ['month' => '2026-07']))
            ->assertOk()
            ->assertJsonPath('data.commercial_id', $user->salesforce_user_id);
    }

    public function test_only_own_commission_route_exists(): void
    {
        $this->assertTrue(Route::has('profile.commissions'));
        $this->assertFalse(Route::has('users.commissions'));

        $this->actingAs($this->commercial())
            ->get('/usuarios/999999/comisiones')
            ->assertNotFound();
    }

    public function test_admin_cannot_query_own_commissions(): void
    {
        Http::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('profile.commissions'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_manager_and_other_roles_cannot_query_commissions(): void
    {
        $commercial = $this->commercial();
        Http::fake();

        foreach ([
            ['role' => User::ROLE_MANAGER],
            ['role' => User::ROLE_MANAGEMENT],
            ['role' => User::ROLE_USER, 'extra_role' => User::ROLE_HUMAN_RESOURCES],
        ] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))
                ->get(route('profile.commissions'))
                ->assertForbidden();
        }

        Http::assertNothingSent();
    }

    public function test_no_data_is_distinguished_from_zero_commission(): void
    {
        $commercial = $this->commercial();

        Http::fakeSequence()
            ->push($this->payload($commercial, false, null))
            ->push($this->payload($commercial, true, 0));

        $this->actingAs($commercial)
            ->get(route('profile.commissions'))
            ->assertOk()
            ->assertJsonPath('data.has_data', false)
            ->assertJsonPath('data.final_commission', null);

        $this->actingAs($commercial)
            ->get(route('profile.commissions'))
            ->assertOk()
            ->assertJsonPath('data.has_data', true)
            ->assertJsonPath('data.final_commission', 0);
    }

    public function test_error_statuses_are_mapped_correctly(): void
    {
        $commercial = $this->commercial();

        Http::fakeSequence()
            ->push([], 404)
            ->push([], 422)
            ->push([], 429, ['Retry-After' => '12'])
            ->push([], 503);

        foreach ([404 => 404, 422 => 422, 429 => 429, 503 => 503] as $apiStatus => $expectedStatus) {

            $response = $this->actingAs($commercial)
                ->get(route('profile.commissions', ['month' => '2026-07']))
                ->assertStatus($expectedStatus);

            if ($apiStatus === 429) {
                $response->assertHeader('Retry-After', '12');
            }
        }
    }

    public function test_service_rejects_missing_salesforce_id_without_http_request(): void
    {
        $commercial = $this->commercial(['salesforce_user_id' => null]);
        Http::fake();

        try {
            app(CommercialCommissionsApiService::class)->get($commercial, '2026-07');
            $this->fail('Expected MissingSalesforceUserIdException was not thrown.');
        } catch (MissingSalesforceUserIdException) {
            Http::assertNothingSent();
        }
    }

    public function test_invalid_and_future_months_do_not_send_http_request(): void
    {
        $commercial = $this->commercial();
        Http::fake();

        $this->actingAs($commercial)
            ->get(route('profile.commissions', ['month' => '2026-13']))
            ->assertStatus(422);

        $this->actingAs($commercial)
            ->get(route('profile.commissions', ['month' => now()->addMonth()->format('Y-m')]))
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_legacy_response_is_processed_tolerantly(): void
    {
        $commercial = $this->commercial();

        Http::fake(['*' => Http::response([
            'month' => '2026-05',
            'has_data' => true,
            'row' => ['final_commission' => '99.90'],
        ])]);

        $this->actingAs($commercial)
            ->get(route('profile.commissions', ['month' => '2026-05']))
            ->assertOk()
            ->assertJsonPath('data.month', '2026-05')
            ->assertJsonPath('data.final_commission', 99.9);
    }

    private function commercial(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-COMMERCIAL-001',
        ], $overrides));
    }

    private function payload(User $commercial, bool $hasData, mixed $commission): array
    {
        return [
            'commercial_id' => $commercial->salesforce_user_id,
            'month' => '2026-07',
            'month_label' => 'Julio 2026',
            'economic_status' => 'ok',
            'has_data' => $hasData,
            'row' => $hasData ? ['final_commission' => $commission] : null,
        ];
    }
}
