<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentActivityLog extends Model
{
    use HasFactory;

    public const CONTENT_TYPE_MAGAZINE = 'magazine';

    public const CONTENT_TYPE_FORUM_TAG = 'forum_tag';

    public const CONTENT_TYPE_CONTACT = 'contact';

    public const CONTENT_TYPE_BULLETIN = 'bulletin';

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public $timestamps = false;

    protected $fillable = [
        'content_type',
        'action',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'target_name',
        'target_reference',
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

    public function getContentTypeLabelAttribute(): string
    {
        return match ($this->content_type) {
            self::CONTENT_TYPE_MAGAZINE => 'Revista mensual',
            self::CONTENT_TYPE_FORUM_TAG => 'Tag del foro',
            self::CONTENT_TYPE_CONTACT => 'Contacto',
            self::CONTENT_TYPE_BULLETIN => 'Tablón',
            default => ucfirst((string) $this->content_type),
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Alta',
            self::ACTION_UPDATED => 'Edición',
            self::ACTION_DELETED => 'Eliminación',
            default => ucfirst((string) $this->action),
        };
    }
}
