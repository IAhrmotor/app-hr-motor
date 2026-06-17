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
}
