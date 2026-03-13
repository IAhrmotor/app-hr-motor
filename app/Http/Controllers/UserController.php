<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['name', 'email', 'role', 'is_active', 'salesforce_user_id'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $status = in_array($status, ['active', 'pending'], true) ? $status : null;

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
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

        return view('users.create', compact('availableRoles'));
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
        ]);

        DB::transaction(function () use ($validated): void {
            $isCommercial = $validated['role'] === 'comercial';

            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'salesforce_user_id' => $isCommercial ? $validated['salesforce_user_id'] : null,
                'password' => Hash::make(Str::password(32)),
                'is_active' => false,
                'must_change_password' => true,
                'activated_at' => null,
            ]);

            Password::broker()->sendResetLink([
                'email' => $validated['email'],
            ]);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente. Le hemos enviado un correo para que establezca su contraseña y active la cuenta.');
    }

    public function destroy(User $user)
    {
        $authUser = request()->user();

        if ($authUser->id === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if ($authUser->role === 'gestor' && $user->role !== 'comercial') {
            return redirect()
                ->route('users.index')
                ->with('error', 'No tienes permisos para eliminar este usuario.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    public function edit(User $user)
    {
        $authUser = request()->user();

        if ($authUser->role === 'gestor') {
            if ($authUser->id === $user->id || $user->role !== 'comercial') {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'No tienes permisos para editar este usuario.');
            }
        }

        $availableRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'comercial']
            : ['comercial'];

        return view('users.edit', compact('user', 'availableRoles'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->role === 'gestor') {
            if ($authUser->id === $user->id || $user->role !== 'comercial') {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'No tienes permisos para editar este usuario.');
            }
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
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $authUser->role === 'admin' ? $validated['role'] : 'comercial';
        $user->salesforce_user_id = $user->role === 'comercial'
            ? $validated['salesforce_user_id']
            : null;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }
}
