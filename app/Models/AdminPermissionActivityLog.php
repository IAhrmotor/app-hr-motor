<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminPermissionActivityLog extends Model
{
    use HasFactory;

    public const ACTION_GROUP_CREATED = 'group_created';
    public const ACTION_GROUP_UPDATED = 'group_updated';
    public const ACTION_GROUP_DELETED = 'group_deleted';
    public const ACTION_PERMISSION_SYNCED = 'permission_synced';

    public $timestamps = false;

    protected $fillable = [
        'action',
        'result',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'target_type',
        'target_id',
        'target_name',
        'permission_key',
        'scope',
        'changes',
        'reason',
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
        return $this->belongsTo(AdminPermissionGroup::class, 'target_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_GROUP_CREATED => 'Grupo creado',
            self::ACTION_GROUP_UPDATED => 'Grupo actualizado',
            self::ACTION_GROUP_DELETED => 'Grupo eliminado',
            self::ACTION_PERMISSION_SYNCED => 'Permisos sincronizados',
            default => ucfirst((string) $this->action),
        };
    }
}
