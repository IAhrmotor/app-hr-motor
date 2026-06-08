<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatGroup;
use App\Models\CompanyChatFavoriteContact;
use App\Models\CompanyChatMessage;
use App\Models\CompanyChatMessageRead;
use App\Models\PolicyAcceptance;
use App\Models\User;
use App\Notifications\CompanyChatMessageNotification;
use App\Support\ChatPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CompanyChatController extends Controller
{
    private const MAX_ATTACHMENT_TOTAL_BYTES = 31457280;
    private const MAX_ATTACHMENT_FILE_KILOBYTES = 30720;

    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();
        $search = trim((string) $request->query('search'));
        $policyVersion = ChatPolicy::version();
        $policyAccepted = $this->hasAcceptedChatPolicy($authUser);
        $favoriteUserIds = $this->favoriteUserIdsFor($authUser);
        $chatGroups = $authUser->chatGroups()
            ->withCount('participants')
            ->with(['participants' => function ($query): void {
                $query->orderBy('name');
            }, 'conversation' => function ($query): void {
                $query->with(['messages' => function ($messageQuery): void {
                    $messageQuery->with('reads');
                }]);
            }])
            ->get();
        $chatGroups = $this->sortChatGroupsForSidebar($chatGroups);

        $conversations = CompanyChatConversation::query()
            ->forUser($authUser)
            ->whereNull('company_chat_group_id')
            ->with(['userOne', 'userTwo'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $conversations->each(function (CompanyChatConversation $conversation) use ($authUser): void {
            $conversation->setAttribute('unread_messages_count', $this->conversationUnreadMessagesCount($conversation, $authUser));
        });

        $chatGroups->each(function (CompanyChatGroup $chatGroup) use ($authUser): void {
            $chatGroup->setRelation('conversation', $chatGroup->conversation);
            $chatGroup->conversation?->setAttribute('unread_messages_count', $this->conversationUnreadMessagesCount($chatGroup->conversation, $authUser));
        });

        if ($request->boolean('ajax')) {
            abort_unless($policyAccepted, 403);

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

            return response()->json([
                'html' => view('tools.chat-beta.partials.search-results', [
                    'people' => $people,
                    'search' => $search,
                    'favoriteUserIds' => $favoriteUserIds,
                ])->render(),
            ]);
        }

        if ($policyAccepted && $request->filled('recipient')) {
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

        if ($policyAccepted && $request->filled('group')) {
            $group = CompanyChatGroup::query()
                ->whereKey($request->integer('group'))
                ->whereHas('participants', function ($query) use ($authUser): void {
                    $query->whereKey($authUser->id);
                })
                ->firstOrFail();

            $conversation = $this->findOrCreateGroupConversation($group);

            return redirect()->route('chat.beta', [
                'conversation' => $conversation->id,
            ]);
        }

        if (! $policyAccepted) {
            return view('tools.chat-beta', [
                'conversations' => collect(),
                'selectedConversation' => null,
                'people' => collect(),
                'favoriteContacts' => [],
                'teamUsers' => [],
                'search' => $search,
                'favoriteUserIds' => [],
                'chatGroups' => collect(),
                'policyAccepted' => false,
                'policyVersion' => $policyVersion,
                'policyStatusUrl' => route('chat.beta.policy.status'),
                'policyAcceptUrl' => route('chat.beta.policy.accept'),
                'policyReturnRecipient' => $request->filled('recipient') ? $request->integer('recipient') : null,
                'policyReturnConversation' => $request->filled('conversation') ? $request->integer('conversation') : null,
                'policyReturnGroup' => $request->filled('group') ? $request->integer('group') : null,
            ]);
        }

        $conversations = CompanyChatConversation::query()
            ->forUser($authUser)
            ->whereNull('company_chat_group_id')
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

            if (! $selectedConversation) {
                $selectedConversation = CompanyChatConversation::query()
                    ->forUser($authUser)
                    ->whereKey($conversationId)
                    ->with(['userOne', 'userTwo', 'chatGroup.participants'])
                    ->first();
            }
        }

        if (! $selectedConversation) {
            $selectedConversation = $conversations->first();
        }

        if ($selectedConversation) {
            $selectedConversation->load([
                'userOne',
                'userTwo',
                'chatGroup.participants',
                'messages' => function ($query) {
                    $query->withTrashed()->with('sender')->orderBy('created_at');
                },
            ]);

            $this->markConversationAsRead($selectedConversation, $authUser);
            $this->markConversationNotificationsAsRead($selectedConversation, $authUser);
            $selectedConversation->refresh();
            $selectedConversation->load([
                'userOne',
                'userTwo',
                'chatGroup.participants',
                'messages' => function ($query) {
                    $query->withTrashed()->with('sender')->orderBy('created_at');
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

        $favoriteContacts = $this->favoriteContactsPayload($authUser);

        $teamUsers = User::query()
            ->where('is_active', true)
            ->whereKeyNot($authUser->id)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (User $user): string => $user->chat_role_label)
            ->sortKeys()
            ->map(function ($users, string $roleLabel): array {
                return [
                    'role_label' => $roleLabel,
                    'users' => $users->map(function (User $user): array {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'avatar_url' => $user->avatar_url,
                            'chat_role_label' => $user->chat_role_label,
                            'resolved_dealership_name' => $user->resolved_dealership_name,
                            'is_disabled' => $user->isDisabled(),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $privateConversations = $conversations
            ->reject(fn (CompanyChatConversation $conversation): bool => $conversation->isGroupConversation())
            ->values();

        return view('tools.chat-beta', [
            'conversations' => $privateConversations,
            'selectedConversation' => $selectedConversation,
            'people' => $people,
            'favoriteContacts' => $favoriteContacts,
            'teamUsers' => $teamUsers,
            'chatGroups' => $chatGroups,
            'search' => $search,
            'favoriteUserIds' => $favoriteUserIds,
            'policyAccepted' => true,
            'policyVersion' => $policyVersion,
            'policyStatusUrl' => route('chat.beta.policy.status'),
            'policyAcceptUrl' => route('chat.beta.policy.accept'),
            'policyReturnRecipient' => null,
            'policyReturnConversation' => null,
        ]);
    }

    public function policyStatus(Request $request): JsonResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();

        return response()->json([
            'policy_version' => ChatPolicy::version(),
            'accepted' => $this->hasAcceptedChatPolicy($authUser),
        ]);
    }

    public function acceptPolicy(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();
        $policyVersion = ChatPolicy::version();

        PolicyAcceptance::query()->updateOrCreate(
            [
                'user_id' => $authUser->id,
                'policy_version' => $policyVersion,
            ],
            [
                'user_email' => $authUser->email,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'source' => ChatPolicy::SOURCE_WEB_CHAT,
            ],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'accepted' => true,
                'policy_version' => $policyVersion,
            ]);
        }

        $redirectParams = [];

        if ($request->filled('recipient')) {
            $redirectParams['recipient'] = $request->integer('recipient');
        } elseif ($request->filled('conversation')) {
            $redirectParams['conversation'] = $request->integer('conversation');
        } elseif ($request->filled('group')) {
            $redirectParams['group'] = $request->integer('group');
        }

        return redirect()->route('chat.beta', $redirectParams);
    }

    public function storeMessage(Request $request, CompanyChatConversation $conversation): JsonResponse|RedirectResponse
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);

        $this->guardAgainstBrokenAttachmentUploads($request);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['file', 'max:' . self::MAX_ATTACHMENT_FILE_KILOBYTES, 'mimes:jpg,jpeg,png,webp,gif,svg,pdf,txt,md,csv,doc,docx,xls,xlsx,ppt,pptx,zip,rar'],
        ]);

        if ($this->attachmentsTotalSize($request) > self::MAX_ATTACHMENT_TOTAL_BYTES) {
            throw ValidationException::withMessages([
                'attachments' => 'El conjunto de archivos adjuntos supera el peso máximo permitido de 30 MB.',
            ]);
        }

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

        $this->notifyConversationParticipants($conversation, $message, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'preview_text' => $message->preview_text,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender?->name,
                    'sender_chat_role_label' => app_chat_role_label($message->sender),
                    'sender_is_active' => $message->sender?->is_active,
                    'sender_is_disabled' => $message->sender?->isDisabled(),
                    'is_mine' => true,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'updated_at' => $message->updated_at?->toIso8601String(),
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

    public function updateMessage(Request $request, CompanyChatConversation $conversation, CompanyChatMessage $message): JsonResponse|RedirectResponse
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);
        abort_unless($message->company_chat_conversation_id === $conversation->id, 404);
        abort_unless($message->canBeEditedOrDeletedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
        ]);

        $originalBody = trim((string) $message->body);
        $body = trim((string) ($validated['body'] ?? ''));
        $hasAttachments = filled($message->attachments);

        if ($body === '' && ! $hasAttachments) {
            throw ValidationException::withMessages([
                'body' => 'Escribe un mensaje o conserva un adjunto.',
            ]);
        }

        $message->forceFill([
            'body' => $body,
            'edited_at' => $body !== $originalBody ? now() : $message->edited_at,
        ])->save();

        $this->refreshConversationSummary($conversation);

        if ($request->expectsJson()) {
            $message = $message->load('sender');

            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'preview_text' => $message->preview_text,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender?->name,
                    'sender_chat_role_label' => app_chat_role_label($message->sender),
                    'sender_is_active' => $message->sender?->is_active,
                    'sender_is_disabled' => $message->sender?->isDisabled(),
                    'is_mine' => true,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'updated_at' => $message->updated_at?->toIso8601String(),
                    'edited_at' => $message->edited_at?->toIso8601String(),
                    'deleted_at' => $message->deleted_at?->toIso8601String(),
                    'created_at_label' => $message->created_at?->translatedFormat('H:i'),
                    'show_time' => true,
                    'read_at' => $message->read_at?->toIso8601String(),
                    'attachments' => $this->formatAttachmentsForPayload($message),
                ],
                'conversation_id' => $conversation->id,
                'last_message_at' => $conversation->fresh()->last_message_at?->toIso8601String(),
                'last_message_excerpt' => $conversation->fresh()->last_message_excerpt,
            ]);
        }

        return redirect()->route('chat.beta', [
            'conversation' => $conversation->id,
        ]);
    }

    public function destroyMessage(Request $request, CompanyChatConversation $conversation, CompanyChatMessage $message): JsonResponse|RedirectResponse
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);
        abort_unless($message->company_chat_conversation_id === $conversation->id, 404);
        abort_unless($message->canBeEditedOrDeletedBy($request->user()), 403);

        $attachmentPaths = collect($message->attachments ?? [])
            ->pluck('path')
            ->filter()
            ->map(static fn ($path): string => (string) $path)
            ->values()
            ->all();

        if ($attachmentPaths !== []) {
            Storage::disk('public')->delete($attachmentPaths);
        }

        $messageId = $message->id;
        $message->delete();

        $this->removeChatNotificationsForMessage($conversation, $request->user(), $messageId);
        $this->refreshConversationSummary($conversation);

        if ($request->expectsJson()) {
            return response()->json([
                'conversation_id' => $conversation->id,
                'last_message_at' => $conversation->fresh()->last_message_at?->toIso8601String(),
                'last_message_excerpt' => $conversation->fresh()->last_message_excerpt,
            ]);
        }

        return redirect()->route('chat.beta', [
            'conversation' => $conversation->id,
        ]);
    }

    public function downloadAttachment(Request $request, CompanyChatConversation $conversation, CompanyChatMessage $message, int $attachmentIndex): Response
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);
        abort_unless($message->company_chat_conversation_id === $conversation->id, 404);

        $attachments = collect($message->attachments ?? [])->values();

        abort_unless($attachmentIndex >= 0 && $attachmentIndex < $attachments->count(), 404);

        $attachment = $attachments->get($attachmentIndex);
        $path = (string) ($attachment['path'] ?? '');
        $mimeType = (string) ($attachment['mime_type'] ?? 'application/octet-stream');
        $downloadName = (string) ($attachment['original_name'] ?? '');
        $downloadName = $downloadName !== '' ? $downloadName : ((basename($path) !== '') ? basename($path) : 'archivo');

        abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, $downloadName, [
            'Content-Type' => $mimeType,
        ]);
    }

    public function messages(Request $request, CompanyChatConversation $conversation): JsonResponse
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);
        abort_unless($conversation->involves($request->user()), 403);

        $authUser = $request->user();
        $favoriteUserIds = $this->favoriteUserIdsFor($authUser);

        $this->markConversationAsRead($conversation, $authUser);
        $this->markConversationNotificationsAsRead($conversation, $authUser);

        $messages = $conversation->messages()
            ->withTrashed()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->values();

        $messagesPayload = $messages->map(function (CompanyChatMessage $message, int $index) use ($authUser, $messages): array {
            $nextMessage = $messages->get($index + 1);
            $currentLabel = $message->created_at?->translatedFormat('H:i');
            $nextLabel = $nextMessage?->created_at?->translatedFormat('H:i');
            $currentDateKey = $message->created_at?->format('Y-m-d');
            $nextDateKey = $nextMessage?->created_at?->format('Y-m-d');
            $isSystem = $message->isSystemMessage();

            return [
                'id' => $message->id,
                'body' => $message->body,
                'preview_text' => $message->preview_text,
                'sender_id' => $message->sender_id,
                'sender_name' => $isSystem ? null : $message->sender?->name,
                'sender_chat_role_label' => $isSystem ? 'Sistema' : app_chat_role_label($message->sender),
                'sender_is_active' => $isSystem ? false : $message->sender?->is_active,
                'sender_is_disabled' => $isSystem ? false : $message->sender?->isDisabled(),
                'is_mine' => ! $isSystem && $message->sender_id === $authUser->id,
                'is_system' => $isSystem,
                'created_at' => $message->created_at?->toIso8601String(),
                'updated_at' => $message->updated_at?->toIso8601String(),
                'edited_at' => $message->edited_at?->toIso8601String(),
                'deleted_at' => $message->deleted_at?->toIso8601String(),
                'created_at_label' => $currentLabel,
                'show_time' => $nextDateKey !== $currentDateKey || $nextLabel !== $currentLabel,
                'read_at' => $message->read_at?->toIso8601String(),
                'attachments' => $this->formatAttachmentsForPayload($message),
            ];
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            ...$this->conversationPayload($conversation, $authUser, $favoriteUserIds),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'messages' => $messagesPayload,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();
        $favoriteUserIds = $this->favoriteUserIdsFor($authUser);

        $privateConversations = CompanyChatConversation::query()
            ->forUser($authUser)
            ->whereNull('company_chat_group_id')
            ->with(['userOne', 'userTwo'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (CompanyChatConversation $conversation) use ($authUser, $favoriteUserIds): array {
                $unreadMessagesCount = $this->conversationUnreadMessagesCount($conversation, $authUser);

                return [
                    'id' => $conversation->id,
                    ...$this->conversationPayload($conversation, $authUser, $favoriteUserIds),
                    'last_message_excerpt' => $conversation->last_message_excerpt,
                    'last_message_at_label' => $conversation->last_message_at?->translatedFormat('d/m H:i'),
                    'unread_messages_count' => $unreadMessagesCount,
                ];
            });

        $chatGroups = $authUser->chatGroups()
            ->withCount('participants')
            ->with(['participants' => function ($query): void {
                $query->orderBy('name');
            }, 'conversation' => function ($query): void {
                $query->with(['messages' => function ($messageQuery): void {
                    $messageQuery->with('reads');
                }]);
            }])
            ->get();
        $chatGroups = $this->sortChatGroupsForSidebar($chatGroups);

        $chatGroupsPayload = $chatGroups->map(function (CompanyChatGroup $chatGroup) use ($authUser): array {
            $conversation = $chatGroup->conversation;
            $participants = $chatGroup->participants;

            return [
                'id' => $chatGroup->id,
                'group_id' => $chatGroup->id,
                'conversation_id' => $conversation?->id,
                'conversation_is_group' => true,
                'conversation_type_label' => 'Grupo',
                'conversation_name' => $chatGroup->name,
                'conversation_avatar_url' => asset('images/users/hrmotor-default-user-avatar.png'),
                'conversation_profile_url' => null,
                'conversation_chat_role_label' => 'Grupo',
                'conversation_dealership_name' => $participants->count() . ' participantes',
                'conversation_is_active' => true,
                'conversation_is_disabled' => false,
                'conversation_status_label' => $participants->count() . ' participantes',
                'conversation_is_favorite' => false,
                'conversation_participants_count' => $participants->count(),
                'last_message_excerpt' => $conversation?->last_message_excerpt,
                'last_message_at_label' => $conversation?->last_message_at?->translatedFormat('d/m H:i'),
                'unread_messages_count' => $this->conversationUnreadMessagesCount($conversation, $authUser),
            ];
        })->values();

        $favoriteContacts = $this->favoriteContactsPayload($authUser);

        return response()->json([
            'unread_messages_total' => $privateConversations->sum('unread_messages_count'),
            'conversations' => $privateConversations,
            'chat_groups' => $chatGroupsPayload,
            'favorite_contacts' => $favoriteContacts,
        ]);
    }

    public function toggleFavorite(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->ensureChatPolicyAccepted($request);
        abort_unless(app_can_access_chat_beta($request->user()), 403);

        $authUser = $request->user();
        abort_unless($user->is_active, 404);
        abort_unless($user->id !== $authUser->id, 403);

        $favorite = CompanyChatFavoriteContact::query()
            ->where('user_id', $authUser->id)
            ->where('favorite_user_id', $user->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorite = false;
        } else {
            CompanyChatFavoriteContact::query()->create([
                'user_id' => $authUser->id,
                'favorite_user_id' => $user->id,
            ]);

            $isFavorite = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'favorite_user_id' => $user->id,
                'is_favorite' => $isFavorite,
                'favorite_contacts' => $this->favoriteContactsPayload($authUser),
            ]);
        }

        return back();
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

    private function findOrCreateGroupConversation(CompanyChatGroup $group): CompanyChatConversation
    {
        return DB::transaction(function () use ($group): CompanyChatConversation {
            return CompanyChatConversation::query()->firstOrCreate([
                'company_chat_group_id' => $group->id,
            ]);
        });
    }

    /**
     * @param  Collection<int, CompanyChatGroup>  $chatGroups
     * @return Collection<int, CompanyChatGroup>
     */
    private function sortChatGroupsForSidebar(Collection $chatGroups): Collection
    {
        return $chatGroups
            ->sort(function (CompanyChatGroup $leftGroup, CompanyChatGroup $rightGroup): int {
                $leftConversation = $leftGroup->conversation;
                $rightConversation = $rightGroup->conversation;
                $leftTimestamp = $leftConversation?->last_message_at?->timestamp;
                $rightTimestamp = $rightConversation?->last_message_at?->timestamp;

                if ($leftTimestamp === null && $rightTimestamp === null) {
                    return strcasecmp($leftGroup->name, $rightGroup->name);
                }

                if ($leftTimestamp === null) {
                    return 1;
                }

                if ($rightTimestamp === null) {
                    return -1;
                }

                if ($leftTimestamp !== $rightTimestamp) {
                    return $rightTimestamp <=> $leftTimestamp;
                }

                return strcasecmp($leftGroup->name, $rightGroup->name);
            })
            ->values();
    }

    private function hasAcceptedChatPolicy(?User $user): bool
    {
        return (bool) $user && $user->hasAcceptedPolicyVersion(ChatPolicy::version());
    }

    private function ensureChatPolicyAccepted(Request $request): void
    {
        abort_unless($this->hasAcceptedChatPolicy($request->user()), 403);
    }

    private function markConversationAsRead(CompanyChatConversation $conversation, User $user): void
    {
        if ($conversation->isGroupConversation()) {
            $this->markGroupConversationAsRead($conversation, $user);

            return;
        }

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function markGroupConversationAsRead(CompanyChatConversation $conversation, User $user): void
    {
        $messages = $conversation->messages()
            ->with('sender')
            ->where(function ($query) use ($user): void {
                $query->whereNull('sender_id')
                    ->orWhere('sender_id', '!=', $user->id);
            })
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($messages as $message) {
            CompanyChatMessageRead::query()->updateOrCreate(
                [
                    'company_chat_message_id' => $message->id,
                    'user_id' => $user->id,
                ],
                [
                    'read_at' => $now,
                ],
            );

            $this->refreshGroupMessageReadState($conversation, $message);
        }
    }

    private function refreshGroupMessageReadState(CompanyChatConversation $conversation, CompanyChatMessage $message): void
    {
        if (! $conversation->isGroupConversation()) {
            return;
        }

        if ($message->isSystemMessage() || ! $message->sender instanceof User) {
            $requiredReaderIds = $conversation->chatGroup?->participants
                ->pluck('id')
                ->filter()
                ->values();
        } else {
            $requiredReaderIds = $conversation->participantsFor($message->sender)
                ->pluck('id')
                ->reject(fn (int $userId): bool => $userId === $message->sender_id)
                ->values();
        }

        if ($requiredReaderIds->isEmpty()) {
            if ($message->read_at === null) {
                $message->forceFill(['read_at' => now()])->save();
            }

            return;
        }

        $readCount = CompanyChatMessageRead::query()
            ->where('company_chat_message_id', $message->id)
            ->whereIn('user_id', $requiredReaderIds->all())
            ->distinct()
            ->count('user_id');

        if ($readCount >= $requiredReaderIds->count() && $message->read_at === null) {
            $message->forceFill(['read_at' => now()])->save();
        }
    }

    private function markConversationNotificationsAsRead(CompanyChatConversation $conversation, User $user): void
    {
        $user->unreadNotifications()
            ->where('type', CompanyChatMessageNotification::class)
            ->where('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);
    }

    private function conversationUnreadMessagesCount(?CompanyChatConversation $conversation, User $user): int
    {
        if (! $conversation) {
            return 0;
        }

        if ($conversation->isGroupConversation()) {
            return (int) $conversation->messages()
                ->where(function ($query) use ($user): void {
                    $query->whereNull('sender_id')
                        ->orWhere('sender_id', '!=', $user->id);
                })
                ->whereDoesntHave('reads', function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                })
                ->count();
        }

        return (int) $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    private function removeChatNotificationsForMessage(CompanyChatConversation $conversation, User $user, int $messageId): void
    {
        $recipients = $conversation->participantsFor($user)
            ->reject(fn (User $participant): bool => $participant->id === $user->id);

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $recipient) {
            $recipient->notifications()
                ->where('type', CompanyChatMessageNotification::class)
                ->where('data->conversation_id', $conversation->id)
                ->where('data->message_id', $messageId)
                ->delete();
        }
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
     * @return array<int, int>
     */
    private function favoriteUserIdsFor(User $user): array
    {
        return $user->chatFavoriteContacts()
            ->pluck('favorite_user_id')
            ->map(static fn ($value): int => (int) $value)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function favoriteContactsPayload(User $user): array
    {
        $favoriteUsers = User::query()
            ->where('is_active', true)
            ->whereIn('id', $this->favoriteUserIdsFor($user))
            ->orderBy('name')
            ->get();

        return $favoriteUsers->map(function (User $favoriteUser) use ($user): array {
            return [
                'id' => $favoriteUser->id,
                'name' => $favoriteUser->name,
                'avatar_url' => $favoriteUser->avatar_url,
                'chat_role_label' => $favoriteUser->chat_role_label,
                'resolved_dealership_name' => $favoriteUser->resolved_dealership_name,
                'is_disabled' => $favoriteUser->isDisabled(),
                'is_favorite' => true,
            ];
        })->values()->all();
    }

    private function notifyConversationParticipants(CompanyChatConversation $conversation, CompanyChatMessage $message, User $sender): void
    {
        $conversation->participantsFor($sender)
            ->reject(fn (User $participant): bool => $participant->id === $sender->id)
            ->each(function (User $participant) use ($conversation, $message, $sender): void {
                $participant->notify(new CompanyChatMessageNotification($conversation, $message, $sender));
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationPayload(CompanyChatConversation $conversation, User $authUser, array $favoriteUserIds = []): array
    {
        $isGroup = $conversation->isGroupConversation();
        $partner = $conversation->otherParticipant($authUser);
        $group = $conversation->chatGroup;
        $participants = $conversation->participantsFor($authUser);
        $participantsCount = $participants->count();
        $partnerRoleLabel = app_chat_role_label($partner);
        $partnerDealershipName = $partner?->resolved_dealership_name ?? 'Sin delegación';

        return [
            'conversation_is_group' => $isGroup,
            'conversation_type_label' => $isGroup ? 'Grupo' : 'Privada',
            'partner_id' => $isGroup ? null : $partner?->id,
            'partner_name' => $isGroup ? ($group?->name ?? 'Grupo de chat') : $partner?->name,
            'partner_avatar_url' => $isGroup
                ? asset('images/users/hrmotor-default-user-avatar.png')
                : ($partner?->avatar_url ?? asset('images/users/hrmotor-default-user-avatar.png')),
            'partner_profile_url' => $isGroup ? null : ($partner ? route('users.show', $partner) : null),
            'partner_chat_role_label' => $isGroup ? 'Grupo' : $partnerRoleLabel,
            'partner_dealership_name' => $isGroup ? ($participantsCount . ' participantes') : $partnerDealershipName,
            'partner_is_active' => $isGroup ? true : $partner?->is_active,
            'partner_is_disabled' => $isGroup ? false : $partner?->isDisabled(),
            'partner_status_label' => $isGroup
                ? ($participantsCount . ' participantes')
                : ($partner?->isDisabled() ? 'Usuario desactivado' : ($partner?->is_active ? 'Activo' : 'Pendiente')),
            'partner_is_favorite' => $isGroup ? false : ($partner?->id ? in_array($partner->id, $favoriteUserIds, true) : false),
            'conversation_name' => $isGroup ? ($group?->name ?? 'Grupo de chat') : ($partner?->name ?? 'Conversación'),
            'conversation_avatar_url' => $isGroup
                ? asset('images/users/hrmotor-default-user-avatar.png')
                : ($partner?->avatar_url ?? asset('images/users/hrmotor-default-user-avatar.png')),
            'conversation_profile_url' => $isGroup ? null : ($partner ? route('users.show', $partner) : null),
            'conversation_chat_role_label' => $isGroup ? 'Grupo' : $partnerRoleLabel,
            'conversation_dealership_name' => $isGroup ? ($participantsCount . ' participantes') : $partnerDealershipName,
            'conversation_is_active' => $isGroup ? true : $partner?->is_active,
            'conversation_is_disabled' => $isGroup ? false : $partner?->isDisabled(),
            'conversation_status_label' => $isGroup
                ? ($participantsCount . ' participantes')
                : ($partner?->isDisabled() ? 'Usuario desactivado' : ($partner?->is_active ? 'Activo' : 'Pendiente')),
            'conversation_is_favorite' => $isGroup ? false : ($partner?->id ? in_array($partner->id, $favoriteUserIds, true) : false),
            'conversation_participants_count' => $participantsCount,
            'conversation_participants_text' => $participants->pluck('name')->implode(', '),
            'conversation_participants' => $participants->map(function (User $participant) use ($favoriteUserIds): array {
                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'profile_url' => route('users.show', $participant),
                    'avatar_url' => $participant->avatar_url,
                    'resolved_dealership_name' => $participant->resolved_dealership_name,
                    'extra_role_label' => $participant->extra_role ? (User::extraRoleLabels()[$participant->extra_role] ?? ucfirst((string) $participant->extra_role)) : null,
                    'chat_role_label' => app_chat_role_label($participant),
                    'is_disabled' => $participant->isDisabled(),
                    'is_favorite' => in_array($participant->id, $favoriteUserIds, true),
                ];
            })->values()->all(),
            'conversation_participants_preview' => $participants->take(3)->map(function (User $participant) use ($favoriteUserIds): array {
                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'avatar_url' => $participant->avatar_url,
                    'chat_role_label' => app_chat_role_label($participant),
                    'resolved_dealership_name' => $participant->resolved_dealership_name,
                    'is_disabled' => $participant->isDisabled(),
                    'is_favorite' => in_array($participant->id, $favoriteUserIds, true),
                ];
            })->values()->all(),
        ];
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
            ->map(function (array $attachment, int $index) use ($message): array {
                $path = (string) ($attachment['path'] ?? '');
                $mimeType = (string) ($attachment['mime_type'] ?? 'application/octet-stream');
                $originalName = (string) ($attachment['original_name'] ?? '');
                $resolvedOriginalName = $originalName !== '' ? $originalName : (basename($path) !== '' ? basename($path) : 'archivo');

                return [
                    'path' => $path,
                    'original_name' => $resolvedOriginalName,
                    'mime_type' => $mimeType,
                    'size' => (int) ($attachment['size'] ?? 0),
                    'size_label' => $this->formatBytes((int) ($attachment['size'] ?? 0)),
                    'is_image' => (bool) ($attachment['is_image'] ?? str_starts_with($mimeType, 'image/')),
                    'url' => route('chat.beta.attachments.show', [
                        'conversation' => $message->company_chat_conversation_id,
                        'message' => $message->id,
                        'attachmentIndex' => $index,
                    ]),
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

    private function attachmentsTotalSize(Request $request): int
    {
        return collect((array) $request->file('attachments'))
            ->filter()
            ->sum(static fn ($file): int => (int) ($file?->getSize() ?? 0));
    }

    private function guardAgainstBrokenAttachmentUploads(Request $request): void
    {
        collect((array) $request->file('attachments'))
            ->filter()
            ->each(function (mixed $file, int $index): void {
                if (! $file instanceof UploadedFile) {
                    return;
                }

                if ($file->isValid()) {
                    return;
                }

                $message = match ($file->getError()) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño permitido por el servidor. Prueba con una versión más pequeña o revisa la configuración de subida.',
                    UPLOAD_ERR_PARTIAL => 'El archivo se ha subido solo parcialmente. Vuelve a intentarlo.',
                    UPLOAD_ERR_NO_FILE => 'No se ha recibido ningún archivo adjunto.',
                    UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene directorio temporal disponible para subir archivos.',
                    UPLOAD_ERR_CANT_WRITE => 'El servidor no ha podido escribir el archivo temporal.',
                    UPLOAD_ERR_EXTENSION => 'Una extensión del servidor ha bloqueado la subida de este archivo.',
                    default => 'No se pudo subir este archivo adjunto. Comprueba que no supera el tamaño permitido por el servidor e inténtalo de nuevo.',
                };

                throw ValidationException::withMessages([
                    "attachments.$index" => $message,
                ]);
            });
    }
}
