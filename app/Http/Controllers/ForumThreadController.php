<?php

namespace App\Http\Controllers;

use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForumThreadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');
        $status = in_array($status, [ForumThread::STATUS_OPEN, ForumThread::STATUS_RESOLVED], true) ? $status : null;

        $threads = ForumThread::query()
            ->with(['creator', 'latestReply.author', 'resolver'])
            ->withCount('replies')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas('creator', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('dealership', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderedForListing()
            ->paginate(12)
            ->withQueryString();

        $threadStats = [
            'open' => ForumThread::query()->where('status', ForumThread::STATUS_OPEN)->count(),
            'resolved' => ForumThread::query()->where('status', ForumThread::STATUS_RESOLVED)->count(),
            'totalReplies' => ForumReply::query()->count(),
        ];

        return view('forum.index', [
            'threads' => $threads,
            'threadStats' => $threadStats,
            'search' => $search,
            'status' => $status,
            'canCreateThreads' => $this->canCreateThreads($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canCreateThreads($request->user()), 403);

        return view('forum.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canCreateThreads($request->user()), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'content' => ['required', 'string', 'min:12', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $thread = ForumThread::query()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => ForumThread::STATUS_OPEN,
        ]);

        $this->storeThreadAttachments($request, $thread);

        return redirect()
            ->route('forum.show', $thread)
            ->with('success', 'Hilo creado correctamente. Ya puedes empezar la conversación.');
    }

    public function show(ForumThread $thread, Request $request): View
    {
        $thread->load([
            'creator',
            'resolver',
            'attachments',
            'replies.author',
            'replies.attachments',
        ]);

        return view('forum.show', [
            'thread' => $thread,
            'canModerateThread' => $this->canModerateThread($request->user()),
            'canChangeThreadStatus' => $this->canChangeThreadStatus($request->user(), $thread),
        ]);
    }

    public function reply(Request $request, ForumThread $thread): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:3000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $reply = $thread->replies()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        $this->storeReplyAttachments($request, $reply);

        return redirect()
            ->route('forum.show', $thread)
            ->with('success', 'Respuesta publicada correctamente.');
    }

    public function updateStatus(Request $request, ForumThread $thread): RedirectResponse
    {
        abort_unless($this->canChangeThreadStatus($request->user(), $thread), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . ForumThread::STATUS_OPEN . ',' . ForumThread::STATUS_RESOLVED],
        ]);

        $thread->status = $validated['status'];
        $thread->resolved_at = $validated['status'] === ForumThread::STATUS_RESOLVED ? now() : null;
        $thread->resolved_by_user_id = $validated['status'] === ForumThread::STATUS_RESOLVED ? $request->user()->id : null;
        $thread->save();

        return redirect()
            ->route('forum.show', $thread)
            ->with('success', $validated['status'] === ForumThread::STATUS_RESOLVED
                ? 'El hilo se ha marcado como resuelto.'
                : 'El hilo se ha vuelto a abrir.');
    }

    public function destroy(Request $request, ForumThread $thread): RedirectResponse
    {
        abort_unless($this->canModerateThread($request->user()), 403);

        $thread->delete();

        return redirect()
            ->route('forum.index')
            ->with('success', 'Hilo eliminado correctamente.');
    }

    private function canCreateThreads(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
        ], true);
    }

    private function canModerateThread(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true);
    }

    private function canChangeThreadStatus(User $user, ForumThread $thread): bool
    {
        return $this->canModerateThread($user) || $thread->user_id === $user->id;
    }

    private function storeThreadAttachments(Request $request, ForumThread $thread): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $directory = public_path('images/forum/threads');
        File::ensureDirectoryExists($directory);

        foreach ((array) $request->file('attachments') as $image) {
            $extension = $image->getClientOriginalExtension() ?: $image->extension() ?: 'png';
            $filename = sprintf('%s-%s.%s', $thread->id, Str::uuid(), strtolower($extension));
            $image->move($directory, $filename);

            $thread->attachments()->create([
                'image_path' => 'images/forum/threads/' . $filename,
            ]);
        }
    }

    private function storeReplyAttachments(Request $request, ForumReply $reply): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $directory = public_path('images/forum/replies');
        File::ensureDirectoryExists($directory);

        foreach ((array) $request->file('attachments') as $image) {
            $extension = $image->getClientOriginalExtension() ?: $image->extension() ?: 'png';
            $filename = sprintf('%s-%s.%s', $reply->id, Str::uuid(), strtolower($extension));
            $image->move($directory, $filename);

            $reply->attachments()->create([
                'image_path' => 'images/forum/replies/' . $filename,
            ]);
        }
    }
}
