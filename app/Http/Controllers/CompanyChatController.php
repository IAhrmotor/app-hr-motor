<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use App\Notifications\CompanyChatMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyChatController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();
        $search = trim((string) $request->query('search'));

        if ($request->filled('recipient')) {
            $recipient = User::query()
                ->whereKey($request->integer('recipient'))
                ->whereKeyNot($authUser->id)
                ->where('is_active', true)
                ->firstOrFail();

            $conversation = $this->findOrCreateConversation($authUser, $recipient);

            return redirect()->route('chat.beta', [
                'conversation' => $conversation->id,
            ]);
        }

        $conversations = CompanyChatConversation::query()
            ->forUser($authUser)
            ->with(['userOne', 'userTwo'])
            ->withCount([
                'messages as unread_messages_count' => function ($query) use ($authUser): void {
                    $query->whereNull('read_at')
                        ->where('sender_id', '!=', $authUser->id);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $selectedConversation = null;
        $conversationId = $request->integer('conversation');

        if ($conversationId) {
            $selectedConversation = $conversations->firstWhere('id', $conversationId);
        }

        if (! $selectedConversation) {
            $selectedConversation = $conversations->first();
        }

        if ($selectedConversation) {
            $selectedConversation->load([
                'userOne',
                'userTwo',
                'messages' => function ($query) {
                    $query->with('sender')->orderBy('created_at');
                },
            ]);

            $this->markConversationAsRead($selectedConversation, $authUser);
            $this->markConversationNotificationsAsRead($selectedConversation, $authUser);
            $selectedConversation->refresh();
            $selectedConversation->load([
                'userOne',
                'userTwo',
                'messages' => function ($query) {
                    $query->with('sender')->orderBy('created_at');
                },
            ]);
        }

        $people = User::query()
            ->where('is_active', true)
            ->whereKeyNot($authUser->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subquery) use ($search): void {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('dealership', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(12)
            ->get();

        return view('tools.chat-beta', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'people' => $people,
            'search' => $search,
        ]);
    }

    public function storeMessage(Request $request, CompanyChatConversation $conversation): JsonResponse|RedirectResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,svg,pdf,txt,md,csv,doc,docx,xls,xlsx,ppt,pptx,zip,rar'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $attachments = $this->storeAttachments($request);

        if ($body === '' && $attachments === []) {
            throw ValidationException::withMessages([
                'body' => 'Escribe un mensaje o adjunta un archivo.',
            ]);
        }

        $message = DB::transaction(function () use ($conversation, $request, $body, $attachments): CompanyChatMessage {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => $body,
                'attachments' => $attachments,
                'read_at' => null,
            ]);

            $conversation->forceFill([
                'last_message_at' => $message->created_at ?? now(),
                'last_message_excerpt' => $message->preview_text,
            ])->save();

            return $message->load('sender');
        });

        if ($recipient = $conversation->otherParticipant($request->user())) {
            $recipient->notify(new CompanyChatMessageNotification($conversation, $message, $request->user()));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'preview_text' => $message->preview_text,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender?->name,
                    'sender_chat_role_label' => app_chat_role_label($message->sender),
                    'is_mine' => true,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'created_at_label' => $message->created_at?->translatedFormat('H:i'),
                    'show_time' => true,
                    'read_at' => $message->read_at?->toIso8601String(),
                    'attachments' => $this->formatAttachmentsForPayload($message),
                ],
                'conversation_id' => $conversation->id,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'last_message_excerpt' => $conversation->last_message_excerpt,
            ]);
        }

        return redirect()->route('chat.beta', [
            'conversation' => $conversation->id,
        ]);
    }

    public function messages(Request $request, CompanyChatConversation $conversation): JsonResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);

        $authUser = $request->user();

        $this->markConversationAsRead($conversation, $authUser);
        $this->markConversationNotificationsAsRead($conversation, $authUser);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->values();

        $messagesPayload = $messages->map(function (CompanyChatMessage $message, int $index) use ($authUser, $messages): array {
            $nextMessage = $messages->get($index + 1);
            $currentLabel = $message->created_at?->translatedFormat('H:i');
            $nextLabel = $nextMessage?->created_at?->translatedFormat('H:i');

            return [
                'id' => $message->id,
                'body' => $message->body,
                'preview_text' => $message->preview_text,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name,
                'sender_chat_role_label' => app_chat_role_label($message->sender),
                'is_mine' => $message->sender_id === $authUser->id,
                'created_at' => $message->created_at?->toIso8601String(),
                'created_at_label' => $currentLabel,
                'show_time' => $nextLabel !== $currentLabel,
                'read_at' => $message->read_at?->toIso8601String(),
                'attachments' => $this->formatAttachmentsForPayload($message),
            ];
        });

        $partner = $conversation->otherParticipant($authUser);

        return response()->json([
            'conversation_id' => $conversation->id,
            'partner_name' => $partner?->name,
            'partner_avatar_url' => $partner?->avatar_url,
            'partner_chat_role_label' => app_chat_role_label($partner),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'messages' => $messagesPayload,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();

        $conversations = CompanyChatConversation::query()
            ->forUser($authUser)
            ->with(['userOne', 'userTwo'])
            ->withCount([
                'messages as unread_messages_count' => function ($query) use ($authUser): void {
                    $query->whereNull('read_at')
                        ->where('sender_id', '!=', $authUser->id);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(static function (CompanyChatConversation $conversation) use ($authUser): array {
                $partner = $conversation->otherParticipant($authUser);

                return [
                    'id' => $conversation->id,
                    'partner_name' => $partner?->name,
                    'partner_avatar_url' => $partner?->avatar_url,
                    'partner_chat_role_label' => app_chat_role_label($partner),
                    'last_message_excerpt' => $conversation->last_message_excerpt,
                    'last_message_at_label' => $conversation->last_message_at?->translatedFormat('d/m H:i'),
                    'unread_messages_count' => (int) ($conversation->unread_messages_count ?? 0),
                ];
            });

        return response()->json([
            'unread_messages_total' => $conversations->sum('unread_messages_count'),
            'conversations' => $conversations,
        ]);
    }

    private function findOrCreateConversation(User $firstUser, User $secondUser): CompanyChatConversation
    {
        [$userOneId, $userTwoId] = CompanyChatConversation::sortParticipantIds($firstUser, $secondUser);

        return DB::transaction(function () use ($userOneId, $userTwoId): CompanyChatConversation {
            return CompanyChatConversation::query()->firstOrCreate([
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]);
        });
    }

    private function markConversationAsRead(CompanyChatConversation $conversation, User $user): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function markConversationNotificationsAsRead(CompanyChatConversation $conversation, User $user): void
    {
        $user->unreadNotifications()
            ->where('type', CompanyChatMessageNotification::class)
            ->where('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function storeAttachments(Request $request): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $directory = storage_path('app/public/chat/attachments');
        File::ensureDirectoryExists($directory);

        $storedAttachments = [];

        foreach ((array) $request->file('attachments') as $file) {
            if (! $file) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $size = (int) $file->getSize();
            $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $filename = Str::uuid()->toString() . '.' . $extension;
            $file->move($directory, $filename);
            $path = 'chat/attachments/' . $filename;

            $storedAttachments[] = [
                'path' => $path,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => $size,
                'is_image' => str_starts_with($mimeType, 'image/'),
                'url' => Storage::disk('public')->url($path),
            ];
        }

        return $storedAttachments;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatAttachmentsForPayload(CompanyChatMessage $message): array
    {
        return collect($message->attachments ?? [])
            ->map(function (array $attachment): array {
                $path = (string) ($attachment['path'] ?? '');
                $mimeType = (string) ($attachment['mime_type'] ?? 'application/octet-stream');
                $originalName = (string) ($attachment['original_name'] ?? '');

                return [
                    'path' => $path,
                    'original_name' => $originalName !== '' ? $originalName : (basename($path) !== '' ? basename($path) : 'archivo'),
                    'mime_type' => $mimeType,
                    'size' => (int) ($attachment['size'] ?? 0),
                    'size_label' => $this->formatBytes((int) ($attachment['size'] ?? 0)),
                    'is_image' => (bool) ($attachment['is_image'] ?? str_starts_with($mimeType, 'image/')),
                    'url' => (string) ($attachment['url'] ?? Storage::disk('public')->url($path)),
                ];
            })
            ->values()
            ->all();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }
}
