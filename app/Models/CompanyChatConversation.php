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
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(CompanyChatMessage::class, 'company_chat_conversation_id');
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
