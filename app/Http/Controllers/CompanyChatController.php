<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'body' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $message = DB::transaction(function () use ($conversation, $request, $validated): CompanyChatMessage {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => $validated['body'],
                'read_at' => now(),
            ]);

            $conversation->forceFill([
                'last_message_at' => $message->created_at ?? now(),
                'last_message_excerpt' => str($message->body)->squish()->limit(120)->toString(),
            ])->save();

            return $message->load('sender');
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender?->name,
                    'sender_role_label' => app_chat_role_label($message->sender),
                    'is_mine' => true,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'created_at_label' => $message->created_at?->translatedFormat('H:i'),
                    'read_at' => $message->read_at?->toIso8601String(),
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

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(fn (CompanyChatMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name,
                'sender_role_label' => app_chat_role_label($message->sender),
                'is_mine' => $message->sender_id === $authUser->id,
                'created_at' => $message->created_at?->toIso8601String(),
                'created_at_label' => $message->created_at?->translatedFormat('H:i'),
                'read_at' => $message->read_at?->toIso8601String(),
            ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'messages' => $messages,
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
                    'partner_role_label' => app_chat_role_label($partner),
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
}
