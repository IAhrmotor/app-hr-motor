<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatConversationAccessAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminConversationAccessController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->query('search'));
        $conversationId = $this->sanitizeConversationId($request->query('conversation'));
        $hasAuditTable = Schema::hasTable('company_chat_conversation_access_audits');
        $grantMap = $this->conversationGrantMap($request);

        $conversations = CompanyChatConversation::query()
            ->with(['userOne', 'userTwo'])
            ->withCount('messages')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subquery) use ($search): void {
                    if (is_numeric($search)) {
                        $subquery->orWhereKey((int) $search);
                    }

                    $subquery->orWhereHas('userOne', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('userTwo', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $selectedConversation = null;
        $selectedConversationHasAccess = false;
        $selectedConversationMessages = collect();

        if ($conversationId !== null) {
            $selectedConversation = CompanyChatConversation::query()
                ->with(['userOne', 'userTwo'])
                ->findOrFail($conversationId);

            $selectedConversationHasAccess = $this->hasConversationAccess($request, $selectedConversation->id);

            if ($selectedConversationHasAccess) {
                $selectedConversation->load([
                    'messages' => function ($query): void {
                        $query->withTrashed()
                            ->with('sender')
                            ->orderBy('created_at');
                    },
                ]);

                $selectedConversationMessages = $selectedConversation->messages;
            }
        }

        return view('admin.conversation-access.index', [
            'conversations' => $conversations,
            'search' => $search,
            'selectedConversation' => $selectedConversation,
            'selectedConversationHasAccess' => $selectedConversationHasAccess,
            'selectedConversationMessages' => $selectedConversationMessages,
            'grantMap' => $grantMap,
            'showAccessModal' => $selectedConversation !== null && ! $selectedConversationHasAccess,
            'missingTable' => ! $hasAuditTable,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $conversationId = $this->sanitizeConversationId($request->input('conversation_id'));
        $conversation = $conversationId !== null
            ? CompanyChatConversation::query()->with(['userOne', 'userTwo'])->find($conversationId)
            : null;

        if (! $this->isAdmin($request->user())) {
            $this->recordDeniedAttempt($request, $conversation, $request->input('reason'));
            abort(403);
        }

        $this->ensureSchemaReady();

        $data = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:company_chat_conversations,id'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $conversation = CompanyChatConversation::query()
            ->with(['userOne', 'userTwo'])
            ->findOrFail((int) $data['conversation_id']);

        $reason = trim((string) $data['reason']);

        DB::transaction(function () use ($conversation, $reason, $request): void {
            $audit = CompanyChatConversationAccessAudit::query()->create([
                'company_chat_conversation_id' => $conversation->id,
                'admin_user_id' => $request->user()?->id,
                'admin_email' => $request->user()?->email,
                'action' => 'VIEW_CONVERSATION_AS_ADMIN',
                'conversation_type' => $conversation->conversation_type_label,
                'affected_user_ids' => array_values(array_filter([$conversation->user_one_id, $conversation->user_two_id])),
                'reason' => $reason,
                'accessed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'granted',
            ]);

            $grants = $request->session()->get('admin_conversation_access_grants', []);
            $grants[(string) $conversation->id] = [
                'audit_id' => $audit->id,
                'granted_at' => now()->toIso8601String(),
            ];
            $request->session()->flash('admin_conversation_access_grants', $grants);
        });

        return redirect()->route('admin.conversation-access.index', [
            'conversation' => $conversation->id,
            'search' => $request->string('search')->toString(),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        if ($this->isAdmin($request->user())) {
            return;
        }

        $conversation = $this->conversationFromRequest($request);

        if ($request->user()) {
            $this->recordDeniedAttempt($request, $conversation, $request->input('reason'));
        }

        abort(403);
    }

    private function ensureSchemaReady(): void
    {
        abort_unless(Schema::hasTable('company_chat_conversation_access_audits'), 503);
    }

    private function isAdmin(?User $user): bool
    {
        return $user !== null && app_real_role($user) === User::ROLE_ADMIN;
    }

    private function hasConversationAccess(Request $request, int $conversationId): bool
    {
        return (bool) data_get($this->conversationGrantMap($request), (string) $conversationId);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function conversationGrantMap(Request $request): array
    {
        $grants = $request->session()->get('admin_conversation_access_grants', []);

        return is_array($grants) ? $grants : [];
    }

    private function conversationFromRequest(Request $request): ?CompanyChatConversation
    {
        $conversationId = $this->sanitizeConversationId($request->input('conversation_id') ?? $request->query('conversation'));

        if ($conversationId === null) {
            return null;
        }

        return CompanyChatConversation::query()
            ->with(['userOne', 'userTwo'])
            ->find($conversationId);
    }

    private function sanitizeConversationId(mixed $conversationId): ?int
    {
        if (! is_numeric($conversationId)) {
            return null;
        }

        $conversationId = (int) $conversationId;

        return $conversationId > 0 ? $conversationId : null;
    }

    private function recordDeniedAttempt(Request $request, ?CompanyChatConversation $conversation, mixed $reason): void
    {
        if (! Schema::hasTable('company_chat_conversation_access_audits') || ! $request->user()) {
            return;
        }

        CompanyChatConversationAccessAudit::query()->create([
            'company_chat_conversation_id' => $conversation?->id,
            'admin_user_id' => $request->user()?->id,
            'admin_email' => $request->user()?->email ?? '',
            'action' => 'VIEW_CONVERSATION_AS_ADMIN_DENIED',
            'conversation_type' => $conversation?->conversation_type_label,
            'affected_user_ids' => $conversation ? array_values(array_filter([$conversation->user_one_id, $conversation->user_two_id])) : [],
            'reason' => is_string($reason) ? trim($reason) : null,
            'accessed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'result' => 'denied',
        ]);
    }
}
