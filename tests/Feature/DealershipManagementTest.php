<?php

namespace Tests\Feature;

use App\Models\Dealership;
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

    public function test_admin_sees_admin_menu_with_users_and_dealerships_links(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee(route('users.index'), false)
            ->assertSee(route('dealerships.index'), false);
    }

    public function test_admin_can_create_dealership(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('dealerships.store'), [
            'name' => 'Sevilla Este',
            'salesforce_id' => 'DLR-SEV-001',
            'google_maps_url' => 'https://maps.google.com/?q=sevilla+este',
            'reviews_url' => 'https://example.com/resenas/sevilla',
        ]);

        $response
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dealerships', [
            'name' => 'Sevilla Este',
            'salesforce_id' => 'DLR-SEV-001',
        ]);
    }

    public function test_admin_can_update_dealership_with_image(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Bilbao Centro',
        ]);

        $response = $this->actingAs($admin)->put(route('dealerships.update', $dealership), [
            'name' => 'Bilbao Norte',
            'salesforce_id' => 'DLR-BIL-002',
            'google_maps_url' => 'https://maps.google.com/?q=bilbao+norte',
            'reviews_url' => 'https://example.com/resenas/bilbao',
            'image' => UploadedFile::fake()->image('delegacion.png', 400, 400),
        ]);

        $response
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('success');

        $dealership->refresh();
        $this->createdImages[] = $dealership->image_path;

        $this->assertSame('Bilbao Norte', $dealership->name);
        $this->assertSame('DLR-BIL-002', $dealership->salesforce_id);
        $this->assertStringStartsWith('images/dealerships/', (string) $dealership->image_path);
        $this->assertFileExists(public_path($dealership->image_path));
    }

    public function test_admin_cannot_delete_dealership_with_users_assigned(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Murcia',
        ]);

        User::factory()->create([
            'dealership' => 'Murcia',
            'dealership_id' => $dealership->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('dealerships.destroy', $dealership));

        $response
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('dealerships', [
            'id' => $dealership->id,
        ]);
    }
}
