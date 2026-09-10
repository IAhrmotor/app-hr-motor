<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CompanyChatMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const EDIT_AND_DELETE_WINDOW_MINUTES = 2;

    protected $fillable = [
        'company_chat_conversation_id',
        'sender_id',
        'body',
        'attachments',
        'mentioned_user_ids',
        'read_at',
        'edited_at',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'mentioned_user_ids' => 'array',
            'read_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_system' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CompanyChatConversation::class, 'company_chat_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CompanyChatMessageRevision::class, 'company_chat_message_id')
            ->orderBy('edited_at')
            ->orderBy('id');
    }

    /**
     * @return HasMany<CompanyChatMessageRead>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(CompanyChatMessageRead::class, 'company_chat_message_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isSystemMessage(): bool
    {
        return (bool) $this->is_system;
    }

    /**
     * Return the single source of truth used by the justified-access UI and CSV export.
     */
    public function accessMessageState(): string
    {
        if ($this->trashed() && $this->edited_at !== null) {
            return 'deleted_and_edited';
        }

        if ($this->trashed()) {
            return 'deleted';
        }

        if ($this->isSystemMessage()) {
            return 'system';
        }

        if ($this->edited_at !== null) {
            return 'edited';
        }

        return 'normal';
    }

    public function accessMessageStateLabel(): string
    {
        return match ($this->accessMessageState()) {
            'deleted_and_edited' => 'Eliminado',
            'deleted' => 'Eliminado',
            'system' => 'Sistema',
            'edited' => 'Editado',
            default => 'Normal',
        };
    }

    public function accessMessageContent(): string
    {
        $body = (string) $this->body;

        if (trim($body) !== '') {
            return $body;
        }

        if ($this->isSystemMessage()) {
            return 'Evento del sistema';
        }

        return filled($this->attachments) ? '[Adjunto]' : 'Mensaje sin texto.';
    }

    public function mentionsUser(User $user): bool
    {
        return in_array($user->id, $this->mentioned_user_ids ?? [], true);
    }

    public function canBeEditedOrDeletedBy(User $user, ?Carbon $at = null): bool
    {
        if ($this->sender_id !== $user->id || $this->created_at === null) {
            return false;
        }

        $at ??= now();

        return ! $at->greaterThan($this->created_at->copy()->addMinutes(self::EDIT_AND_DELETE_WINDOW_MINUTES));
    }

    public function isEditAndDeleteWindowExpired(?Carbon $at = null): bool
    {
        if ($this->created_at === null) {
            return true;
        }

        $at ??= now();

        return $at->greaterThan($this->created_at->copy()->addMinutes(self::EDIT_AND_DELETE_WINDOW_MINUTES));
    }

    public function getPreviewTextAttribute(): string
    {
        if ($this->trashed()) {
            return 'Mensaje eliminado';
        }

        $body = trim((string) $this->body);

        if ($body !== '') {
            return str($body)->squish()->limit(140)->toString();
        }

        $attachments = collect($this->attachments ?? []);
        $count = $attachments->count();

        if ($count === 1) {
            $name = (string) ($attachments->first()['original_name'] ?? 'archivo');

            return 'Archivo adjunto: ' . $name;
        }

        if ($count > 1) {
            return $count . ' archivos adjuntos';
        }

        return 'Mensaje sin texto';
    }
}
