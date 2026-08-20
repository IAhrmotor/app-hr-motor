<?php

namespace Tests\Feature\Users;

use App\Filament\Resources\Users\UserResource;
use App\Models\Dealership;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Notifications\UserPasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function baseUserPayload(array $overrides = []): array
    {
        return array_merge([
            'company_entry_date' => '2026-01-01',
            'job_position' => 'Puesto base',
        ], $overrides);
    }

    public function test_user_creation_is_logged_with_actor_and_target_data(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Pamplona',
        ]);

        $this->actingAs($admin)->post(route('users.store'), $this->baseUserPayload([
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-NEW-001',
            'dealership_id' => $dealership->id,
        ]))->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('user_activity_logs', [
            'action' => UserActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => 'Admin Principal',
            'target_name' => 'Usuario Nuevo',
            'target_email' => 'nuevo@example.com',
            'target_dealership' => 'Pamplona',
        ]);

        $createdUser = User::query()->where('email', 'nuevo@example.com')->firstOrFail();

        Notification::assertSentTo($createdUser, UserPasswordResetNotification::class);
    }

    public function test_user_update_is_logged_with_changed_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $dealership = Dealership::factory()->create([
            'name' => 'Bilbao',
        ]);
        $user = User::factory()->create([
            'name' => 'Usuario Original',
            'email' => 'original@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-OLD-001',
        ]);

        $this->actingAs($admin)->put(route('users.update', $user), $this->baseUserPayload([
            'name' => 'Usuario Editado',
            'email' => 'editado@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_COMMERCIAL,
            'salesforce_user_id' => 'SF-NEW-002',
            'dealership_id' => $dealership->id,
            'password' => '',
            'password_confirmation' => '',
        ]))->assertRedirect(route('users.index'));

        $log = UserActivityLog::query()
            ->where('action', UserActivityLog::ACTION_UPDATED)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Usuario Editado', $log->target_name);
        $this->assertSame('editado@example.com', $log->target_email);
        $this->assertSame('Usuario Original', $log->changes['Nombre']['from']);
        $this->assertSame('Usuario Editado', $log->changes['Nombre']['to']);
        $this->assertSame('original@example.com', $log->changes['Email']['from']);
        $this->assertSame('editado@example.com', $log->changes['Email']['to']);
        $this->assertSame('SF-OLD-001', $log->changes['ID Salesforce']['from']);
        $this->assertSame('SF-NEW-002', $log->changes['ID Salesforce']['to']);
        $this->assertSame('Bilbao', $log->target_dealership);
    }

    public function test_user_deletion_is_logged_before_removing_the_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
        ]);
        $user = User::factory()->create([
            'name' => 'Usuario Eliminado',
            'email' => 'eliminado@example.com',
        ]);

        $this->actingAs($admin)->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseHas('user_activity_logs', [
            'action' => UserActivityLog::ACTION_DELETED,
            'actor_user_id' => $admin->id,
            'target_name' => 'Usuario Eliminado',
            'target_email' => 'eliminado@example.com',
        ]);
    }

    public function test_filament_user_logs_page_lists_user_activity_and_is_admin_only(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Principal',
            'email' => 'gestor@example.com',
        ]);

        UserActivityLog::query()->create([
            'action' => UserActivityLog::ACTION_UPDATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_user_id' => null,
            'target_name' => 'Usuario Audit',
            'target_email' => 'audit@example.com',
            'target_role' => 'comercial',
            'target_dealership' => 'Valencia',
            'changes' => [
                'Email' => [
                    'from' => 'antes@example.com',
                    'to' => 'audit@example.com',
                ],
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(UserResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs de usuarios')
            ->assertSee('Usuario Audit')
            ->assertSee('Admin Principal');

        $this->actingAs($manager)
            ->get(UserResource::getUrl('logs'))
            ->assertForbidden();
    }

    public function test_old_admin_user_logs_urls_are_removed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/logs/usuarios')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get('/admin/logs/usuarios/descargar')
            ->assertNotFound();
    }
}
