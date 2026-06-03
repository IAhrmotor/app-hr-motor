<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_DISABLED = 'user_disabled';

    public const ACTION_REACTIVATED = 'user_reactivated';

    public $timestamps = false;

    protected $fillable = [
        'action',
        'result',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'target_user_id',
        'target_name',
        'target_email',
        'target_role',
        'target_dealership',
        'changes',
        'reason',
        'ip_address',
        'user_agent',
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

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Alta',
            self::ACTION_UPDATED => 'Edición',
            self::ACTION_DELETED => 'Eliminación',
            self::ACTION_DISABLED => 'Desactivación',
            self::ACTION_REACTIVATED => 'Reactivación',
            default => ucfirst((string) $this->action),
        };
    }
}
