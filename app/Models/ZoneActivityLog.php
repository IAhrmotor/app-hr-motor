<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneActivityLog extends Model
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
        'target_zone_id',
        'target_name',
        'target_dealerships',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'target_dealerships' => 'array',
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'target_zone_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Alta',
            self::ACTION_UPDATED => 'Edicion',
            self::ACTION_DELETED => 'Eliminacion',
            default => ucfirst((string) $this->action),
        };
    }
}
