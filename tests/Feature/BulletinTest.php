<?php

namespace Tests\Feature;

use App\Filament\Resources\Bulletins\BulletinPostResource;
use App\Models\BulletinActivityLog;
use App\Models\AdminPermissionGrant;
use App\Models\BulletinPost;
use App\Models\BulletinPostAttachment;
use App\Models\User;
use App\Notifications\AdminPriorityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BulletinTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory('bulletin-posts');

        parent::tearDown();
    }

    public function test_public_bulletin_is_visible_to_all_authenticated_users(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);
        $creator = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        BulletinPost::query()->create([
            'title' => 'Aviso visible',
            'body' => 'Este contenido debe verse para todos.',
            'is_published' => true,
            'published_at' => now(),
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $creator->id,
        ]);

        BulletinPost::query()->create([
            'title' => 'Borrador oculto',
            'body' => html_entity_decode('No deber&iacute;a aparecer en el tabl&oacute;n p&uacute;blico.'),
            'is_published' => false,
            'published_at' => null,
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $creator->id,
        ]);

        $response = $this->actingAs($user)->get(route('tablon.index'));

        $response
            ->assertOk()
            ->assertSee('Tabl&oacute;n de anuncios', false)
            ->assertSee('Aviso visible')
            ->assertSee(route('users.show', $creator))
            ->assertSee($creator->avatar_url)
            ->assertDontSee('Borrador oculto');
    }
}

