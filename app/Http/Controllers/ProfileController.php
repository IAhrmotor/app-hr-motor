<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('users.show', compact('user'));
    }

    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'linkedin_url' => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?linkedin\.com\/.+/i'],
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $this->storeAvatar($request, $user);
        }

        $user->linkedin_url = $validated['linkedin_url'] ?? null;
        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    protected function storeAvatar(Request $request, User $user): string
    {
        $directory = public_path('images/users/avatars');
        File::ensureDirectoryExists($directory);

        $avatar = $request->file('avatar');
        $extension = $avatar->getClientOriginalExtension() ?: $avatar->extension() ?: 'png';
        $filename = sprintf('%s-%s.%s', $user->id, Str::uuid(), strtolower($extension));
        $avatar->move($directory, $filename);

        $this->deletePreviousAvatar($user);

        return 'images/users/avatars/' . $filename;
    }

    protected function deletePreviousAvatar(User $user): void
    {
        if ($user->avatar_path === User::DEFAULT_AVATAR_PATH) {
            return;
        }

        $path = public_path($user->avatar_path);

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
