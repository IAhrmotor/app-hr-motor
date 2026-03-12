<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $authUser = request()->user();

        $availableRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'user']
            : ['user'];

        return view('users.create', compact('availableRoles'));
    }

    public function store(Request $request)
    {
        $authUser = $request->user();

        $allowedRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'user']
            : ['user'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function destroy(User $user)
    {
        $authUser = request()->user();

        if ($authUser->id === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if ($authUser->role === 'gestor' && $user->role !== 'user') {
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
            if ($authUser->id === $user->id || $user->role !== 'user') {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'No tienes permisos para editar este usuario.');
            }
        }

        $availableRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'user']
            : ['user'];

        return view('users.edit', compact('user', 'availableRoles'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = $request->user();

        if ($authUser->role === 'gestor') {
            if ($authUser->id === $user->id || $user->role !== 'user') {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'No tienes permisos para editar este usuario.');
            }
        }

        $allowedRoles = $authUser->role === 'admin'
            ? ['admin', 'gestor', 'user']
            : ['user'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $authUser->role === 'admin' ? $validated['role'] : 'user';

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }
}
