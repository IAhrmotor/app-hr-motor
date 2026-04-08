<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class ForumThread extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
        'resolved_at',
        'resolved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $thread): void {
            $thread->attachments()->each(fn (ForumThreadAttachment $attachment) => $attachment->delete());
            $thread->replies()->with('attachments')->get()->each(function (ForumReply $reply): void {
                $reply->delete();
            });
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'forum_thread_id')->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ForumThreadAttachment::class, 'forum_thread_id');
    }

    public function latestReply(): HasOne
    {
        return $this->hasOne(ForumReply::class, 'forum_thread_id')->latestOfMany();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ForumTag::class, 'forum_tag_forum_thread')
            ->withTimestamps();
    }

    public function scopeOrderedForListing(Builder $query): Builder
    {
        return $query
            ->orderByRaw("case when status = ? then 0 else 1 end asc", [self::STATUS_OPEN])
            ->orderByDesc('created_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_RESOLVED ? 'Resuelta' : 'Abierta';
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
