<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

class ForumReplyAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'forum_reply_id',
        'image_path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $attachment): void {
            $path = public_path($attachment->image_path);

            if (File::exists($path)) {
                File::delete($path);
            }
        });
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'forum_reply_id');
    }

    public function getImageUrlAttribute(): string
    {
        return asset($this->image_path);
    }
}
