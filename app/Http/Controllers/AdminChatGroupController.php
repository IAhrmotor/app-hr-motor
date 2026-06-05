<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatGroup;
use App\Models\CompanyChatGroupActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AdminChatGroupController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['name', 'participants_count', 'created_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $groups = CompanyChatGroup::query()
            ->withCount('participants')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'groups_total' => CompanyChatGroup::query()->count(),
            'participants_total' => User::query()->where('is_active', true)->count(),
            'participants_in_groups' => CompanyChatGroup::query()
                ->withCount('participants')
                ->get()
                ->sum('participants_count'),
        ];

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.chat-groups.partials.index-results', [
                    'groups' => $groups,
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction,
                ])->render(),
            ]);
        }

        return view('admin.chat-groups.index', compact('groups', 'search', 'sort', 'direction', 'stats'));
    }

    public function create(): View
    {
        return view('admin.chat-groups.create', [
            'availableParticipants' => $this->availableParticipants(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:company_chat_groups,name'],
            'participants' => ['required', 'array', 'min:2'],
            'participants.*' => [Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true))],
        ]);

        $participantIds = collect($validated['participants'])->map(fn ($value) => (int) $value)->unique()->values();

        if ($participantIds->count() < 2) {
            return back()
                ->withErrors(['participants' => 'Debes seleccionar al menos dos participantes distintos.'])
                ->withInput();
        }

        $group = DB::transaction(function () use ($request, $validated, $participantIds) {
            $group = CompanyChatGroup::query()->create([
                'name' => $validated['name'],
            ]);

            $group->participants()->sync($participantIds->all());
            $group->load('participants');

            $this->storeActivityLog(
                actor: $request->user(),
                group: $group,
                action: CompanyChatGroupActivityLog::ACTION_CREATED,
                changes: [
                    'name' => ['from' => null, 'to' => $group->name],
                    'participants' => ['from' => null, 'to' => $this->participantsSummary($group)],
                ],
            );

            return $group;
        });

        return redirect()
            ->route('admin.chat-groups.index')
            ->with('success', 'Grupo creado correctamente.');
    }

    public function edit(CompanyChatGroup $chatGroup): View
    {
        $chatGroup->load('participants');

        return view('admin.chat-groups.edit', [
            'group' => $chatGroup,
            'availableParticipants' => $this->availableParticipants(),
        ]);
    }

    public function update(Request $request, CompanyChatGroup $chatGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('company_chat_groups', 'name')->ignore($chatGroup->id)],
            'participants' => ['required', 'array', 'min:2'],
            'participants.*' => [Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true))],
        ]);

        $chatGroup->load('participants');
        $participantIds = collect($validated['participants'])->map(fn ($value) => (int) $value)->unique()->values();

        if ($participantIds->count() < 2) {
            return back()
                ->withErrors(['participants' => 'Debes seleccionar al menos dos participantes distintos.'])
                ->withInput();
        }
        $changes = [];

        if ($chatGroup->name !== $validated['name']) {
            $changes['name'] = ['from' => $chatGroup->name, 'to' => $validated['name']];
        }

        $previousParticipantsSummary = $this->participantsSummary($chatGroup);
        $newParticipantsSummary = $this->participantsSummaryFromIds($participantIds->all());

        if ($previousParticipantsSummary !== $newParticipantsSummary) {
            $changes['participants'] = ['from' => $previousParticipantsSummary, 'to' => $newParticipantsSummary];
        }

        DB::transaction(function () use ($request, $chatGroup, $validated, $participantIds, $changes): void {
            $chatGroup->update([
                'name' => $validated['name'],
            ]);

            $chatGroup->participants()->sync($participantIds->all());
            $chatGroup->load('participants');

            if ($changes !== []) {
                $this->storeActivityLog(
                    actor: $request->user(),
                    group: $chatGroup,
                    action: CompanyChatGroupActivityLog::ACTION_UPDATED,
                    changes: $changes,
                );
            }
        });

        return redirect()
            ->route('admin.chat-groups.index')
            ->with('success', 'Grupo actualizado correctamente.');
    }

    public function destroy(Request $request, CompanyChatGroup $chatGroup): RedirectResponse
    {
        DB::transaction(function () use ($request, $chatGroup): void {
            $this->storeActivityLog(
                actor: $request->user(),
                group: $chatGroup,
                action: CompanyChatGroupActivityLog::ACTION_DELETED,
                changes: [
                    'name' => ['from' => $chatGroup->name, 'to' => null],
                    'participants' => ['from' => $this->participantsSummary($chatGroup), 'to' => null],
                ],
            );

            $chatGroup->delete();
        });

        return redirect()
            ->route('admin.chat-groups.index')
            ->with('success', 'Grupo eliminado correctamente.');
    }

    private function storeActivityLog(User $actor, CompanyChatGroup $group, string $action, array $changes = []): void
    {
        CompanyChatGroupActivityLog::query()->create([
            'action' => $action,
            'result' => 'success',
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'company_chat_group_id' => $group->id,
            'target_name' => $group->name,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 2000) ?: null,
        ]);
    }

    private function availableParticipants()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function participantsSummary(CompanyChatGroup $group): string
    {
        $group->loadMissing('participants');

        return $this->participantsSummaryFromCollection($group->participants);
    }

    private function participantsSummaryFromIds(array $participantIds): string
    {
        if ($participantIds === []) {
            return 'Sin participantes';
        }

        $participants = User::query()
            ->whereKey($participantIds)
            ->orderBy('name')
            ->get();

        return $this->participantsSummaryFromCollection($participants);
    }

    private function participantsSummaryFromCollection($participants): string
    {
        $names = collect($participants)->pluck('name')->filter()->values();

        if ($names->isEmpty()) {
            return 'Sin participantes';
        }

        return $names->join(', ');
    }
}
