<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CompanyChatRetentionUserHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'retention_hold',
        'retention_hold_reason',
        'retention_hold_created_at',
        'retention_hold_created_by',
        'retention_hold_expires_at',
        'retention_hold_deactivated_at',
        'retention_hold_deactivated_by',
        'retention_hold_deactivation_reason',
    ];

    protected function casts(): array
    {
        return [
            'retention_hold' => 'boolean',
            'retention_hold_created_at' => 'datetime',
            'retention_hold_expires_at' => 'datetime',
            'retention_hold_deactivated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retention_hold_created_by');
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retention_hold_deactivated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('retention_hold', true)
            ->where(function (Builder $subquery) use ($now): void {
                $subquery->whereNull('retention_hold_expires_at')
                    ->orWhere('retention_hold_expires_at', '>', $now);
            });
    }

    public function hasActiveRetentionHold(?\Illuminate\Support\Carbon $at = null): bool
    {
        $at ??= now();

        if (! $this->retention_hold) {
            return false;
        }

        if ($this->retention_hold_expires_at === null) {
            return true;
        }

        return $this->retention_hold_expires_at->greaterThan($at);
    }

    public function getRetentionHoldStatusLabelAttribute(): string
    {
        if (! $this->retention_hold) {
            return 'Sin bloqueo';
        }

        if ($this->retention_hold_expires_at !== null && $this->retention_hold_expires_at->isPast()) {
            return 'Caducado';
        }

        return 'Activo';
    }
}
