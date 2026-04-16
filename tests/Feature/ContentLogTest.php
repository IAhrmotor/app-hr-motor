<?php

namespace Tests\Feature;

use App\Models\ContentActivityLog;
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

    public function test_content_logs_collect_tags_and_magazine_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.forum-tags.store'), [
            'name' => 'Coche nuevo',
            'color' => '#FF6600',
        ])->assertRedirect(route('admin.forum-tags.index'));

        $pdf = UploadedFile::fake()->create('revista-abril.pdf', 512, 'application/pdf');
        $this->actingAs($admin)->put(route('admin.magazine.update'), [
            'tag_label' => 'Abril',
            'file_name' => 'revista abril 2026',
            'magazine_file' => $pdf,
        ])->assertRedirect(route('admin.magazine.edit'));

        $this->assertDatabaseCount('content_activity_logs', 2);

        $page = $this->actingAs($admin)->get(route('admin.content-logs.index'));

        $page
            ->assertOk()
            ->assertSee('Logs de contenidos')
            ->assertSee('Tag del foro')
            ->assertSee('Revista mensual')
            ->assertSee('Coche nuevo')
            ->assertSee('Abril');

        $magazine = MonthlyMagazineSetting::query()->first();
        if ($magazine) {
            $this->createdMagazineFiles[] = $magazine->pdf_path;
        }

        $this->assertDatabaseHas('content_activity_logs', [
            'content_type' => ContentActivityLog::CONTENT_TYPE_FORUM_TAG,
            'action' => ContentActivityLog::ACTION_CREATED,
            'target_name' => 'Coche nuevo',
        ]);

        $this->assertDatabaseHas('content_activity_logs', [
            'content_type' => ContentActivityLog::CONTENT_TYPE_MAGAZINE,
            'action' => ContentActivityLog::ACTION_UPDATED,
            'target_name' => 'Abril',
        ]);
    }
}
