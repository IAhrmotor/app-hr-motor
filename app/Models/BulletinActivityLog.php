<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BulletinPost;
use App\Models\User;

class BulletinActivityLog extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public $timestamps = false;

    protected $fillable = [
        'action',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'target_bulletin_post_id',
        'target_name',
        'target_reference',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function bulletinPost(): BelongsTo
    {
        return $this->belongsTo(BulletinPost::class, 'target_bulletin_post_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Alta',
            self::ACTION_UPDATED => 'Edición',
            self::ACTION_DELETED => 'Eliminación',
            default => ucfirst((string) $this->action),
        };
    }
}
