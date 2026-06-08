<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChatGroupActivityLog extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public $timestamps = false;

    protected $fillable = [
        'action',
        'result',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'company_chat_group_id',
        'target_name',
        'target_description',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(CompanyChatGroup::class, 'company_chat_group_id');
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
