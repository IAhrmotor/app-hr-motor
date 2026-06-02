<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyChatMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_chat_conversation_id',
        'sender_id',
        'body',
        'attachments',
        'read_at',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'read_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
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

    public function isRead(): bool
    {
        return $this->read_at !== null;
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
