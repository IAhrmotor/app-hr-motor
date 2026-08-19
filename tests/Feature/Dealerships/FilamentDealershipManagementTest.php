<?php

namespace Tests\Feature\Dealerships;

use App\Filament\Resources\Dealerships\Pages\CreateDealership;
use App\Filament\Resources\Dealerships\Pages\EditDealership;
use App\Models\Dealership;
use App\Models\DealershipActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentDealershipManagementTest extends TestCase
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

    public function test_admin_can_create_dealership_with_image_in_filament(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin);

        Livewire::test(CreateDealership::class)
            ->set('data.name', 'Sevilla Este')
            ->set('data.salesforce_id', 'DLR-SEV-001')
            ->set('data.phone', '954000111')
            ->set('data.google_maps_url', 'https://maps.google.com/?q=sevilla+este')
            ->set('data.reviews_url', 'https://example.com/resenas/sevilla')
            ->set('data.image_path', UploadedFile::fake()->image('sevilla.png', 400, 400))
            ->call('create')
            ->assertHasNoErrors();

        $dealership = Dealership::query()->where('name', 'Sevilla Este')->firstOrFail();

        $this->createdImages[] = $dealership->image_path;

        $this->assertStringStartsWith('images/dealerships/', (string) $dealership->image_path);
        $this->assertFileExists(public_path($dealership->image_path));
    }

    public function test_admin_can_replace_dealership_image_in_filament(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $oldImagePath = 'images/dealerships/bilbao-centro-original.png';
        File::ensureDirectoryExists(public_path('images/dealerships'));
        File::put(public_path($oldImagePath), 'old image');

        $dealership = Dealership::query()->create([
            'name' => 'Bilbao Centro',
            'salesforce_id' => 'DLR-BIL-001',
            'phone' => '944000111',
            'google_maps_url' => 'https://maps.google.com/?q=bilbao+centro',
            'reviews_url' => 'https://example.com/resenas/bilbao',
            'image_path' => $oldImagePath,
        ]);

        $this->createdImages[] = $oldImagePath;

        Livewire::actingAs($admin);

        Livewire::test(EditDealership::class, ['record' => $dealership->getKey()])
            ->set('data.image_path', UploadedFile::fake()->image('bilbao-nueva.png', 600, 600))
            ->call('save', false, false)
            ->assertHasNoErrors();

        $dealership->refresh();

        $this->createdImages[] = $dealership->image_path;

        $this->assertStringStartsWith('images/dealerships/', (string) $dealership->image_path);
        $this->assertFileExists(public_path($dealership->image_path));
        $this->assertFileDoesNotExist(public_path($oldImagePath));

        $log = DealershipActivityLog::query()->latest('created_at')->first();

        $this->assertNotNull($log);
        $this->assertSame(DealershipActivityLog::ACTION_UPDATED, $log->action);
        $this->assertSame('Bilbao Centro', $log->target_name);
        $this->assertSame([
            'Foto' => [
                'from' => 'Anterior',
                'to' => 'Actualizada',
            ],
        ], $log->changes);
    }
}
