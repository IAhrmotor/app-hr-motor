<?php

namespace App\Http\Controllers;

use App\Models\ForumTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ForumTagController extends Controller
{
    public function index(): View
    {
        return view('admin.forum-tags.index', [
            'tags' => ForumTag::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.forum-tags.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:forum_tags,name'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        ForumTag::query()->create($validated);

        return redirect()
            ->route('admin.forum-tags.index')
            ->with('success', 'Tag creado correctamente.');
    }

    public function edit(ForumTag $forumTag): View
    {
        return view('admin.forum-tags.edit', [
            'tag' => $forumTag,
        ]);
    }

    public function update(Request $request, ForumTag $forumTag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('forum_tags', 'name')->ignore($forumTag->id)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $forumTag->update($validated);

        return redirect()
            ->route('admin.forum-tags.index')
            ->with('success', 'Tag actualizado correctamente.');
    }

    public function destroy(ForumTag $forumTag): RedirectResponse
    {
        $forumTag->delete();

        return redirect()
            ->route('admin.forum-tags.index')
            ->with('success', 'Tag eliminado correctamente.');
    }
}
