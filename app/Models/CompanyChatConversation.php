<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
        'last_message_excerpt',
        'retention_hold',
        'retention_hold_reason',
        'retention_hold_created_at',
        'retention_hold_created_by',
        'retention_hold_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'retention_hold' => 'boolean',
            'retention_hold_created_at' => 'datetime',
            'retention_hold_expires_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function retentionHoldCreatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retention_hold_created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CompanyChatMessage::class, 'company_chat_conversation_id');
    }

    public function accessAudits(): HasMany
    {
        return $this->hasMany(CompanyChatConversationAccessAudit::class, 'company_chat_conversation_id');
    }

    public function scopeWithActiveRetentionHold(Builder $query): Builder
    {
        $now = now();

        return $query->where('retention_hold', true)
            ->where(function (Builder $subquery) use ($now): void {
                $subquery->whereNull('retention_hold_expires_at')
                    ->orWhere('retention_hold_expires_at', '>', $now);
            });
    }

    public function scopeAvailableForRetentionHold(Builder $query): Builder
    {
        $now = now();

        return $query->where(function (Builder $subquery) use ($now): void {
            $subquery->where('retention_hold', false)
                ->orWhere(function (Builder $holdQuery) use ($now): void {
                    $holdQuery->where('retention_hold', true)
                        ->whereNotNull('retention_hold_expires_at')
                        ->where('retention_hold_expires_at', '<=', $now);
                });
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

    public function getRetentionHoldTargetLabelAttribute(): string
    {
        $participants = collect([
            $this->userOne?->name,
            $this->userTwo?->name,
        ])->filter()->values();

        if ($participants->count() === 0) {
            return '';
        }

        if ($participants->count() === 1) {
            return (string) $participants->first();
        }

        return $participants->join(' con ');
    }

    public function getConversationTypeLabelAttribute(): string
    {
        return 'Privada';
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $subquery) use ($user): void {
            $subquery->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id);
        });
    }

    public static function sortParticipantIds(User $firstUser, User $secondUser): array
    {
        return collect([$firstUser->id, $secondUser->id])
            ->sort()
            ->values()
            ->all();
    }

    public static function betweenUsers(User $firstUser, User $secondUser): ?self
    {
        [$userOneId, $userTwoId] = self::sortParticipantIds($firstUser, $secondUser);

        return self::query()
            ->where('user_one_id', $userOneId)
            ->where('user_two_id', $userTwoId)
            ->first();
    }

    public static function createBetweenUsers(User $firstUser, User $secondUser): self
    {
        [$userOneId, $userTwoId] = self::sortParticipantIds($firstUser, $secondUser);

        return self::query()->create([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    public function otherParticipant(User $user): ?User
    {
        if ($this->user_one_id === $user->id) {
            return $this->userTwo;
        }

        if ($this->user_two_id === $user->id) {
            return $this->userOne;
        }

        return null;
    }

    public function involves(User $user): bool
    {
        return in_array($user->id, [$this->user_one_id, $this->user_two_id], true);
    }
}
