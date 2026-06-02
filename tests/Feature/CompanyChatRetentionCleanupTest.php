<?php

namespace Tests\Feature;

use App\Mail\ChatRetentionCleanupFailedMail;
use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\CompanyChatRetentionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CompanyChatRetentionCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_chat_retention_cleanup_removes_messages_older_than_six_months_and_keeps_recent_ones(): void
    {
        Carbon::setTestNow('2026-05-28 05:30:00');
        Storage::fake('public');
        Mail::fake();

        $sender = User::factory()->create([
            'name' => 'Usuario Antiguo',
            'email' => 'antiguo@example.com',
        ]);
        $recipient = User::factory()->create([
            'name' => 'Usuario Receptor',
            'email' => 'receptor@example.com',
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        Storage::disk('public')->put('chat/attachments/old-file.txt', 'old');

        $oldMessage = CompanyChatMessage::query()->forceCreate([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje antiguo',
            'attachments' => [
                [
                    'path' => 'chat/attachments/old-file.txt',
                    'original_name' => 'old-file.txt',
                    'mime_type' => 'text/plain',
                    'size' => 3,
                    'is_image' => false,
                ],
            ],
            'created_at' => Carbon::parse('2025-11-27 08:00:00'),
            'updated_at' => Carbon::parse('2025-11-27 08:00:00'),
        ]);

        $recentMessage = CompanyChatMessage::query()->forceCreate([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $recipient->id,
            'body' => 'Mensaje reciente',
            'created_at' => Carbon::parse('2026-03-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-01 10:00:00'),
        ]);

        $conversation->forceFill([
            'last_message_at' => $recentMessage->created_at,
            'last_message_excerpt' => $recentMessage->preview_text,
        ])->save();

        $this->artisan('chat:purge-expired-messages')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('company_chat_messages', [
            'id' => $oldMessage->id,
        ]);
        $this->assertDatabaseHas('company_chat_messages', [
            'id' => $recentMessage->id,
        ]);
        Storage::disk('public')->assertMissing('chat/attachments/old-file.txt');
        Mail::assertNothingSent();

        $retentionLog = CompanyChatRetentionLog::query()->latest('id')->first();

        $this->assertNotNull($retentionLog);
        $this->assertSame('success', $retentionLog->status);
        $this->assertSame(1, $retentionLog->deleted_count);
        $this->assertSame(0, $retentionLog->error_count);
        $this->assertSame('cron', $retentionLog->source);
        $this->assertSame([$sender->id], $retentionLog->affected_user_ids);
        $this->assertSame([sprintf('%s (%d)', $sender->name, 1)], $retentionLog->affected_users);

        $conversation->refresh();
        $this->assertSame('Mensaje reciente', $conversation->last_message_excerpt);

        Carbon::setTestNow();
    }

    public function test_chat_retention_cleanup_sends_mail_when_a_delete_fails(): void
    {
        Carbon::setTestNow('2026-05-28 05:30:00');
        Mail::fake();

        $sender = User::factory()->create([
            'name' => 'Usuario Antiguo',
            'email' => 'antiguo@example.com',
        ]);
        $recipient = User::factory()->create([
            'name' => 'Usuario Receptor',
            'email' => 'receptor@example.com',
        ]);

        $conversation = CompanyChatConversation::query()->create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        CompanyChatMessage::query()->forceCreate([
            'company_chat_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Mensaje antiguo con error',
            'attachments' => [
                [
                    'path' => 'chat/attachments/failing-file.txt',
                    'original_name' => 'failing-file.txt',
                    'mime_type' => 'text/plain',
                    'size' => 10,
                    'is_image' => false,
                ],
            ],
            'created_at' => Carbon::parse('2025-11-27 08:00:00'),
            'updated_at' => Carbon::parse('2025-11-27 08:00:00'),
        ]);

        $disk = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('delete')->andThrow(new \RuntimeException('No se pudo eliminar el archivo adjunto.'));
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);

        $this->artisan('chat:purge-expired-messages')
            ->assertExitCode(1);

        Mail::assertSent(ChatRetentionCleanupFailedMail::class, function (ChatRetentionCleanupFailedMail $mail): bool {
            return $mail->hasTo('informatica@hrmotor.com')
                && $mail->cutoff === '2025-11-28 05:30:00'
                && $mail->deletedCount === 0
                && $mail->errors !== [];
        });

        $retentionLog = CompanyChatRetentionLog::query()->latest('id')->first();

        $this->assertNotNull($retentionLog);
        $this->assertSame('failed', $retentionLog->status);
        $this->assertSame(0, $retentionLog->deleted_count);
        $this->assertGreaterThanOrEqual(1, $retentionLog->error_count);
        $this->assertSame('cron', $retentionLog->source);
        $this->assertSame([], $retentionLog->affected_user_ids);

        Carbon::setTestNow();
    }
}
