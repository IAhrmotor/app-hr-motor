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

    public function test_manager_without_permission_cannot_open_admin_bulletin(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.tablon.index'))
            ->assertForbidden();
    }

    public function test_manager_with_bulletin_permission_can_manage_and_log_posts(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        AdminPermissionGrant::query()->create([
            'permission_key' => 'bulletin.manage',
            'user_id' => $manager->id,
            'group_id' => null,
            'group_role' => null,
            'granted_by_user_id' => $admin->id,
        ]);

        $this->actingAs($manager)
            ->get(BulletinPostResource::getUrl())
            ->assertOk()
            ->assertSee(html_entity_decode('Tabl&oacute;n'));

        $this->actingAs($manager)
            ->post(route('admin.tablon.store'), [
                'title' => 'Nuevo aviso interno',
                'body' => 'Texto del aviso para toda la plantilla.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $this->assertDatabaseHas('bulletin_posts', [
            'title' => 'Nuevo aviso interno',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('bulletin_activity_logs', [
            'action' => BulletinActivityLog::ACTION_CREATED,
            'target_name' => 'Nuevo aviso interno',
        ]);

        $this->actingAs($admin)
            ->get(BulletinPostResource::getUrl('logs'))
            ->assertOk()
            ->assertSee('Logs del tablón')
            ->assertSee('Alta')
            ->assertSee('Nuevo aviso interno');
    }

    public function test_admin_bulletin_filament_form_is_available_for_creating_publications(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(BulletinPostResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Título')
            ->assertSee('Contenido')
            ->assertSee('Publicar ahora')
            ->assertSee('Escribe');
    }

    public function test_publishing_a_bulletin_sends_a_priority_notification_to_active_users(): void
    {
        Notification::fake();

        $publisher = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $recipient = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);
        $inactiveRecipient = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => false,
            'disabled_at' => now(),
        ]);

        $this->actingAs($publisher)
            ->post(route('admin.tablon.store'), [
                'title' => 'Aviso urgente',
                'body' => html_entity_decode('Se ha publicado una nueva comunicaci&oacute;n interna.'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.tablon.index'));

        Notification::assertSentTo($publisher, AdminPriorityNotification::class);
        Notification::assertSentTo($recipient, AdminPriorityNotification::class);
        Notification::assertNotSentTo($inactiveRecipient, AdminPriorityNotification::class);
    }

    public function test_saving_a_bulletin_as_draft_keeps_it_unpublished_and_without_notifications(): void
    {
        Notification::fake();

        $publisher = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($publisher)
            ->post(route('admin.tablon.store'), [
                'title' => 'Borrador interno',
                'body' => 'Esto no debe publicarse todavía.',
                'is_published' => '0',
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $post = BulletinPost::query()->where('title', 'Borrador interno')->firstOrFail();

        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        Notification::assertNothingSent();
    }

    public function test_admin_bulletin_index_shows_a_publish_button_for_drafts_and_can_publish_them(): void
    {
        Notification::fake();

        $publisher = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $attachment = UploadedFile::fake()->image('anuncio.png', 1200, 800);

        $this->actingAs($publisher)
            ->post(route('admin.tablon.store'), [
                'title' => 'Borrador con acción',
                'body' => 'Contenido del borrador.',
                'is_published' => '0',
                'images' => [$attachment],
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $post = BulletinPost::query()->where('title', 'Borrador con acción')->with('attachments')->firstOrFail();

        $response = $this->actingAs($publisher)->get(BulletinPostResource::getUrl());
        $response
            ->assertOk()
            ->assertSee('Publicar', false);

        $this->actingAs($publisher)
            ->put(route('admin.tablon.update', $post), [
                'title' => $post->title,
                'body' => $post->body,
                'is_published' => '1',
                'keep_attachment_ids' => $post->attachments->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $post = $post->fresh(['attachments']);

        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
        $this->assertSame(1, $post->attachments->count());
        Notification::assertSentTo($publisher, AdminPriorityNotification::class);
    }

    public function test_edited_bulletins_show_edited_timestamp_on_the_public_board(): void
    {
        $author = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $viewer = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);

        Carbon::setTestNow(now()->setTime(10, 0, 0));

        $post = BulletinPost::query()->create([
            'title' => 'Anuncio inicial',
            'body' => 'Texto original.',
            'is_published' => true,
            'published_at' => now(),
            'created_by_user_id' => $author->id,
            'updated_by_user_id' => $author->id,
        ]);

        Carbon::setTestNow(now()->addMinute());

        $this->actingAs($author)
            ->put(route('admin.tablon.update', $post), [
                'title' => 'Anuncio inicial',
                'body' => 'Texto original con una edición.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $this->actingAs($viewer)
            ->get(route('tablon.index'))
            ->assertOk()
            ->assertSee('Editado')
            ->assertSee(now()->format('d/m/Y H:i'));

        Carbon::setTestNow();
    }

    public function test_bulletin_images_are_uploaded_and_rendered_on_the_public_board(): void
    {
        $publisher = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $image = UploadedFile::fake()->image('anuncio.png', 1200, 800);

        $this->actingAs($publisher)
            ->post(route('admin.tablon.store'), [
                'title' => 'Anuncio con imagen',
                'body' => 'Mira la imagen adjunta.',
                'is_published' => '1',
                'images' => [$image],
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $post = BulletinPost::query()->where('title', 'Anuncio con imagen')->firstOrFail();

        $this->assertSame(1, $post->attachments()->count());
        $attachment = $post->attachments()->firstOrFail();

        $this->actingAs($publisher)
            ->get(route('tablon.index'))
            ->assertOk()
            ->assertSee($attachment->image_url, false)
            ->assertSee('Mira la imagen adjunta.');
    }

    public function test_bulletin_edit_can_remove_existing_images(): void
    {
        $publisher = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $image = UploadedFile::fake()->image('anuncio.png', 1200, 800);

        $this->actingAs($publisher)
            ->post(route('admin.tablon.store'), [
                'title' => 'Anuncio editable',
                'body' => 'Texto inicial con foto.',
                'is_published' => '1',
                'images' => [$image],
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $post = BulletinPost::query()->where('title', 'Anuncio editable')->firstOrFail();
        $attachment = $post->attachments()->firstOrFail();

        $this->actingAs($publisher)
            ->put(route('admin.tablon.update', $post), [
                'title' => 'Anuncio editable',
                'body' => 'Texto inicial con foto.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $this->assertDatabaseMissing('bulletin_post_attachments', [
            'id' => $attachment->id,
        ]);
        $this->assertFalse(Storage::disk('public')->exists($attachment->image_path));
        $this->assertSame(0, $post->fresh()->attachments()->count());
    }

    public function test_bulletin_mentions_render_as_clickable_profile_links(): void
    {
        $publisher = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $mentionedUser = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
            'name' => 'Ana López',
        ]);

        $this->actingAs($publisher)
            ->post(route('admin.tablon.store'), [
                'title' => 'Aviso con mención',
                'body' => 'Hola @Ana López, revisa este anuncio.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.tablon.index'));

        $this->actingAs($mentionedUser)
            ->get(route('tablon.index'))
            ->assertOk()
            ->assertSee(route('users.show', $mentionedUser), false)
            ->assertSee('@Ana López', false)
            ->assertSee('text-sky-600', false);
    }
}

