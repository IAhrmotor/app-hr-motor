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

    public function test_admin_sees_admin_menu_and_admin_page_lists_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Admin');

        $adminPageResponse = $this->actingAs($admin)->get(route('admin.index'));

        $adminPageResponse
            ->assertOk()
            ->assertSee(route('users.index'), false)
            ->assertSee(route('dealerships.index'), false)
            ->assertSee(route('admin.dealership-logs.index'), false);
    }

    public function test_admin_can_create_dealership(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('dealerships.store'), [
            'name' => 'Sevilla Este',
            'salesforce_id' => 'DLR-SEV-001',
            'phone' => '954000111',
            'google_maps_url' => 'https://maps.google.com/?q=sevilla+este',
            'reviews_url' => 'https://example.com/resenas/sevilla',
            'image' => UploadedFile::fake()->image('sevilla.png', 400, 400),
        ]);

        $response
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dealerships', [
            'name' => 'Sevilla Este',
            'salesforce_id' => 'DLR-SEV-001',
            'phone' => '954000111',
        ]);

        $createdDealership = Dealership::query()->where('name', 'Sevilla Este')->first();
        $this->assertNotNull($createdDealership);
        $this->createdImages[] = $createdDealership->image_path;
    }

    public function test_admin_cannot_create_dealership_without_all_required_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->from(route('dealerships.create'))
            ->actingAs($admin)
            ->post(route('dealerships.store'), [
                'name' => 'Sevilla Sur',
            ]);

        $response
            ->assertRedirect(route('dealerships.create'))
            ->assertSessionHasErrors(['salesforce_id', 'phone', 'google_maps_url', 'reviews_url', 'image']);
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
            'phone' => '944000222',
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
        $this->assertSame('944000222', $dealership->phone);
        $this->assertStringStartsWith('images/dealerships/', (string) $dealership->image_path);
        $this->assertFileExists(public_path($dealership->image_path));
    }

    public function test_manager_can_manage_dealership_crud(): void
    {
        $manager = User::factory()->create([
            'role' => 'gestor',
        ]);

        $createResponse = $this->actingAs($manager)->post(route('dealerships.store'), [
            'name' => 'Malaga Centro',
            'salesforce_id' => 'DLR-MAL-001',
            'phone' => '952000444',
            'google_maps_url' => 'https://maps.google.com/?q=malaga+centro',
            'reviews_url' => 'https://example.com/resenas/malaga',
            'image' => UploadedFile::fake()->image('malaga.png', 400, 400),
        ]);

        $createResponse
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('success');

        $dealership = Dealership::query()->where('name', 'Malaga Centro')->firstOrFail();
        $this->createdImages[] = $dealership->image_path;

        $this->actingAs($manager)->get(route('dealerships.create'))->assertOk();
        $this->actingAs($manager)->get(route('dealerships.show', $dealership))->assertOk();
        $this->actingAs($manager)->get(route('dealerships.edit', $dealership))->assertOk();

        $updateResponse = $this->actingAs($manager)->put(route('dealerships.update', $dealership), [
            'name' => 'Malaga Norte',
            'salesforce_id' => 'DLR-MAL-002',
            'phone' => '952000555',
            'google_maps_url' => 'https://maps.google.com/?q=malaga+norte',
            'reviews_url' => 'https://example.com/resenas/malaga-norte',
        ]);

        $updateResponse
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dealerships', [
            'id' => $dealership->id,
            'name' => 'Malaga Norte',
            'salesforce_id' => 'DLR-MAL-002',
            'phone' => '952000555',
        ]);

        $deleteResponse = $this->actingAs($manager)->delete(route('dealerships.destroy', $dealership));

        $deleteResponse
            ->assertRedirect(route('dealerships.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('dealerships', [
            'id' => $dealership->id,
        ]);
    }

    public function test_dealership_show_hides_salesforce_id_and_displays_clickable_links(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Valencia Centro',
            'salesforce_id' => 'DLR-VAL-555',
            'phone' => '961000333',
            'google_maps_url' => 'https://maps.google.com/?q=valencia+centro',
            'reviews_url' => 'https://example.com/resenas/valencia',
        ]);

        $response = $this->actingAs($user)->get(route('dealerships.show', $dealership));

        $response
            ->assertOk()
            ->assertSee('Valencia Centro')
            ->assertSee('961000333')
            ->assertDontSee('DLR-VAL-555')
            ->assertDontSee('ID de Salesforce')
            ->assertSee($dealership->google_maps_url, false)
            ->assertSee($dealership->reviews_url, false);
    }

    public function test_dealership_show_displays_monthly_user_stats_and_dealership_rank_badges(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Sevilla',
        ]);

        $topDealership = Dealership::factory()->create([
            'name' => 'Valencia',
        ]);

        $sevillaCommercial = User::factory()->create([
            'name' => 'Laura Sevilla',
            'dealership' => 'Sevilla',
            'dealership_id' => $dealership->id,
            'salesforce_user_id' => 'SF-SEV-001',
        ]);

        $valenciaCommercial = User::factory()->create([
            'name' => 'Ana Valencia',
            'dealership' => 'Valencia',
            'dealership_id' => $topDealership->id,
            'salesforce_user_id' => 'SF-VAL-001',
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $valenciaCommercial->id,
            'salesforce_user_id' => 'SF-VAL-001',
            'seller_name' => 'Ana Valencia',
            'total_sales' => 12,
            'synced_at' => now(),
        ]);

        SalesLeaderboardEntry::query()->create([
            'ranking_position' => 2,
            'user_id' => $sevillaCommercial->id,
            'salesforce_user_id' => 'SF-SEV-001',
            'seller_name' => 'Laura Sevilla',
            'total_sales' => 8,
            'synced_at' => now(),
        ]);

        PurchaseLeaderboardEntry::query()->create([
            'ranking_position' => 1,
            'user_id' => $valenciaCommercial->id,
            'salesforce_user_id' => 'SF-VAL-001',
            'seller_name' => 'Ana Valencia',
            'total_purchases' => 9,
            'synced_at' => now(),
        ]);

        PurchaseLeaderboardEntry::query()->create([
            'ranking_position' => 2,
            'user_id' => $sevillaCommercial->id,
            'salesforce_user_id' => 'SF-SEV-001',
            'seller_name' => 'Laura Sevilla',
            'total_purchases' => 4,
            'synced_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('dealerships.show', $dealership));

        $response
            ->assertOk()
            ->assertSee('Laura Sevilla')
            ->assertSee('8 V')
            ->assertSee('4 C')
            ->assertSee('Top 2 en ventas')
            ->assertSee('Top 2 en compras');
    }

    public function test_regular_users_can_open_the_dealership_detail_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);

        $dealership = Dealership::factory()->create([
            'name' => 'Alicante',
        ]);

        $this->actingAs($user)
            ->get(route('dealerships.show', $dealership))
            ->assertOk()
            ->assertSee('Alicante');
    }

    public function test_regular_users_cannot_open_the_dealership_list_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);

        $this->actingAs($user)
            ->get(route('dealerships.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/delegaciones')
            ->assertForbidden();
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
