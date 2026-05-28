<?php

namespace App\Console\Commands;

use App\Mail\ChatRetentionCleanupFailedMail;
use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\CompanyChatRetentionLog;
use App\Notifications\CompanyChatMessageNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PurgeExpiredCompanyChatMessages extends Command
{
    protected $signature = 'chat:purge-expired-messages';

    protected $description = 'Elimina mensajes de chat con mas de seis meses y registra el resultado de la limpieza.';

    public function handle(): int
    {
        $cutoff = now()->subMonthsNoOverflow(6);
        $deletedCount = 0;
        $affectedUsers = [];
        $affectedConversations = [];
        $errors = [];

        CompanyChatMessage::query()
            ->with('sender:id,name,email')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($messages) use (&$deletedCount, &$affectedUsers, &$affectedConversations, &$errors): void {
                foreach ($messages as $message) {
                    try {
                        $attachmentPaths = collect($message->attachments ?? [])
                            ->pluck('path')
                            ->filter()
                            ->map(static fn ($path): string => (string) $path)
                            ->values()
                            ->all();

                        if ($attachmentPaths !== []) {
                            Storage::disk('public')->delete($attachmentPaths);
                        }

                        DB::table('notifications')
                            ->where('type', CompanyChatMessageNotification::class)
                            ->where('data->conversation_id', $message->company_chat_conversation_id)
                            ->where('data->message_id', $message->id)
                            ->delete();

                        $senderName = $message->sender?->name ?? $message->sender?->email ?? 'Usuario desconocido';
                        $senderId = (int) $message->sender_id;

                        $message->forceDelete();
                        $deletedCount++;

                        $affectedUsers[$senderId] = [
                            'id' => $senderId,
                            'name' => $senderName,
                            'count' => ($affectedUsers[$senderId]['count'] ?? 0) + 1,
                        ];
                        $affectedConversations[$message->company_chat_conversation_id] = true;
                    } catch (Throwable $exception) {
                        report($exception);

                        $errors[] = [
                            'message_id' => $message->id,
                            'conversation_id' => $message->company_chat_conversation_id,
                            'sender_id' => $message->sender_id,
                            'sender_name' => $message->sender?->name ?? $message->sender?->email ?? 'Usuario desconocido',
                            'error' => $exception->getMessage(),
                        ];
                    }
                }
            });

        foreach (array_keys($affectedConversations) as $conversationId) {
            $conversation = CompanyChatConversation::query()->find($conversationId);

            if (! $conversation) {
                continue;
            }

            $this->refreshConversationSummary($conversation);
        }

        $userList = collect($affectedUsers)
            ->sortBy('name')
            ->map(fn (array $user): string => sprintf('%s (%d)', $user['name'], $user['count']))
            ->values()
            ->all();

        $affectedUserIds = collect($affectedUsers)
            ->keys()
            ->map(static fn ($userId): int => (int) $userId)
            ->values()
            ->all();

        if ($errors !== []) {
            Log::channel('chat_retention')->error('Limpieza diaria del chat finalizada con errores.', [
                'cutoff' => $cutoff->toDateTimeString(),
                'deleted_count' => $deletedCount,
                'affected_users' => $userList,
                'errors' => $errors,
            ]);

            Mail::to('informatica@hrmotor.com')->send(new ChatRetentionCleanupFailedMail(
                cutoff: $cutoff->toDateTimeString(),
                deletedCount: $deletedCount,
                affectedUsers: $userList,
                errors: $errors,
            ));

            $this->storeRetentionLog(
                cutoff: $cutoff,
                status: 'failed',
                deletedCount: $deletedCount,
                affectedUserIds: $affectedUserIds,
                affectedUsers: $userList,
                errors: $errors,
                errorSummary: collect($errors)
                    ->take(3)
                    ->map(static fn (array $error): string => sprintf(
                        'Mensaje #%s (%s): %s',
                        $error['message_id'] ?? 'N/D',
                        $error['sender_name'] ?? 'N/D',
                        $error['error'] ?? 'Error desconocido'
                    ))
                    ->implode(' | '),
            );

            $this->error(sprintf(
                'Limpieza diaria del chat finalizada con errores. Mensajes eliminados: %d.',
                $deletedCount
            ));

            return self::FAILURE;
        }

        Log::channel('chat_retention')->info('Limpieza diaria del chat completada correctamente.', [
            'cutoff' => $cutoff->toDateTimeString(),
            'deleted_count' => $deletedCount,
            'affected_users' => $userList,
        ]);

        $this->storeRetentionLog(
            cutoff: $cutoff,
            status: 'success',
            deletedCount: $deletedCount,
            affectedUserIds: $affectedUserIds,
            affectedUsers: $userList,
            errors: [],
            errorSummary: null,
        );

        $this->info(sprintf(
            'Limpieza diaria del chat completada correctamente. Mensajes eliminados: %d.',
            $deletedCount
        ));

        return self::SUCCESS;
    }

    private function refreshConversationSummary(CompanyChatConversation $conversation): void
    {
        $latestMessage = $conversation->messages()
            ->withTrashed()
            ->with('sender')
            ->orderByDesc('created_at')
            ->first();

        $conversation->forceFill([
            'last_message_at' => $latestMessage?->created_at,
            'last_message_excerpt' => $latestMessage?->preview_text,
        ])->save();
    }

    /**
     * @param  array<int, int>  $affectedUserIds
     * @param  array<int, string>  $affectedUsers
     * @param  array<int, array<string, mixed>>  $errors
     */
    private function storeRetentionLog(
        \Illuminate\Support\Carbon $cutoff,
        string $status,
        int $deletedCount,
        array $affectedUserIds,
        array $affectedUsers,
        array $errors,
        ?string $errorSummary,
    ): void {
        try {
            CompanyChatRetentionLog::query()->create([
                'executed_at' => now(),
                'cutoff' => $cutoff,
                'status' => $status,
                'deleted_count' => $deletedCount,
                'affected_user_ids' => $affectedUserIds,
                'affected_users' => $affectedUsers,
                'error_count' => count($errors),
                'error_summary' => $errorSummary,
                'errors' => $errors,
                'source' => 'cron',
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
