<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivityLog extends Model
{
    use HasFactory;

    public const EVENT_CREATED = 'created';
    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_COMMENT_ADDED = 'comment_added';
    public const EVENT_STATUS_CHANGED = 'status_changed';
    public const EVENT_PRIORITY_CHANGED = 'priority_changed';
    public const EVENT_TOOL_CHANGED = 'tool_changed';
    public const EVENT_CLOSED = 'closed';

    protected $fillable = [
        'it_ticket_id',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'event',
        'title',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ItTicket::class, 'it_ticket_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => 'Ticket creado',
            self::EVENT_ASSIGNED => 'Asignado',
            self::EVENT_COMMENT_ADDED => 'Comentario añadido',
            self::EVENT_STATUS_CHANGED => 'Estado cambiado',
            self::EVENT_PRIORITY_CHANGED => 'Prioridad cambiada',
            self::EVENT_TOOL_CHANGED => 'Tipo de incidencia cambiada',
            self::EVENT_CLOSED => 'Cerrado',
            default => ucfirst((string) $this->event),
        };
    }
}
