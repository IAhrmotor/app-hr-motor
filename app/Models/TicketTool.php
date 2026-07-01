<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(ItTicket::class, 'ticket_tool_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TicketToolActivityLog::class, 'target_ticket_tool_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
