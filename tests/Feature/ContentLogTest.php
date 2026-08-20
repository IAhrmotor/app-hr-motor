<?php

namespace Tests\Feature;

use App\Models\MonthlyMagazineActivityLog;
use App\Models\MonthlyMagazineSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentLogTest extends TestCase
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

    public function test_monthly_magazine_logs_collect_magazine_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $pdf = UploadedFile::fake()->create('revista-abril.pdf', 512, 'application/pdf');
        $this->actingAs($admin)->put(route('admin.magazine.update'), [
            'tag_label' => 'Abril',
            'file_name' => 'revista abril 2026',
            'magazine_file' => $pdf,
        ])->assertRedirect(route('admin.magazine.edit'));

        $this->assertDatabaseCount('monthly_magazine_activity_logs', 1);

        $magazine = MonthlyMagazineSetting::query()->first();
        if ($magazine) {
            $this->createdMagazineFiles[] = $magazine->pdf_path;
        }

        $this->assertDatabaseHas('monthly_magazine_activity_logs', [
            'action' => MonthlyMagazineActivityLog::ACTION_CREATED,
            'target_name' => 'Abril',
            'target_reference' => 'revista/revista-abril-2026.pdf',
        ]);
    }
}
