<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleViewerController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->role === User::ROLE_ADMIN, 403);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', array_keys(User::roleLabels()))],
        ]);

        if ($validated['role'] === User::ROLE_ADMIN) {
            session()->forget('role_viewer.active_role');
        } else {
            session(['role_viewer.active_role' => $validated['role']]);
        }

        return back()->with('success', 'Vista cambiada a ' . (User::roleLabels()[$validated['role']] ?? 'Admin') . '.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->role === User::ROLE_ADMIN, 403);

        $previousRole = session('role_viewer.active_role');
        session()->forget('role_viewer.active_role');

        return back()->with('success', $previousRole
            ? 'Has vuelto a la vista de admin.'
            : 'La vista ya estaba en admin.');
    }
}
