<?php

namespace Tests\Feature\Users;

use App\Models\Dealership;
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
            ->assertSee('Delegación')
            ->assertSee('Bilbao')
            ->assertDontSee('Estado')
            ->assertDontSee('ID Salesforce');
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
