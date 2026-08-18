<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatRetentionHoldAudit;
use App\Models\CompanyChatRetentionUserHold;
use App\Models\CompanyChatRetentionUserHoldAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminChatRetentionHoldController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $missingTable = ! Schema::hasColumns('company_chat_conversations', [
            'retention_hold',
            'retention_hold_reason',
            'retention_hold_created_at',
            'retention_hold_created_by',
            'retention_hold_expires_at',
        ]) || ! Schema::hasTable('company_chat_retention_hold_audits')
            || ! Schema::hasTable('company_chat_retention_user_holds')
            || ! Schema::hasTable('company_chat_retention_user_hold_audits');

        $activeHolds = $missingTable
            ? $this->emptyPaginator()
            : $this->activeHoldsQuery()->paginate(20)->withQueryString();

        $availableConversations = $missingTable
            ? collect()
            : $this->availableConversations();

        $activeUserHolds = $missingTable
            ? $this->emptyPaginator()
            : $this->activeUserHoldsQuery()->paginate(20)->withQueryString();

        $availableUsers = $missingTable
            ? collect()
            : $this->availableUsers();

        return view('admin.chat-retention-holds.index', [
            'activeHolds' => $activeHolds,
            'availableConversations' => $availableConversations,
            'activeUserHolds' => $activeUserHolds,
            'availableUsers' => $availableUsers,
            'missingTable' => $missingTable,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureSchemaReady();

        $data = $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:company_chat_conversations,id'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $conversation = CompanyChatConversation::query()
            ->with(['userOne', 'userTwo'])
            ->findOrFail((int) $data['conversation_id']);

        if ($conversation->hasActiveRetentionHold()) {
            return back()
                ->withErrors(['conversation_id' => 'La conversación ya tiene un bloqueo de retención activo.'])
                ->withInput();
        }

        $reason = trim($data['reason']);
        $expiresAt = ! empty($data['expires_at'])
            ? Carbon::parse($data['expires_at'])->endOfDay()
            : null;

        $conversation->forceFill([
            'retention_hold' => true,
            'retention_hold_reason' => $reason,
            'retention_hold_created_at' => now(),
            'retention_hold_created_by' => $request->user()->id,
            'retention_hold_expires_at' => $expiresAt,
        ])->save();

        $this->recordAudit(
            conversation: $conversation,
            action: 'activated',
            reason: $reason,
            previousReason: null,
            expiresAt: $expiresAt,
            previousExpiresAt: null,
            request: $request,
        );

        return redirect()
            ->route('admin.chat-retention-holds.index')
            ->with('status', 'La conversación se ha marcado para conservación excepcional.');
    }

    public function update(Request $request, CompanyChatConversation $conversation): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureSchemaReady();

        $conversation->loadMissing(['userOne', 'userTwo', 'retentionHoldCreatedByUser']);

        if (! $conversation->hasActiveRetentionHold()) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $newReason = trim($data['reason']);
        $newExpiresAt = ! empty($data['expires_at'])
            ? Carbon::parse($data['expires_at'])->endOfDay()
            : null;
        $oldReason = $conversation->retention_hold_reason;
        $oldExpiresAt = $conversation->retention_hold_expires_at;
        $changesMade = false;

        $conversation->forceFill([
            'retention_hold_reason' => $newReason,
            'retention_hold_expires_at' => $newExpiresAt,
        ])->save();

        if ($oldReason !== $newReason) {
            $changesMade = true;

            $this->recordAudit(
                conversation: $conversation,
                action: 'reason_updated',
                reason: $newReason,
                previousReason: $oldReason,
                expiresAt: $newExpiresAt,
                previousExpiresAt: $oldExpiresAt,
                request: $request,
            );
        }

        if (! $this->sameTimestamp($oldExpiresAt, $newExpiresAt)) {
            $changesMade = true;

            $this->recordAudit(
                conversation: $conversation,
                action: 'expires_at_updated',
                reason: $newReason,
                previousReason: $oldReason,
                expiresAt: $newExpiresAt,
                previousExpiresAt: $oldExpiresAt,
                request: $request,
            );
        }

        if (! $changesMade) {
            return back()->with('status', 'No se han detectado cambios en la conservación excepcional.');
        }

        return redirect()
            ->route('admin.chat-retention-holds.index')
            ->with('status', 'La conservación excepcional se ha actualizado correctamente.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureSchemaReady();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $targetUser = User::query()->findOrFail((int) $data['user_id']);

        if (CompanyChatRetentionUserHold::query()->active()->where('user_id', $targetUser->id)->exists()) {
            return back()
                ->withErrors(['user_id' => 'Ese usuario ya tiene un bloqueo de conservación excepcional activo.'])
                ->withInput();
        }

        $reason = trim($data['reason']);
        $expiresAt = ! empty($data['expires_at'])
            ? Carbon::parse($data['expires_at'])->endOfDay()
            : null;

        $hold = CompanyChatRetentionUserHold::query()->create([
            'user_id' => $targetUser->id,
            'retention_hold' => true,
            'retention_hold_reason' => $reason,
            'retention_hold_created_at' => now(),
            'retention_hold_created_by' => $request->user()->id,
            'retention_hold_expires_at' => $expiresAt,
        ]);

        $this->recordUserAudit(
            userId: $targetUser->id,
            action: 'activated',
            reason: $reason,
            previousReason: null,
            expiresAt: $expiresAt,
            previousExpiresAt: null,
            request: $request,
        );

        return redirect()
            ->route('admin.chat-retention-holds.index')
            ->with('status', 'Se ha activado la conservación excepcional para todas las conversaciones de ese usuario.');
    }

    public function updateUser(Request $request, CompanyChatRetentionUserHold $userHold): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureSchemaReady();

        if (! $userHold->hasActiveRetentionHold()) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $newReason = trim($data['reason']);
        $newExpiresAt = ! empty($data['expires_at'])
            ? Carbon::parse($data['expires_at'])->endOfDay()
            : null;
        $oldReason = $userHold->retention_hold_reason;
        $oldExpiresAt = $userHold->retention_hold_expires_at;
        $changesMade = false;

        $userHold->forceFill([
            'retention_hold_reason' => $newReason,
            'retention_hold_expires_at' => $newExpiresAt,
        ])->save();

        if ($oldReason !== $newReason) {
            $changesMade = true;
            $this->recordUserAudit(
                userId: $userHold->user_id,
                action: 'reason_updated',
                reason: $newReason,
                previousReason: $oldReason,
                expiresAt: $newExpiresAt,
                previousExpiresAt: $oldExpiresAt,
                request: $request,
            );
        }

        if (! $this->sameTimestamp($oldExpiresAt, $newExpiresAt)) {
            $changesMade = true;
            $this->recordUserAudit(
                userId: $userHold->user_id,
                action: 'expires_at_updated',
                reason: $newReason,
                previousReason: $oldReason,
                expiresAt: $newExpiresAt,
                previousExpiresAt: $oldExpiresAt,
                request: $request,
            );
        }

        if (! $changesMade) {
            return back()->with('status', 'No se han detectado cambios en la conservación excepcional del usuario.');
        }

        return redirect()
            ->route('admin.chat-retention-holds.index')
            ->with('status', 'La conservación excepcional del usuario se ha actualizado correctamente.');
    }

    public function destroyUser(Request $request, CompanyChatRetentionUserHold $userHold): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureSchemaReady();

        if (! $userHold->hasActiveRetentionHold()) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $reason = trim($data['reason']);
        $previousReason = $userHold->retention_hold_reason;
        $previousExpiresAt = $userHold->retention_hold_expires_at;

        $userHold->forceFill([
            'retention_hold' => false,
            'retention_hold_reason' => null,
            'retention_hold_created_at' => null,
            'retention_hold_created_by' => null,
            'retention_hold_expires_at' => null,
            'retention_hold_deactivated_at' => now(),
            'retention_hold_deactivated_by' => $request->user()->id,
            'retention_hold_deactivation_reason' => $reason,
        ])->save();

        $this->recordUserAudit(
            userId: $userHold->user_id,
            action: 'deactivated',
            reason: $reason,
            previousReason: $previousReason,
            expiresAt: null,
            previousExpiresAt: $previousExpiresAt,
            request: $request,
        );

        return redirect()
            ->route('admin.chat-retention-holds.index')
            ->with('status', 'La conservación excepcional del usuario ha sido desactivada.');
    }

    public function destroy(Request $request, CompanyChatConversation $conversation): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureSchemaReady();

        $conversation->loadMissing(['userOne', 'userTwo', 'retentionHoldCreatedByUser']);

        if (! $conversation->hasActiveRetentionHold()) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $reason = trim($data['reason']);
        $previousReason = $conversation->retention_hold_reason;
        $previousExpiresAt = $conversation->retention_hold_expires_at;

        $conversation->forceFill([
            'retention_hold' => false,
            'retention_hold_reason' => null,
            'retention_hold_created_at' => null,
            'retention_hold_created_by' => null,
            'retention_hold_expires_at' => null,
        ])->save();

        $this->recordAudit(
            conversation: $conversation,
            action: 'deactivated',
            reason: $reason,
            previousReason: $previousReason,
            expiresAt: null,
            previousExpiresAt: $previousExpiresAt,
            request: $request,
        );

        return redirect()
            ->route('admin.chat-retention-holds.index')
            ->with('status', 'La conversación ha dejado de tener conservación excepcional.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            app_real_role(auth()->user()) === User::ROLE_ADMIN
                || app_user_can_manage_admin_tool(auth()->user(), 'chat-retention-holds.manage'),
            403
        );
    }

    private function ensureSchemaReady(): void
    {
        abort_unless(
            Schema::hasTable('company_chat_conversations')
                && Schema::hasTable('company_chat_retention_hold_audits')
                && Schema::hasTable('company_chat_retention_user_holds')
                && Schema::hasTable('company_chat_retention_user_hold_audits'),
            503,
        );
    }

    private function activeHoldsQuery(): Builder
    {
        return CompanyChatConversation::query()
            ->with(['userOne', 'userTwo', 'retentionHoldCreatedByUser'])
            ->withActiveRetentionHold()
            ->orderByDesc('retention_hold_created_at')
            ->orderByDesc('updated_at');
    }

    private function availableConversations(): Collection
    {
        return CompanyChatConversation::query()
            ->with(['userOne', 'userTwo'])
            ->availableForRetentionHold()
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    private function activeUserHoldsQuery(): Builder
    {
        return CompanyChatRetentionUserHold::query()
            ->with(['user', 'createdBy'])
            ->active()
            ->orderByDesc('retention_hold_created_at')
            ->orderByDesc('updated_at');
    }

    private function availableUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function emptyPaginator()
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: 20,
            currentPage: \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage(),
            options: ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    private function recordAudit(
        CompanyChatConversation $conversation,
        string $action,
        ?string $reason,
        ?string $previousReason,
        ?\Illuminate\Support\Carbon $expiresAt,
        ?\Illuminate\Support\Carbon $previousExpiresAt,
        Request $request,
    ): void {
        CompanyChatRetentionHoldAudit::query()->create([
            'company_chat_conversation_id' => $conversation->id,
            'admin_user_id' => $request->user()?->id,
            'action' => $action,
            'reason' => $reason,
            'previous_reason' => $previousReason,
            'expires_at' => $expiresAt,
            'previous_expires_at' => $previousExpiresAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'source' => 'web-admin',
        ]);
    }

    private function recordUserAudit(
        int $userId,
        string $action,
        ?string $reason,
        ?string $previousReason,
        ?Carbon $expiresAt,
        ?Carbon $previousExpiresAt,
        Request $request,
    ): void {
        CompanyChatRetentionUserHoldAudit::query()->create([
            'user_id' => $userId,
            'admin_user_id' => $request->user()?->id,
            'action' => $action,
            'reason' => $reason,
            'previous_reason' => $previousReason,
            'expires_at' => $expiresAt,
            'previous_expires_at' => $previousExpiresAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'source' => 'web-admin',
        ]);
    }

    private function sameTimestamp(?\Illuminate\Support\Carbon $first, ?\Illuminate\Support\Carbon $second): bool
    {
        if ($first === null && $second === null) {
            return true;
        }

        if ($first === null || $second === null) {
            return false;
        }

        return $first->equalTo($second);
    }
}
