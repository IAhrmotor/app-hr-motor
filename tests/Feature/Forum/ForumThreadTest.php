<?php

namespace Tests\Feature\Forum;

use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ForumThreadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        $forumImageDirectory = public_path('images/forum');

        if (File::exists($forumImageDirectory)) {
            File::deleteDirectory($forumImageDirectory);
        }

        parent::tearDown();
    }

    public function test_commercial_can_open_the_create_screen_and_create_a_forum_thread_with_attachments(): void
    {
        $commercial = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);

        $this->actingAs($commercial)
            ->get(route('forum.create'))
            ->assertOk()
            ->assertSee('Descripción del problema');

        $response = $this->actingAs($commercial)->post(route('forum.store'), [
            'title' => 'No puedo cerrar la oportunidad',
            'content' => 'Al intentar cerrar la oportunidad en Salesforce me devuelve un error y no veo qué campo falta.',
            'attachments' => [
                UploadedFile::fake()->image('captura-1.png', 1200, 900),
                UploadedFile::fake()->image('captura-2.png', 1200, 900),
            ],
        ]);

        $thread = ForumThread::query()->with('attachments')->first();

        $response
            ->assertRedirect(route('forum.show', $thread))
            ->assertSessionHas('success');

        $this->assertNotNull($thread);
        $this->assertSame($commercial->id, $thread->user_id);
        $this->assertSame(ForumThread::STATUS_OPEN, $thread->status);
        $this->assertCount(2, $thread->attachments);
        $this->assertFileExists(public_path($thread->attachments->first()->image_path));
    }

    public function test_manager_can_create_a_forum_thread(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)
            ->post(route('forum.store'), [
                'title' => 'Duda de gestor',
                'content' => 'Necesito validar el flujo del foro desde una cuenta de gestión para hacer pruebas completas.',
            ]);

        $thread = ForumThread::query()->first();

        $response
            ->assertRedirect(route('forum.show', $thread))
            ->assertSessionHas('success');

        $this->assertNotNull($thread);
        $this->assertSame($manager->id, $thread->user_id);
    }

    public function test_authenticated_user_can_reply_to_a_thread_with_attachments(): void
    {
        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $thread = ForumThread::query()->create([
            'user_id' => $creator->id,
            'title' => 'Duda sobre documentación',
            'content' => 'Necesito saber dónde encontrar el documento correcto para la entrega.',
            'status' => ForumThread::STATUS_OPEN,
        ]);

        $response = $this->actingAs($admin)->post(route('forum.reply', $thread), [
            'content' => 'Revisa la carpeta compartida de entregas y el check-list del concesionario.',
            'attachments' => [
                UploadedFile::fake()->image('respuesta.png', 1000, 800),
            ],
        ]);

        $reply = ForumReply::query()->with('attachments')->first();

        $response
            ->assertRedirect(route('forum.show', $thread))
            ->assertSessionHas('success');

        $this->assertNotNull($reply);
        $this->assertSame($thread->id, $reply->forum_thread_id);
        $this->assertSame($admin->id, $reply->user_id);
        $this->assertCount(1, $reply->attachments);
        $this->assertFileExists(public_path($reply->attachments->first()->image_path));
    }

    public function test_thread_creator_can_mark_thread_as_resolved_but_other_commercial_cannot(): void
    {
        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);
        $otherCommercial = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);

        $thread = ForumThread::query()->create([
            'user_id' => $creator->id,
            'title' => 'Duda con reserva',
            'content' => 'No sé qué paso seguir cuando la reserva ya está confirmada.',
            'status' => ForumThread::STATUS_OPEN,
        ]);

        $this->actingAs($otherCommercial)
            ->patch(route('forum.status.update', $thread), [
                'status' => ForumThread::STATUS_RESOLVED,
            ])
            ->assertForbidden();

        $this->actingAs($creator)
            ->patch(route('forum.status.update', $thread), [
                'status' => ForumThread::STATUS_RESOLVED,
            ])
            ->assertRedirect(route('forum.show', $thread))
            ->assertSessionHas('success');

        $thread->refresh();

        $this->assertSame(ForumThread::STATUS_RESOLVED, $thread->status);
        $this->assertSame($creator->id, $thread->resolved_by_user_id);
        $this->assertNotNull($thread->resolved_at);
    }

    public function test_manager_can_reopen_and_delete_threads(): void
    {
        $creator = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $thread = ForumThread::query()->create([
            'user_id' => $creator->id,
            'title' => 'Duda cerrada',
            'content' => 'La duda ya estaba cerrada pero hace falta reabrirla.',
            'status' => ForumThread::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by_user_id' => $creator->id,
        ]);

        ForumReply::query()->create([
            'forum_thread_id' => $thread->id,
            'user_id' => $creator->id,
            'content' => 'Primera respuesta del hilo.',
        ]);

        $this->actingAs($manager)
            ->patch(route('forum.status.update', $thread), [
                'status' => ForumThread::STATUS_OPEN,
            ])
            ->assertRedirect(route('forum.show', $thread))
            ->assertSessionHas('success');

        $thread->refresh();

        $this->assertSame(ForumThread::STATUS_OPEN, $thread->status);
        $this->assertNull($thread->resolved_at);
        $this->assertNull($thread->resolved_by_user_id);

        $this->actingAs($manager)
            ->delete(route('forum.destroy', $thread))
            ->assertRedirect(route('forum.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('forum_threads', [
            'id' => $thread->id,
        ]);
        $this->assertDatabaseMissing('forum_replies', [
            'forum_thread_id' => $thread->id,
        ]);
    }

    public function test_forum_index_orders_open_threads_first_and_then_resolved_by_creation_date(): void
    {
        Carbon::setTestNow('2026-04-07 12:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_COMMERCIAL,
        ]);

        $resolvedOlder = ForumThread::query()->create([
            'user_id' => $user->id,
            'title' => 'Resuelta antigua',
            'content' => 'Contenido resuelto antiguo.',
            'status' => ForumThread::STATUS_RESOLVED,
            'resolved_at' => now()->subDays(1),
            'resolved_by_user_id' => $user->id,
        ]);
        $resolvedOlder->forceFill([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ])->saveQuietly();

        $openOlder = ForumThread::query()->create([
            'user_id' => $user->id,
            'title' => 'Abierta antigua',
            'content' => 'Contenido abierto antiguo.',
            'status' => ForumThread::STATUS_OPEN,
        ]);
        $openOlder->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ])->saveQuietly();

        $resolvedNewer = ForumThread::query()->create([
            'user_id' => $user->id,
            'title' => 'Resuelta nueva',
            'content' => 'Contenido resuelto nuevo.',
            'status' => ForumThread::STATUS_RESOLVED,
            'resolved_at' => now()->subDay(),
            'resolved_by_user_id' => $user->id,
        ]);
        $resolvedNewer->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $openNewer = ForumThread::query()->create([
            'user_id' => $user->id,
            'title' => 'Abierta nueva',
            'content' => 'Contenido abierto nuevo.',
            'status' => ForumThread::STATUS_OPEN,
        ]);
        $openNewer->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();

        $response = $this->actingAs($user)->get(route('forum.index'));

        $response
            ->assertOk()
            ->assertSee(route('forum.create'), false)
            ->assertSeeInOrder([
                $openNewer->title,
                $openOlder->title,
                $resolvedNewer->title,
                $resolvedOlder->title,
            ]);

        Carbon::setTestNow();
    }
}
