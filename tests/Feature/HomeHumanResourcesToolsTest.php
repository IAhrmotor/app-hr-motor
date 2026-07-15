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
            'iLovePDF',
            'Sepe',
            'Sepe usuarios',
            'Trámites Navarra',
            'Dehú',
            'Registro Electrónico',
            'Mi IP',
        ] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_hr_newcars_role_only_sees_the_basic_home_sections(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_HR_NEWCARS,
            'email' => 'hrnewcars@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $response
            ->assertSee('Herramientas generales')
            ->assertSee('DocuSign')
            ->assertDontSee('Otros recursos')
            ->assertDontSee('Documentos Sistemas')
            ->assertDontSee('CMS Motorflash')
            ->assertDontSee('Poder Judicial');
    }

    public function test_it_role_sees_cms_motorflash_and_not_enreach_normal(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_INFORMATION_TECHNOLOGY,
            'email' => 'it@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $response->assertSee('CMS Motorflash');
        $response->assertDontSee('Enreach normal');
    }

    public function test_call_center_extra_role_sees_its_other_resources_section(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_CALL_CENTER,
            'email' => 'callcenter@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $response
            ->assertSee('Otros recursos')
            ->assertSee('Coches de cortesía')
            ->assertSee('Citas garantías HR');
    }
}
