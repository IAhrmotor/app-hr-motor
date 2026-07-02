<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\TicketTool;

class ItTicket extends Model
{
    protected $table = 'it_tickets';

    protected $fillable = [
        'user_id',
        'assigned_to_user_id',
        'ticket_tool_id',
        'number',
        'tool',
        'priority',
        'status',
        'title',
        'description',
        'screenshots',
    ];

    protected $casts = [
        'screenshots' => 'array',
    ];

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes): string {
                if (empty($attributes['assigned_to_user_id']) && in_array($value, [null, '', 'new'], true)) {
                    return 'new';
                }

                return $value === 'resolved' ? 'closed' : ($value ?? 'new');
            },
            set: function (?string $value, array $attributes): string {
                if (empty($attributes['assigned_to_user_id']) && in_array($value, [null, '', 'new'], true)) {
                    return 'new';
                }

                return $value === 'resolved' ? 'closed' : ($value ?? 'new');
            },
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function ticketTool(): BelongsTo
    {
        return $this->belongsTo(TicketTool::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ItTicketMessage::class, 'it_ticket_id')->orderBy('created_at');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TicketActivityLog::class, 'it_ticket_id')->orderBy('created_at')->orderBy('id');
    }
}
