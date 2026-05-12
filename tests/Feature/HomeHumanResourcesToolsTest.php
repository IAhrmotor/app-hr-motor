<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHumanResourcesToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_human_resources_role_sees_the_new_general_tools(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        foreach ([
            'ChatGPT',
            'CaixaBank',
            'Unión de Mutuas',
            'Calculadora Vacaciones',
            'Docusign',
            'Sede',
            'Seguridad Social',
            'Convenios Colectivos',
            'Sistema Delta',
            'Poder Judicial',
            'Sede SEPE',
            'iLovePDF',
            'Sepe',
            'Sepe usuarios',
            'Trámites Navarra',
            'Dehú',
            'Registro Electrónico',
            'Mi IP',
            'Seguridad Social Portal',
        ] as $label) {
            $response->assertSee($label);
        }
    }
}
