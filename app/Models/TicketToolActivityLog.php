<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TicketTool;
use App\Models\User;

class TicketToolActivityLog extends Model
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
        'target_ticket_tool_id',
        'target_name',
        'target_color',
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

    public function tool(): BelongsTo
    {
        return $this->belongsTo(TicketTool::class, 'target_ticket_tool_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Alta',
            self::ACTION_UPDATED => 'EdiciÃ³n',
            self::ACTION_DELETED => 'EliminaciÃ³n',
            default => ucfirst((string) $this->action),
        };
    }
}
