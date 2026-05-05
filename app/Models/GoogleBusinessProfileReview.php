<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleBusinessProfileReview extends Model
{
    protected $fillable = [
        'dealership_id',
        'location_name',
        'location_title',
        'review_name',
        'reviewer_name',
        'reviewer_photo_url',
        'rating',
        'comment',
        'reply_name',
        'reply_comment',
        'reply_updated_at',
        'review_created_at',
        'review_updated_at',
        'synced_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'reply_updated_at' => 'datetime',
            'review_created_at' => 'datetime',
            'review_updated_at' => 'datetime',
            'synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function dealership(): BelongsTo
    {
        return $this->belongsTo(Dealership::class);
    }

    public function isAnswered(): bool
    {
        return filled($this->reply_comment);
    }

    public function getCommentAttribute(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : trim((string) $value);
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $parts = preg_split('/\R*\(Translated by Google\)\R*/i', $value, 2);
        $cleanValue = trim((string) ($parts[0] ?? $value));

        return $cleanValue === '' ? null : $cleanValue;
    }
}
