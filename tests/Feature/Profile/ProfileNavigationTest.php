<?php

namespace Tests\Feature\Profile;

use App\Models\Dealership;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProfileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected array $createdAvatarPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdAvatarPaths as $path) {
            $absolutePath = public_path($path);

            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        parent::tearDown();
    }

    public function test_authenticated_user_can_open_profile_edit_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Perfil HR',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('Modificar perfil')
            ->assertSee($user->avatar_url, false);
    }

    public function test_authenticated_user_can_open_own_profile_view(): void
    {
        $dealership = Dealership::factory()->create([
            'name' => 'Valencia',
        ]);

        $user = User::factory()->create([
            'name' => 'Perfil Visible',
            'dealership' => 'Valencia',
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response
            ->assertOk()
            ->assertSee('Perfil Visible')
            ->assertSee('Delegación')
            ->assertSee('Valencia')
            ->assertSee('Editar perfil')
            ->assertDontSee('Estado')
            ->assertDontSee('ID Salesforce');
    }

    public function test_homepage_shows_profile_menu_avatar_and_link(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuario Menu',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee($user->avatar_url, false)
            ->assertSee(route('profile.show'), false)
            ->assertSee(route('profile.edit'), false)
            ->assertSee('Cerrar sesi', false);
    }

    public function test_homepage_shows_top_10_leaderboard_section_when_data_exists(): void
    {
        $user = User::factory()->create([
            'name' => 'Comercial Viewer',
            'extra_role' => User::ROLE_COMMERCIAL,
        ]);

        $commercial = User::factory()->create([
            'name' => 'Comercial Uno',
            'email' => 'comercial-uno@example.com',
            'salesforce_user_id' => 'SF-001',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $commercial->id,
            'salesforce_user_id' => 'SF-001',
            'seller_name' => 'Nombre Salesforce',
            'total_sales' => 12,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Top 10 comerciales del mes')
            ->assertSee('Comercial Uno')
            ->assertSee('12')
            ->assertSee(route('users.show', $commercial), false)
            ->assertSee('group-hover:text-brand-primary', false);
    }

    public function test_user_can_update_linkedin_url_and_avatar_from_profile(): void
    {
        $user = User::factory()->create([
            'linkedin_url' => null,
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'linkedin_url' => 'https://www.linkedin.com/in/perfil-hr-motor/',
            'avatar' => UploadedFile::fake()->image('avatar.png', 400, 400),
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->createdAvatarPaths[] = $user->avatar_path;

        $this->assertSame('https://www.linkedin.com/in/perfil-hr-motor/', $user->linkedin_url);
        $this->assertStringStartsWith('images/users/avatars/', $user->avatar_path);
        $this->assertFileExists(public_path($user->avatar_path));
    }
}
