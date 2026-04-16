<?php

namespace App\Http\Controllers;

use App\Models\ForumTag;
use App\Models\ContentActivityLog;
use App\Services\ContentActivityLogger;
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

        $tag = ForumTag::query()->create($validated);
        app(ContentActivityLogger::class)->record(
            actor: $request->user(),
            contentType: ContentActivityLog::CONTENT_TYPE_FORUM_TAG,
            action: ContentActivityLog::ACTION_CREATED,
            targetName: $tag->name,
            targetReference: $tag->color,
            changes: [
                'name' => ['from' => null, 'to' => $tag->name],
                'color' => ['from' => null, 'to' => $tag->color],
            ],
        );

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

        $changes = [];

        if ($forumTag->name !== $validated['name']) {
            $changes['name'] = ['from' => $forumTag->name, 'to' => $validated['name']];
        }

        if ($forumTag->color !== $validated['color']) {
            $changes['color'] = ['from' => $forumTag->color, 'to' => $validated['color']];
        }

        $forumTag->update($validated);
        app(ContentActivityLogger::class)->record(
            actor: $request->user(),
            contentType: ContentActivityLog::CONTENT_TYPE_FORUM_TAG,
            action: ContentActivityLog::ACTION_UPDATED,
            targetName: $forumTag->name,
            targetReference: $forumTag->color,
            changes: $changes,
        );

        return redirect()
            ->route('admin.forum-tags.index')
            ->with('success', 'Tag actualizado correctamente.');
    }

    public function destroy(Request $request, ForumTag $forumTag): RedirectResponse
    {
        app(ContentActivityLogger::class)->record(
            actor: $request->user(),
            contentType: ContentActivityLog::CONTENT_TYPE_FORUM_TAG,
            action: ContentActivityLog::ACTION_DELETED,
            targetName: $forumTag->name,
            targetReference: $forumTag->color,
            changes: [
                'name' => ['from' => $forumTag->name, 'to' => null],
                'color' => ['from' => $forumTag->color, 'to' => null],
            ],
        );

        $forumTag->delete();

        return redirect()
            ->route('admin.forum-tags.index')
            ->with('success', 'Tag eliminado correctamente.');
    }
}
