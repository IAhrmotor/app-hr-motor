<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ItTicketMessage extends Model
{
    use HasFactory;

    protected $touches = [
        'ticket',
    ];

    protected $fillable = [
        'it_ticket_id',
        'user_id',
        'body',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $message): void {
            foreach ($message->attachments ?? [] as $attachment) {
                $path = (string) data_get($attachment, 'path', '');

                if ($path === '') {
                    continue;
                }

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    continue;
                }

                $absolutePath = public_path('storage/' . ltrim($path, '/'));

                if (File::exists($absolutePath)) {
                    File::delete($absolutePath);
                }
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ItTicket::class, 'it_ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getPreviewTextAttribute(): string
    {
        $body = trim((string) $this->body);

        if ($body !== '') {
            return str($body)->squish()->limit(140)->toString();
        }

        $attachments = collect($this->attachments ?? []);
        $count = $attachments->count();

        if ($count === 1) {
            return 'Archivo adjunto: ' . (string) data_get($attachments->first(), 'name', 'imagen');
        }

        if ($count > 1) {
            return $count . ' archivos adjuntos';
        }

        return 'Mensaje sin texto';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeAttachments(array $attachments): array
    {
        return collect($attachments)
            ->map(function (array $attachment): array {
                return [
                    'name' => (string) ($attachment['name'] ?? 'imagen'),
                    'path' => (string) ($attachment['path'] ?? ''),
                ];
            })
            ->filter(fn (array $attachment): bool => $attachment['path'] !== '')
            ->values()
            ->all();
    }
}
