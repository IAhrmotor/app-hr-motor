<?php

namespace Tests\Feature\Users;

use App\Models\Dealership;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@example.com',
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-NEW-001',
            'dealership_id' => $dealership->id,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('user_activity_logs', [
            'action' => UserActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => 'Admin Principal',
            'target_name' => 'Usuario Nuevo',
            'target_email' => 'nuevo@example.com',
            'target_dealership' => 'Pamplona',
        ]);

        $createdUser = User::query()->where('email', 'nuevo@example.com')->firstOrFail();

        Notification::assertSentTo($createdUser, ResetPassword::class);
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
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-OLD-001',
        ]);

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Usuario Editado',
            'email' => 'editado@example.com',
            'role' => 'comercial',
            'salesforce_user_id' => 'SF-NEW-002',
            'dealership_id' => $dealership->id,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));

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

    public function test_admin_logs_page_lists_user_activity_and_allows_csv_download(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
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

        $pageResponse = $this->actingAs($admin)->get(route('admin.logs.index'));

        $pageResponse
            ->assertOk()
            ->assertSee('Logs de usuarios')
            ->assertSee('Usuario Audit')
            ->assertSee('Admin Principal')
            ->assertSee(route('admin.logs.export'), false);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.logs.export'));

        $downloadResponse
            ->assertOk()
            ->assertDownload();

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('fecha_hora;accion;gestionado_por', $content);
        $this->assertStringContainsString('Usuario Audit', $content);
        $this->assertStringContainsString('Admin Principal', $content);
        $this->assertStringContainsString('Email: ""antes@example.com"" -> ""audit@example.com""', $content);
    }

    public function test_admin_logs_can_be_filtered_by_date_range_and_actor(): void
    {
        Carbon::setTestNow('2026-03-23 10:00:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
        ]);
        $manager = User::factory()->create([
            'role' => 'gestor',
            'name' => 'Gestor Norte',
            'email' => 'gestor@example.com',
        ]);

        UserActivityLog::query()->create([
            'action' => UserActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_user_id' => null,
            'target_name' => 'Usuario Hoy Admin',
            'target_email' => 'hoy-admin@example.com',
            'target_role' => 'comercial',
            'target_dealership' => 'Madrid',
            'changes' => null,
            'created_at' => Carbon::parse('2026-03-23 10:00:00'),
        ]);

        UserActivityLog::query()->create([
            'action' => UserActivityLog::ACTION_CREATED,
            'actor_user_id' => $manager->id,
            'actor_name' => $manager->name,
            'actor_email' => $manager->email,
            'target_user_id' => null,
            'target_name' => 'Usuario Hoy Gestor',
            'target_email' => 'hoy-gestor@example.com',
            'target_role' => 'comercial',
            'target_dealership' => 'Bilbao',
            'changes' => null,
            'created_at' => Carbon::parse('2026-03-23 11:00:00'),
        ]);

        UserActivityLog::query()->create([
            'action' => UserActivityLog::ACTION_CREATED,
            'actor_user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'target_user_id' => null,
            'target_name' => 'Usuario Ayer Admin',
            'target_email' => 'ayer-admin@example.com',
            'target_role' => 'comercial',
            'target_dealership' => 'Valencia',
            'changes' => null,
            'created_at' => Carbon::parse('2026-03-22 10:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.logs.index', [
            'date_from' => '2026-03-23',
            'date_to' => '2026-03-23',
            'actor' => $manager->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Usuario Hoy Gestor')
            ->assertDontSee('Usuario Hoy Admin')
            ->assertDontSee('Usuario Ayer Admin')
            ->assertSee('value="2026-03-23"', false)
            ->assertSee('Del 23/03/2026 al 23/03/2026')
            ->assertSee('Gestor Norte');

        $downloadResponse = $this->actingAs($admin)->get(route('admin.logs.export', [
            'date_from' => '2026-03-23',
            'date_to' => '2026-03-23',
            'actor' => $manager->id,
        ]));

        $content = $downloadResponse->streamedContent();

        $this->assertStringContainsString('Usuario Hoy Gestor', $content);
        $this->assertStringNotContainsString('Usuario Hoy Admin', $content);
        $this->assertStringNotContainsString('Usuario Ayer Admin', $content);

        Carbon::setTestNow();
    }
}
