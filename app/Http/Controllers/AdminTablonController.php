<?php

namespace App\Http\Controllers;

use App\Models\BulletinPost;
use App\Models\BulletinPostAttachment;
use App\Models\BulletinActivityLog;
use App\Models\User;
use App\Notifications\AdminPriorityNotification;
use App\Services\BulletinActivityLogWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminTablonController extends Controller
{
    public function index(): View
    {
        $posts = BulletinPost::query()
            ->with(['creator:id,name,avatar_path', 'attachments'])
            ->orderByDesc('is_published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.tablon.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.tablon.create', [
            'post' => new BulletinPost([
                'is_published' => false,
            ]),
            'mentionableUsers' => $this->mentionableUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'body' => ['required', 'string', 'max:10000'],
            'is_published' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $isPublished = $request->boolean('is_published');
        $post = null;
        $shouldNotify = false;
        $storedImages = [];

        try {
            DB::transaction(function () use (&$post, &$shouldNotify, &$storedImages, $validated, $isPublished, $request): void {
                $post = BulletinPost::query()->create([
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                    'is_published' => $isPublished,
                    'published_at' => $isPublished ? now() : null,
                    'created_by_user_id' => $request->user()?->id,
                    'updated_by_user_id' => $request->user()?->id,
                ]);

                $this->storeImages($post, $request->file('images', []), $storedImages);
                $shouldNotify = $post->is_published;

                $this->recordActivity(
                    actor: $request->user(),
                    action: BulletinActivityLog::ACTION_CREATED,
                    post: $post,
                    changes: [
                        'title' => ['from' => null, 'to' => $post->title],
                        'body_excerpt' => ['from' => null, 'to' => $this->excerpt($post->body)],
                        'is_published' => ['from' => null, 'to' => $post->is_published ? 'true' : 'false'],
                        'published_at' => ['from' => null, 'to' => $post->published_at?->format('Y-m-d H:i:s')],
                        'images' => ['from' => null, 'to' => $post->attachments()->count() > 0 ? (string) $post->attachments()->count() : null],
                    ],
                );
            });
        } catch (Throwable $throwable) {
            $this->cleanupStoredBulletinImages($storedImages);
            throw $throwable;
        }

        if ($shouldNotify && $post) {
            $this->notifyPublishedPost($post, $request->user());
        }

        return redirect()
            ->route('admin.tablon.index')
            ->with('success', html_entity_decode('El tabl&oacute;n se ha publicado correctamente.'));
    }

    public function edit(BulletinPost $bulletin): View
    {
        return view('admin.tablon.edit', [
            'post' => $bulletin->load(['attachments']),
            'mentionableUsers' => $this->mentionableUsers(),
        ]);
    }

    public function update(Request $request, BulletinPost $bulletin): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'body' => ['required', 'string', 'max:10000'],
            'is_published' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'keep_attachment_ids' => ['nullable', 'array'],
            'keep_attachment_ids.*' => ['integer'],
        ]);

        $isPublished = $request->boolean('is_published');
        $shouldNotify = $isPublished && ! $bulletin->is_published;
        $changes = [];
        $storedImages = [];
        $existingAttachments = $bulletin->attachments()->get();
        $existingAttachmentIds = $existingAttachments->pluck('id')->all();
        $keepAttachmentIds = collect($validated['keep_attachment_ids'] ?? [])
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
        $finalAttachmentCount = count(array_intersect($existingAttachmentIds, $keepAttachmentIds)) + count($request->file('images', []));

        if ($bulletin->title !== $validated['title']) {
            $changes['title'] = ['from' => $bulletin->title, 'to' => $validated['title']];
        }

        if ($bulletin->body !== $validated['body']) {
            $changes['body_excerpt'] = ['from' => $this->excerpt($bulletin->body), 'to' => $this->excerpt($validated['body'])];
        }

        if ($bulletin->is_published !== $isPublished) {
            $changes['is_published'] = ['from' => $bulletin->is_published ? 'true' : 'false', 'to' => $isPublished ? 'true' : 'false'];
        }

        $publishedAt = $bulletin->published_at;

        if ($isPublished && ! $bulletin->is_published) {
            $publishedAt = now();
            $changes['published_at'] = ['from' => $bulletin->published_at?->format('Y-m-d H:i:s'), 'to' => $publishedAt->format('Y-m-d H:i:s')];
        }

        if ($bulletin->attachments()->count() !== $finalAttachmentCount) {
            $changes['images'] = [
                'from' => (string) $bulletin->attachments()->count(),
                'to' => $finalAttachmentCount > 0 ? (string) $finalAttachmentCount : null,
            ];
        }

        try {
            DB::transaction(function () use ($bulletin, $changes, $request, $validated, $isPublished, $publishedAt, $existingAttachments, $keepAttachmentIds, &$storedImages): void {
                $bulletin->fill([
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                    'is_published' => $isPublished,
                    'published_at' => $publishedAt,
                    'updated_by_user_id' => $request->user()?->id,
                ]);
                $bulletin->save();

                $this->storeImages($bulletin, $request->file('images', []), $storedImages);

                $attachmentsToDelete = $existingAttachments
                    ->reject(static fn (BulletinPostAttachment $attachment): bool => in_array($attachment->id, $keepAttachmentIds, true));

                $attachmentsToDelete->each(static function (BulletinPostAttachment $attachment): void {
                    $attachment->delete();
                });

                $this->recordActivity(
                    actor: $request->user(),
                    action: BulletinActivityLog::ACTION_UPDATED,
                    post: $bulletin,
                    changes: $changes,
                );
            });
        } catch (Throwable $throwable) {
            $this->cleanupStoredBulletinImages($storedImages);
            throw $throwable;
        }

        if ($shouldNotify) {
            $this->notifyPublishedPost($bulletin, $request->user());
        }

        return redirect()
            ->route('admin.tablon.index')
            ->with('success', html_entity_decode('El tabl&oacute;n se ha actualizado correctamente.'));
    }

    public function destroy(Request $request, BulletinPost $bulletin): RedirectResponse
    {
        $this->recordActivity(
            actor: $request->user(),
            action: BulletinActivityLog::ACTION_DELETED,
            post: $bulletin,
            changes: [
                'title' => ['from' => $bulletin->title, 'to' => null],
                'body_excerpt' => ['from' => $this->excerpt($bulletin->body), 'to' => null],
                'is_published' => ['from' => $bulletin->is_published ? 'true' : 'false', 'to' => null],
                'published_at' => ['from' => $bulletin->published_at?->format('Y-m-d H:i:s'), 'to' => null],
                'images' => ['from' => (string) $bulletin->attachments()->count(), 'to' => null],
            ],
        );

        $bulletin->delete();

        return redirect()
            ->route('admin.tablon.index')
            ->with('success', html_entity_decode('La publicaci&oacute;n se ha eliminado correctamente.'));
    }

    private function excerpt(string $body): string
    {
        return Str::limit(Str::squish($body), 120);
    }

    private function recordActivity(User $actor, string $action, BulletinPost $post, array $changes): void
    {
        app(BulletinActivityLogWriter::class)->record(
            actor: $actor,
            action: $action,
            bulletin: $post,
            changes: $changes,
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,avatar_url:string}>
     */
    private function mentionableUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar_path'])
            ->map(static function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ];
            })
            ->values();
    }

    /**
     * @param array<int, string> $storedImages
     * @param array<int, \Illuminate\Http\UploadedFile> $images
     */
    private function storeImages(BulletinPost $bulletin, array $images, array &$storedImages): void
    {
        if ($images === []) {
            return;
        }

        $nextSortOrder = (int) $bulletin->attachments()->count();

        foreach ($images as $image) {
            if (! $image) {
                continue;
            }

            $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension() ?: 'png');
            $filename = Str::uuid()->toString() . '.' . $extension;
            $path = $image->storeAs('bulletin-posts', $filename, 'public');
            $storedImages[] = $path;

            $bulletin->attachments()->create([
                'image_path' => $path,
                'sort_order' => $nextSortOrder,
            ]);

            $nextSortOrder++;
        }
    }

    /**
     * @param array<int, string> $storedImages
     */
    private function cleanupStoredBulletinImages(array $storedImages): void
    {
        foreach ($storedImages as $storedImage) {
            Storage::disk('public')->delete($storedImage);
        }
    }

    private function notifyPublishedPost(BulletinPost $post, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new AdminPriorityNotification(
                title: html_entity_decode('Nuevo anuncio en el tabl&oacute;n'),
                description: $post->title,
                linkUrl: route('tablon.index'),
                actor: $actor,
            )
        );
    }
}
