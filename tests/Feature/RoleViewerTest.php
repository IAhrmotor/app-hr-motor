<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_switch_role_view_and_return_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Visor de roles')
            ->assertSee(route('role-viewer.store'), false)
            ->assertDontSee('Volver a vista admin');

        $this->from(route('home'))
            ->post(route('role-viewer.store'), [
                'role' => User::ROLE_COMMERCIAL,
            ])
            ->assertRedirect(route('home'));

        $this->assertSame(User::ROLE_COMMERCIAL, session('role_viewer.active_role'));

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Volver a admin')
            ->assertSee('Comercial');

        $this->from(route('home'))
            ->delete(route('role-viewer.destroy'))
            ->assertRedirect(route('home'));

        $this->assertNull(session('role_viewer.active_role'));

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Visor de roles')
            ->assertDontSee('Volver a admin');
    }

    public function test_admin_role_viewer_places_hr_newcars_last(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-order@example.com',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->getContent();

        $rentingPosition = strrpos($html, 'Renting');
        $hrNewcarsPosition = strrpos($html, 'HR NewCars');

        $this->assertNotFalse($rentingPosition);
        $this->assertNotFalse($hrNewcarsPosition);
        $this->assertGreaterThan($rentingPosition, $hrNewcarsPosition);
    }

    public function test_non_admin_users_do_not_see_or_use_the_role_viewer(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'email' => 'commercial@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Visor de roles');

        $this->actingAs($user)
            ->post(route('role-viewer.store'), [
                'role' => User::ROLE_COMMERCIAL,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('role-viewer.destroy'))
            ->assertForbidden();
    }
}
