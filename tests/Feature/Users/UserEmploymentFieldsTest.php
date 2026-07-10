<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmploymentFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'company_entry_date' => '2026-01-10',
            'job_position' => 'Puesto base',
        ], $overrides);
    }

    private function itSchedulePayload(array $overrides = []): array
    {
        return array_merge([
            'it_monday_start' => '08:00',
            'it_monday_end' => '17:00',
            'it_tuesday_start' => '08:00',
            'it_tuesday_end' => '17:00',
            'it_wednesday_start' => '08:00',
            'it_wednesday_end' => '17:00',
            'it_thursday_start' => '08:00',
            'it_thursday_end' => '17:00',
            'it_friday_start' => '08:00',
            'it_friday_end' => '17:00',
        ], $overrides);
    }

    public function test_company_entry_date_and_job_position_are_required_when_creating_user(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->from(route('users.create'))
            ->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Usuario Sin Campos',
                'email' => 'sin-campos@example.com',
                'role' => User::ROLE_USER,
            ]);

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors(['company_entry_date', 'job_position']);

        $this->assertDatabaseMissing('users', [
            'email' => 'sin-campos@example.com',
        ]);
    }

    public function test_company_entry_date_and_job_position_are_required_when_updating_user(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => null,
        ]);

        $response = $this->from(route('users.edit', $user))
            ->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => User::ROLE_USER,
                'salesforce_user_id' => null,
                'dealership_id' => $user->dealership_id,
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors(['company_entry_date', 'job_position']);
    }

    public function test_user_employment_fields_persist_on_create_and_update(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $createdResponse = $this->actingAs($admin)->post(route('users.store'), $this->userPayload([
            'name' => 'Usuario Laboral',
            'email' => 'laboral@example.com',
            'role' => User::ROLE_USER,
        ]));

        $createdResponse->assertRedirect(route('users.index'));

        $createdUser = User::query()->where('email', 'laboral@example.com')->firstOrFail();

        $this->assertSame('2026-01-10', $createdUser->company_entry_date?->toDateString());
        $this->assertSame('Puesto base', $createdUser->job_position);

        $updateResponse = $this->actingAs($admin)->put(route('users.update', $createdUser), $this->userPayload([
            'name' => 'Usuario Laboral',
            'email' => 'laboral@example.com',
            'role' => User::ROLE_USER,
            'company_entry_date' => '2026-02-11',
            'job_position' => 'Responsable de equipo',
            'password' => '',
            'password_confirmation' => '',
        ]));

        $updateResponse->assertRedirect(route('users.index'));

        $createdUser->refresh();

        $this->assertSame('2026-02-11', $createdUser->company_entry_date?->toDateString());
        $this->assertSame('Responsable de equipo', $createdUser->job_position);
    }

    public function test_it_users_require_a_weekday_schedule_when_creating_and_updating(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $createResponse = $this->from(route('users.create'))
            ->actingAs($admin)
            ->post(route('users.store'), $this->userPayload([
                'name' => 'Tecnico Sin Horario',
                'email' => 'it-sin-horario@example.com',
                'role' => User::ROLE_USER,
                'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            ]));

        $createResponse
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors([
                'it_monday_start',
                'it_monday_end',
                'it_tuesday_start',
                'it_tuesday_end',
                'it_wednesday_start',
                'it_wednesday_end',
                'it_thursday_start',
                'it_thursday_end',
                'it_friday_start',
                'it_friday_end',
            ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'it_monday_start' => '08:00',
            'it_monday_end' => '17:00',
            'it_tuesday_start' => '08:00',
            'it_tuesday_end' => '17:00',
            'it_wednesday_start' => '08:00',
            'it_wednesday_end' => '17:00',
            'it_thursday_start' => '08:00',
            'it_thursday_end' => '17:00',
            'it_friday_start' => '08:00',
            'it_friday_end' => '17:00',
        ]);

        $updateResponse = $this->from(route('users.edit', $user))
            ->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'company_entry_date' => optional($user->company_entry_date)->format('Y-m-d') ?? now()->toDateString(),
                'job_position' => $user->job_position,
                'role' => User::ROLE_USER,
                'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
                'phone' => $user->phone,
                'enreach_extension' => $user->enreach_extension,
                'salesforce_user_id' => null,
                'dealership_id' => $user->dealership_id,
                'password' => '',
                'password_confirmation' => '',
            ]);

        $updateResponse
            ->assertRedirect(route('users.edit', $user))
            ->assertSessionHasErrors([
                'it_monday_start',
                'it_monday_end',
                'it_tuesday_start',
                'it_tuesday_end',
                'it_wednesday_start',
                'it_wednesday_end',
                'it_thursday_start',
                'it_thursday_end',
                'it_friday_start',
                'it_friday_end',
            ]);
    }

    public function test_it_user_schedule_persists_on_create_and_update(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $createdResponse = $this->actingAs($admin)->post(route('users.store'), $this->userPayload($this->itSchedulePayload([
            'name' => 'Tecnico Con Horario',
            'email' => 'it-con-horario@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
        ])));

        $createdResponse->assertRedirect(route('users.index'));

        $createdUser = User::query()->where('email', 'it-con-horario@example.com')->firstOrFail();

        $this->assertSame('08:00', $createdUser->it_monday_start);
        $this->assertSame('17:00', $createdUser->it_friday_end);
        $this->assertSame('08:00', $createdUser->it_tuesday_start);
        $this->assertSame('17:00', $createdUser->it_wednesday_end);

        $updatedResponse = $this->actingAs($admin)->put(route('users.update', $createdUser), $this->userPayload($this->itSchedulePayload([
            'name' => 'Tecnico Con Horario',
            'email' => 'it-con-horario@example.com',
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'it_monday_start' => '09:00',
            'it_monday_end' => '18:00',
        ])));

        $updatedResponse->assertRedirect(route('users.index'));

        $createdUser->refresh();

        $this->assertSame('09:00', $createdUser->it_monday_start);
        $this->assertSame('18:00', $createdUser->it_monday_end);
        $this->assertSame('17:00', $createdUser->it_friday_end);
    }
}
