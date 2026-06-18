<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BulletinPostAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulletin_post_id',
        'image_path',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $attachment): void {
            Storage::disk('public')->delete($attachment->image_path);
        });
    }

    public function bulletinPost(): BelongsTo
    {
        return $this->belongsTo(BulletinPost::class, 'bulletin_post_id');
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . ltrim($this->image_path, '/'));
    }
}
