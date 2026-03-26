<?php

namespace App\Http\Controllers;

use App\Models\Dealership;
use App\Models\PurchaseLeaderboardEntry;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    protected const INVITATION_DELIVERY_FAILED = 'invitation_delivery_failed';

    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['name', 'email', 'role', 'dealership', 'is_active', 'salesforce_user_id'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $status = in_array($status, ['active', 'pending'], true) ? $status : null;

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
                        ->orWhere('dealership', 'like', "%{$search}%")
                        ->orWhere('salesforce_user_id', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'pending', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'search', 'status', 'sort', 'direction'));
    }

    public function create()
    {
        $authUser = request()->user();

        $availableRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'comercial']
            : ['comercial'];
        $availableDealerships = Dealership::query()->orderBy('name')->get();

        return view('users.create', compact('availableRoles', 'availableDealerships'));
    }

    public function show(User $user)
    {
        if ($response = $this->ensureCanManageListedUser(request()->user(), $user, 'ver')) {
            return $response;
        }

        return view('users.show', [
            'user' => $user,
            'rankingPositions' => $this->buildRankingPositions($user),
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();

        $allowedRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'comercial']
            : ['comercial'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'salesforce_user_id' => [
                Rule::requiredIf(fn () => $request->input('role') === 'comercial'),
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'salesforce_user_id'),
            ],
            'dealership_id' => [
                Rule::requiredIf(fn () => $request->input('role') === 'comercial'),
                'nullable',
                'integer',
                Rule::exists('dealerships', 'id'),
            ],
        ]);

        try {
            [$user, $status] = DB::transaction(function () use ($validated) {
                $isCommercial = $validated['role'] === 'comercial';
                $dealership = $isCommercial
                    ? Dealership::query()->find($validated['dealership_id'])
                    : null;

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'role' => $validated['role'],
                    'salesforce_user_id' => $isCommercial ? $validated['salesforce_user_id'] : null,
                    'dealership' => $dealership?->name,
                    'dealership_id' => $dealership?->id,
                    'password' => Hash::make(Str::password(32)),
                    'is_active' => false,
                    'must_change_password' => true,
                    'activated_at' => null,
                ]);

                return [$user, $this->sendInvitationLink($user)];
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', $this->invitationErrorMessage(self::INVITATION_DELIVERY_FAILED));
        }

        $this->storeActivityLog(
            actor: $authUser,
            targetUser: $user,
            action: UserActivityLog::ACTION_CREATED,
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()
                ->route('users.index')
                ->with('error', $this->invitationErrorMessage($status));
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente. Le hemos enviado un correo para que establezca su contrasena y active la cuenta.');
    }

    public function destroy(User $user)
    {
        $authUser = request()->user();

        if ($response = $this->ensureCanManageListedUser($authUser, $user, 'eliminar', preventSelf: true)) {
            return $response;
        }

        $this->storeActivityLog(
            actor: $authUser,
            targetUser: $user,
            action: UserActivityLog::ACTION_DELETED,
        );

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    public function edit(User $user)
    {
        $authUser = request()->user();

        if ($response = $this->ensureCanManageListedUser($authUser, $user, 'editar', preventSelf: $authUser->role === 'gestor')) {
            return $response;
        }

        $availableRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'comercial']
            : ['comercial'];
        $availableDealerships = Dealership::query()->orderBy('name')->get();

        return view('users.edit', compact('user', 'availableRoles', 'availableDealerships'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($response = $this->ensureCanManageListedUser($authUser, $user, 'editar', preventSelf: $authUser->role === 'gestor')) {
            return $response;
        }

        $allowedRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'comercial']
            : ['comercial'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'salesforce_user_id' => [
                Rule::requiredIf(fn () => $request->input('role') === 'comercial'),
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'salesforce_user_id')->ignore($user->id),
            ],
            'dealership_id' => [
                Rule::requiredIf(fn () => $request->input('role') === 'comercial'),
                'nullable',
                'integer',
                Rule::exists('dealerships', 'id'),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $dealership = $validated['role'] === 'comercial'
            ? Dealership::query()->find($validated['dealership_id'])
            : null;

        $changes = $this->buildChangeSet($user, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $authUser->role === 'admin' ? $validated['role'] : 'comercial',
            'salesforce_user_id' => $validated['role'] === 'comercial' ? $validated['salesforce_user_id'] : null,
            'dealership' => $dealership?->name,
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $authUser->role === 'admin' ? $validated['role'] : 'comercial';
        $user->salesforce_user_id = $user->role === 'comercial'
            ? $validated['salesforce_user_id']
            : null;
        $user->dealership = $dealership?->name;
        $user->dealership_id = $dealership?->id;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $changes['Contrasena'] = [
                'from' => 'Oculta',
                'to' => 'Actualizada',
            ];
        }

        $user->save();

        if ($changes !== []) {
            $this->storeActivityLog(
                actor: $authUser,
                targetUser: $user,
                action: UserActivityLog::ACTION_UPDATED,
                changes: $changes,
            );
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function resendInvitation(Request $request, User $user)
    {
        if ($response = $this->ensureCanManageListedUser($request->user(), $user, 'reenviar la invitacion', preventSelf: true)) {
            return $response;
        }

        if ($user->is_active && ! $user->must_change_password) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Solo puedes reenviar la invitacion a usuarios pendientes de activacion.');
        }

        try {
            $status = $this->sendInvitationLink($user);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('users.index')
                ->with('error', $this->invitationErrorMessage(self::INVITATION_DELIVERY_FAILED));
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()
                ->route('users.index')
                ->with('error', $this->invitationErrorMessage($status));
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Correo de activacion reenviado correctamente.');
    }

    protected function ensureCanManageListedUser(User $authUser, User $targetUser, string $action, bool $preventSelf = false): ?RedirectResponse
    {
        if ($preventSelf && $authUser->id === $targetUser->id) {
            return redirect()
                ->route('users.index')
                ->with('error', "No puedes {$action} tu propio usuario.");
        }

        if ($authUser->role === 'gestor' && $targetUser->role !== 'comercial') {
            return redirect()
                ->route('users.index')
                ->with('error', "No tienes permisos para {$action} este usuario.");
        }

        return null;
    }

    protected function sendInvitationLink(User $user): string
    {
        return Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);
    }

    protected function invitationErrorMessage(string $status): string
    {
        if ($status === self::INVITATION_DELIVERY_FAILED) {
            return 'No se ha podido enviar el correo de activacion. Revisa que el email sea correcto y que el dominio exista.';
        }

        return match ($status) {
            Password::RESET_THROTTLED => 'Espera un momento antes de volver a enviar el correo de activacion.',
            Password::INVALID_USER => 'No se ha encontrado un usuario valido para enviar el correo de activacion.',
            default => 'No se ha podido enviar el correo de activacion. Intentalo de nuevo en unos minutos.',
        };
    }

    protected function storeActivityLog(User $actor, User $targetUser, string $action, array $changes = []): void
    {
        UserActivityLog::query()->create([
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_user_id' => $targetUser->id,
            'target_name' => $targetUser->name,
            'target_email' => $targetUser->email,
            'target_role' => $targetUser->role,
            'target_dealership' => $targetUser->dealership,
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }

    protected function buildChangeSet(User $user, array $newValues): array
    {
        $labels = [
            'name' => 'Nombre',
            'email' => 'Email',
            'role' => 'Rol',
            'salesforce_user_id' => 'ID Salesforce',
            'dealership' => 'Delegacion',
        ];

        return collect($newValues)
            ->filter(fn ($value, $field) => $user->{$field} !== $value)
            ->mapWithKeys(fn ($value, $field) => [
                $labels[$field] ?? $field => [
                    'from' => $user->{$field},
                    'to' => $value,
                ],
            ])
            ->all();
    }

    protected function buildRankingPositions(User $user): array
    {
        $positions = [
            'sales' => null,
            'purchases' => null,
        ];

        if ($user->role !== 'comercial') {
            return $positions;
        }

        if (Schema::hasTable('sales_leaderboard_entries')) {
            $salesEntry = SalesLeaderboardEntry::query()
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);

                    if ($user->salesforce_user_id) {
                        $query->orWhere('salesforce_user_id', $user->salesforce_user_id);
                    }
                })
                ->orderBy('ranking_position')
                ->first();

            $positions['sales'] = $salesEntry?->ranking_position;
        }

        if (Schema::hasTable('purchase_leaderboard_entries')) {
            $purchaseEntry = PurchaseLeaderboardEntry::query()
                ->when($this->excludedLeaderboardUserIds() !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', $this->excludedLeaderboardUserIds());
                })
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);

                    if ($user->salesforce_user_id) {
                        $query->orWhere('salesforce_user_id', $user->salesforce_user_id);
                    }
                })
                ->orderBy('ranking_position')
                ->first();

            $positions['purchases'] = $purchaseEntry?->ranking_position;
        }

        return $positions;
    }

    protected function excludedLeaderboardUserIds(): array
    {
        return config('services.salesforce.excluded_leaderboard_user_ids', []);
    }
}
