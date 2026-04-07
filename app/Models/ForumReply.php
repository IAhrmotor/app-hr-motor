<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'forum_thread_id',
        'user_id',
        'content',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $reply): void {
            $reply->attachments()->each(fn (ForumReplyAttachment $attachment) => $attachment->delete());
        });
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ForumReplyAttachment::class, 'forum_reply_id');
    }
}
