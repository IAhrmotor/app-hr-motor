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
            'company_entry_date' => '2024-02-15',
            'job_position' => 'Responsable de tienda',
            'dealership' => 'Bilbao',
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response
            ->assertOk()
            ->assertSee('Perfil Comercial')
            ->assertSee('aria-label="Chatear con Perfil Comercial"', false)
            ->assertSee('href="' . route('chat.beta', ['recipient' => $user->id]) . '"', false)
            ->assertSee('aria-label="Ver LinkedIn"', false)
            ->assertSee('href="' . $user->linkedin_url . '"', false)
            ->assertDontSeeText($user->linkedin_url)
            ->assertSee('Delegación')
            ->assertSee('Bilbao')
            ->assertSee('15/02/2024')
            ->assertSee('Responsable de tienda')
            ->assertSee(route('dealerships.show', $dealership), false)
            ->assertDontSee('Estado')
            ->assertDontSee('ID Salesforce');
    }

    public function test_user_profile_hides_empty_optional_fields_instead_of_showing_no_disponible(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'Perfil Sin Extras',
            'phone' => null,
            'enreach_extension' => null,
            'company_entry_date' => null,
            'job_position' => null,
            'dealership' => null,
            'dealership_id' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response
            ->assertOk()
            ->assertDontSee('No disponible')
            ->assertDontSee('Teléfono')
            ->assertDontSee('Extensión Enreach')
            ->assertDontSee('Día que entró en la empresa')
            ->assertDontSee('Puesto')
            ->assertDontSee('Delegación');
    }

    public function test_any_registered_user_can_open_another_users_profile_view(): void
    {
        $commercial = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'name' => 'Comercial Acceso',
        ]);

        $profileUser = User::factory()->create([
            'name' => 'Perfil Visible',
            'dealership' => 'Madrid',
        ]);

        $response = $this->actingAs($commercial)->get(route('users.show', $profileUser));

        $response
            ->assertOk()
            ->assertSee('Perfil Visible')
            ->assertSee(route('chat.beta', ['recipient' => $profileUser->id]), false)
            ->assertSee(route('users.show', $profileUser), false);
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
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
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
            ->assertSee('Posición en rankings')
            ->assertSee('Top 3')
            ->assertSee('Top 2')
            ->assertSee('7 ventas este mes')
            ->assertSee('5 compras este mes')
            ->assertSee('Ranking ventas')
            ->assertSee('Ranking compras');
    }

    public function test_store_manager_profile_uses_the_new_role_label_and_shows_rankings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Sevilla',
        ]);

        $storeManager = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_STORE_MANAGER,
            'name' => 'Marta Jefa',
            'dealership' => 'Sevilla',
            'dealership_id' => $dealership->id,
            'salesforce_user_id' => 'SF-STORE-002',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 4,
            'user_id' => $storeManager->id,
            'salesforce_user_id' => 'SF-STORE-002',
            'seller_name' => 'Marta Jefa',
            'total_sales' => 6,
            'synced_at' => now(),
        ]);

        PurchaseLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $storeManager->id,
            'salesforce_user_id' => 'SF-STORE-002',
            'seller_name' => 'Marta Jefa',
            'total_purchases' => 8,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $storeManager));

        $response
            ->assertOk()
            ->assertSee('Jefe de tienda')
            ->assertSee('Top 4')
            ->assertSee('Top 1')
            ->assertSee('6 ventas este mes')
            ->assertSee('8 compras este mes');
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
