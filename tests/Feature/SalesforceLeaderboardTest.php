<?php

namespace Tests\Feature;

use App\Models\PurchaseLeaderboardDailySnapshot;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardDailySnapshot;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SalesforceLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_is_redirected_from_generic_leaderboard_route_to_sales_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('leaderboard.index'));

        $response->assertRedirect(route('leaderboard.sales'));
    }

    public function test_authenticated_user_can_open_sales_leaderboard_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('leaderboard.sales'));

        $response
            ->assertOk()
            ->assertSee('Ranking de ventas');
    }

    public function test_authenticated_user_can_open_purchase_leaderboard_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('leaderboard.purchases'));

        $response
            ->assertOk()
            ->assertSee('Ranking de compras');
    }

    public function test_sales_leaderboard_shows_dealership_instead_of_salesforce_id(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $commercial = User::factory()->create([
            'name' => 'Comercial Delegacion',
            'dealership' => 'Sevilla',
            'salesforce_user_id' => 'SF-DEAL-001',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-DEAL-001',
            'seller_name' => 'Comercial Delegacion',
            'total_sales' => 15,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.sales'));

        $response
            ->assertOk()
            ->assertSee('Delegación')
            ->assertSee('Sevilla')
            ->assertDontSee('ID Salesforce');
    }

    public function test_admin_can_start_salesforce_oauth_flow(): void
    {
        config()->set('services.salesforce.client_id', 'client-id');
        config()->set('services.salesforce.redirect_uri', 'https://staging.hrmotor.com/integraciones/salesforce/callback');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('salesforce.connect'));

        $location = $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertNotNull($location);
        $this->assertStringContainsString('https://login.salesforce.com/services/oauth2/authorize', $location);
        $this->assertStringContainsString('client_id=client-id', $location);
        $this->assertStringContainsString(urlencode('https://staging.hrmotor.com/integraciones/salesforce/callback'), $location);
    }

    public function test_callback_persists_connection_and_syncs_leaderboard(): void
    {
        config()->set('services.salesforce.client_id', 'client-id');
        config()->set('services.salesforce.client_secret', 'client-secret');
        config()->set('services.salesforce.redirect_uri', 'https://staging.hrmotor.com/integraciones/salesforce/callback');
        config()->set(
            'services.salesforce.leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, SUM(Amount) totalSales FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );
        config()->set(
            'services.salesforce.purchase_leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalPurchases FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $commercial = User::factory()->create([
            'role' => 'comercial',
            'salesforce_user_id' => '005xx0000000001AAA',
            'email' => 'comercial@example.com',
        ]);

        Http::fake([
            'https://login.salesforce.com/services/oauth2/token' => Http::sequence()
                ->push([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'instance_url' => 'https://example.my.salesforce.com',
                    'token_type' => 'Bearer',
                    'scope' => 'api refresh_token',
                ], 200),
            'https://example.my.salesforce.com/*' => Http::response([
                'records' => [
                    [
                        'ownerId' => '005xx0000000001AAA',
                        'ownerName' => 'Laura Ventas',
                        'totalSales' => 25000.75,
                    ],
                    [
                        'ownerId' => '005xx0000000002AAA',
                        'ownerName' => 'Carlos Cierre',
                        'totalSales' => 18000,
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->withSession(['salesforce_oauth_state' => 'known-state'])
            ->actingAs($admin)
            ->get(route('salesforce.callback', [
                'code' => 'oauth-code',
                'state' => 'known-state',
            ]));

        $response
            ->assertRedirect(route('leaderboard.sales'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('salesforce_connections', [
            'provider' => 'salesforce',
            'instance_url' => 'https://example.my.salesforce.com',
        ]);

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'salesforce_user_id' => '005xx0000000001AAA',
            'seller_name' => 'Laura Ventas',
        ]);

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'ranking_position' => 2,
            'salesforce_user_id' => '005xx0000000002AAA',
            'seller_name' => 'Carlos Cierre',
        ]);

        $this->assertDatabaseHas('sales_leaderboard_daily_snapshots', [
            'snapshot_date' => now()->toDateString(),
            'ranking_position' => 1,
            'salesforce_user_id' => '005xx0000000001AAA',
        ]);

        $this->assertDatabaseHas('purchase_leaderboard_entries', [
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'salesforce_user_id' => '005xx0000000001AAA',
            'seller_name' => 'Laura Ventas',
            'total_purchases' => 25000.75,
        ]);

        $this->assertDatabaseHas('purchase_leaderboard_daily_snapshots', [
            'snapshot_date' => now()->toDateString(),
            'ranking_position' => 1,
            'salesforce_user_id' => '005xx0000000001AAA',
        ]);
    }

    public function test_leaderboard_shows_rank_movement_against_previous_day(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $commercial = User::factory()->create([
            'name' => 'Comercial Escalador',
            'salesforce_user_id' => 'SF-UP-001',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-UP-001',
            'seller_name' => 'Nombre Salesforce',
            'total_sales' => 10,
            'synced_at' => now(),
        ]);

        SalesLeaderboardDailySnapshot::query()->create([
            'snapshot_date' => today()->subDay(),
            'ranking_position' => 3,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-UP-001',
            'seller_name' => 'Nombre Salesforce',
            'total_sales' => 8,
            'captured_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.sales'));

        $response
            ->assertOk()
            ->assertSee('Sube 2 puestos')
            ->assertSee('#1');
    }

    public function test_purchase_leaderboard_shows_rank_movement_against_previous_day(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $commercial = User::factory()->create([
            'name' => 'Comprador Escalador',
            'salesforce_user_id' => 'SF-BUY-001',
        ]);

        PurchaseLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-BUY-001',
            'seller_name' => 'Nombre Salesforce Compra',
            'total_purchases' => 7,
            'synced_at' => now(),
        ]);

        PurchaseLeaderboardDailySnapshot::query()->create([
            'snapshot_date' => today()->subDay(),
            'ranking_position' => 4,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-BUY-001',
            'seller_name' => 'Nombre Salesforce Compra',
            'total_purchases' => 4,
            'captured_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.purchases'));

        $response
            ->assertOk()
            ->assertSee('Ranking de compras')
            ->assertSee('Sube 3 puestos');
    }

    public function test_sync_adds_commercial_users_without_sales_as_zero_sales_rows(): void
    {
        config()->set('services.salesforce.client_id', 'client-id');
        config()->set('services.salesforce.client_secret', 'client-secret');
        config()->set('services.salesforce.redirect_uri', 'https://staging.hrmotor.com/integraciones/salesforce/callback');
        config()->set(
            'services.salesforce.leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalSales FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );
        config()->set(
            'services.salesforce.purchase_leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalPurchases FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $commercialWithSales = User::factory()->create([
            'role' => 'comercial',
            'name' => 'Comercial con ventas',
            'salesforce_user_id' => 'SF-001',
        ]);

        $commercialWithoutSales = User::factory()->create([
            'role' => 'comercial',
            'name' => 'Comercial sin ventas',
            'salesforce_user_id' => 'SF-999',
        ]);

        Http::fake([
            'https://login.salesforce.com/services/oauth2/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'instance_url' => 'https://example.my.salesforce.com',
                'token_type' => 'Bearer',
                'scope' => 'api refresh_token',
            ], 200),
            'https://example.my.salesforce.com/*' => Http::response([
                'records' => [
                    [
                        'ownerId' => 'SF-001',
                        'ownerName' => 'Nombre SF con ventas',
                        'totalSales' => 5,
                    ],
                ],
            ], 200),
        ]);

        $this->withSession(['salesforce_oauth_state' => 'known-state'])
            ->actingAs($admin)
            ->get(route('salesforce.callback', [
                'code' => 'oauth-code',
                'state' => 'known-state',
            ]));

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'ranking_position' => 1,
            'user_id' => $commercialWithSales->id,
            'salesforce_user_id' => 'SF-001',
            'total_sales' => 5,
        ]);

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'ranking_position' => 2,
            'user_id' => $commercialWithoutSales->id,
            'salesforce_user_id' => 'SF-999',
            'seller_name' => 'Comercial sin ventas',
            'total_sales' => 0,
        ]);

        $this->assertDatabaseHas('purchase_leaderboard_entries', [
            'ranking_position' => 2,
            'user_id' => $commercialWithoutSales->id,
            'salesforce_user_id' => 'SF-999',
            'seller_name' => 'Comercial sin ventas',
            'total_purchases' => 0,
        ]);
    }

    public function test_sync_excludes_configured_salesforce_user_from_rankings(): void
    {
        config()->set('services.salesforce.client_id', 'client-id');
        config()->set('services.salesforce.client_secret', 'client-secret');
        config()->set('services.salesforce.redirect_uri', 'https://staging.hrmotor.com/integraciones/salesforce/callback');
        config()->set('services.salesforce.excluded_leaderboard_user_ids', ['0057R00000B2SGHQA3']);
        config()->set(
            'services.salesforce.leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalSales FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );
        config()->set(
            'services.salesforce.purchase_leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalPurchases FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        User::factory()->create([
            'role' => 'comercial',
            'name' => 'Usuario Excluido',
            'salesforce_user_id' => '0057R00000B2SGHQA3',
        ]);

        Http::fake([
            'https://login.salesforce.com/services/oauth2/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'instance_url' => 'https://example.my.salesforce.com',
                'token_type' => 'Bearer',
                'scope' => 'api refresh_token',
            ], 200),
            'https://example.my.salesforce.com/*' => Http::response([
                'records' => [
                    [
                        'ownerId' => '0057R00000B2SGHQA3',
                        'ownerName' => 'Excluido Salesforce',
                        'totalSales' => 99,
                    ],
                    [
                        'ownerId' => 'SF-KEEP-001',
                        'ownerName' => 'Comercial Visible',
                        'totalSales' => 10,
                    ],
                ],
            ], 200),
        ]);

        $this->withSession(['salesforce_oauth_state' => 'known-state'])
            ->actingAs($admin)
            ->get(route('salesforce.callback', [
                'code' => 'oauth-code',
                'state' => 'known-state',
            ]));

        $this->assertDatabaseMissing('sales_leaderboard_entries', [
            'salesforce_user_id' => '0057R00000B2SGHQA3',
        ]);

        $this->assertDatabaseMissing('purchase_leaderboard_entries', [
            'salesforce_user_id' => '0057R00000B2SGHQA3',
        ]);

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'salesforce_user_id' => 'SF-KEEP-001',
            'ranking_position' => 1,
        ]);
    }

    public function test_sync_keeps_all_commercials_in_ranking_even_when_salesforce_returns_no_rows(): void
    {
        config()->set('services.salesforce.client_id', 'client-id');
        config()->set('services.salesforce.client_secret', 'client-secret');
        config()->set('services.salesforce.redirect_uri', 'https://staging.hrmotor.com/integraciones/salesforce/callback');
        config()->set(
            'services.salesforce.leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalSales FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );
        config()->set(
            'services.salesforce.purchase_leaderboard_soql',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalPurchases FROM Opportunity GROUP BY OwnerId, Owner.Name'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $commercialWithoutSalesforceId = User::factory()->create([
            'role' => 'comercial',
            'name' => 'Ana Comercial',
            'salesforce_user_id' => null,
        ]);

        $commercialWithSalesforceId = User::factory()->create([
            'role' => 'comercial',
            'name' => 'Bernardo Comercial',
            'salesforce_user_id' => 'SF-404',
        ]);

        Http::fake([
            'https://login.salesforce.com/services/oauth2/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'instance_url' => 'https://example.my.salesforce.com',
                'token_type' => 'Bearer',
                'scope' => 'api refresh_token',
            ], 200),
            'https://example.my.salesforce.com/*' => Http::response([
                'records' => [],
            ], 200),
        ]);

        $this->withSession(['salesforce_oauth_state' => 'known-state'])
            ->actingAs($admin)
            ->get(route('salesforce.callback', [
                'code' => 'oauth-code',
                'state' => 'known-state',
            ]));

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'user_id' => $commercialWithoutSalesforceId->id,
            'seller_name' => 'Ana Comercial',
            'total_sales' => 0,
        ]);

        $this->assertDatabaseHas('sales_leaderboard_entries', [
            'user_id' => $commercialWithSalesforceId->id,
            'salesforce_user_id' => 'SF-404',
            'seller_name' => 'Bernardo Comercial',
            'total_sales' => 0,
        ]);

        $this->assertSame(2, SalesLeaderboardEntry::query()->count());
        $this->assertSame(2, PurchaseLeaderboardEntry::query()->count());
    }

    public function test_leaderboard_is_paginated_instead_of_rendering_an_infinite_scroll_list(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        foreach (range(1, 18) as $position) {
            SalesLeaderboardEntry::query()->create([
                'ranking_position' => $position,
                'seller_name' => 'Comercial '.$position,
                'salesforce_user_id' => 'SF-'.$position,
                'total_sales' => max(0, 20 - $position),
                'synced_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('leaderboard.sales', ['page' => 2]));

        $response
            ->assertOk()
            ->assertSee('Pagina 2 de 2')
            ->assertSee('Comercial 16')
            ->assertDontSee('Comercial 10');
    }

    public function test_home_only_shows_sales_leaderboard_and_not_purchase_leaderboard(): void
    {
        $user = User::factory()->create();

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'seller_name' => 'Comercial Home',
            'salesforce_user_id' => 'SF-HOME-1',
            'total_sales' => 12,
            'synced_at' => now(),
        ]);

        PurchaseLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'seller_name' => 'Comercial Compra Home',
            'salesforce_user_id' => 'SF-HOME-BUY-1',
            'total_purchases' => 5,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Top 10 comerciales del mes')
            ->assertDontSee('Comercial Compra Home');
    }

    public function test_home_shows_commercial_dealership_in_leaderboard_cards(): void
    {
        $user = User::factory()->create();

        $commercial = User::factory()->create([
            'name' => 'Comercial Home Delegacion',
            'dealership' => 'Valencia',
            'salesforce_user_id' => 'SF-HOME-VAL',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'seller_name' => 'Comercial Home Delegacion',
            'salesforce_user_id' => 'SF-HOME-VAL',
            'total_sales' => 22,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Valencia')
            ->assertDontSee('SF-HOME-VAL');
    }
}
