<?php

namespace App\Http\Controllers;

use App\Models\CompanyChatGroup;
use App\Models\User;
use App\Services\CompanyChatGroupManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        $group = app(CompanyChatGroupManagementService::class)->create(
            $validated['name'],
            $participantIds->all(),
            $request->user(),
        );

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
        app(CompanyChatGroupManagementService::class)->update(
            $chatGroup,
            $validated['name'],
            $participantIds->all(),
            $request->user(),
        );

        return redirect()
            ->route('admin.chat-groups.index')
            ->with('success', 'Grupo actualizado correctamente.');
    }

    public function destroy(Request $request, CompanyChatGroup $chatGroup): RedirectResponse
    {
        app(CompanyChatGroupManagementService::class)->delete($chatGroup, $request->user());

        return redirect()
            ->route('admin.chat-groups.index')
            ->with('success', 'Grupo eliminado correctamente.');
    }

    private function availableParticipants()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
