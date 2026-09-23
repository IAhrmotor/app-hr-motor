<?php

namespace Tests\Feature;

use App\Models\Dealership;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DealershipManagementTest extends TestCase
{
    use RefreshDatabase;

    protected array $createdImages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdImages as $path) {
            $absolutePath = public_path($path);

            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        parent::tearDown();
    }

    public function test_admin_no_longer_sees_the_legacy_admin_menu_and_admin_redirects_to_backoffice(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response
            ->assertOk()
            ->assertDontSee(route('admin.index'), false);

        $adminPageResponse = $this->actingAs($admin)->get(route('admin.index'));

        $adminPageResponse
            ->assertRedirect('/backoffice');
    }
}
