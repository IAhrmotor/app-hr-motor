<?php

namespace Tests\Feature;

use App\Models\MonthlyMagazineSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MonthlyMagazineManagementTest extends TestCase
{
    use RefreshDatabase;

    protected array $createdMagazineFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdMagazineFiles as $path) {
            $absolutePath = public_path($path);

            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        parent::tearDown();
    }

    public function test_admin_and_manager_can_open_monthly_magazine_admin_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'gestor']);

        $this->actingAs($admin)
            ->get(route('admin.magazine.edit'))
            ->assertOk()
            ->assertSee('Gestión de la revista mensual');

        $this->actingAs($manager)
            ->get(route('admin.magazine.edit'))
            ->assertOk()
            ->assertSee('Gestión de la revista mensual');
    }

    public function test_admin_can_publish_new_monthly_magazine(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pdf = UploadedFile::fake()->create('revista-mayo.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($admin)->put(route('admin.magazine.update'), [
            'tag_label' => 'Mayo',
            'file_name' => 'revista mayo 2026',
            'magazine_file' => $pdf,
        ]);

        $response
            ->assertRedirect(route('admin.magazine.edit'))
            ->assertSessionHas('success');

        $magazine = MonthlyMagazineSetting::query()->first();

        $this->assertNotNull($magazine);
        $this->assertSame('Mayo', $magazine->tag_label);
        $this->assertSame('revista/revista-mayo-2026.pdf', $magazine->pdf_path);

        $absolutePath = public_path($magazine->pdf_path);
        $this->assertFileExists($absolutePath);
        $this->createdMagazineFiles[] = $magazine->pdf_path;

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Mayo');
    }
}
