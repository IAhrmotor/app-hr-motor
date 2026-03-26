<?php

namespace Tests\Feature\Users;

use App\Models\Dealership;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_open_user_profile_view_and_see_linkedin_button(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Bilbao',
        ]);

        $user = User::factory()->create([
            'name' => 'Perfil Comercial',
            'linkedin_url' => 'https://www.linkedin.com/in/perfil-comercial/',
            'dealership' => 'Bilbao',
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response
            ->assertOk()
            ->assertSee('Perfil Comercial')
            ->assertSee('aria-label="Ver LinkedIn"', false)
            ->assertSee('href="' . $user->linkedin_url . '"', false)
            ->assertDontSeeText($user->linkedin_url)
            ->assertSee('Delegacion')
            ->assertSee('Bilbao')
            ->assertSee(route('dealerships.show', $dealership), false)
            ->assertDontSee('Estado')
            ->assertDontSee('ID Salesforce');
    }

    public function test_commercial_profile_shows_sales_and_purchase_ranking_positions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Sevilla',
        ]);

        $commercial = User::factory()->create([
            'role' => 'comercial',
            'name' => 'Laura Comercial',
            'dealership' => 'Sevilla',
            'dealership_id' => $dealership->id,
            'salesforce_user_id' => 'SF-COM-001',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 3,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-COM-001',
            'seller_name' => 'Laura Comercial',
            'total_sales' => 7,
            'synced_at' => now(),
        ]);

        PurchaseLeaderboardEntry::query()->create([
            'ranking_position' => 2,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-COM-001',
            'seller_name' => 'Laura Comercial',
            'total_purchases' => 5,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $commercial));

        $response
            ->assertOk()
            ->assertSee('Posicion en rankings')
            ->assertSee('Top 3')
            ->assertSee('Top 2')
            ->assertSee('7 ventas este mes')
            ->assertSee('5 compras este mes')
            ->assertSee('Ranking ventas')
            ->assertSee('Ranking compras');
    }

    public function test_users_index_links_avatar_and_name_to_profile_view(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee(route('users.show', $user), false);
    }
}
