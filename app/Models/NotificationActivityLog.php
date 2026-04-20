<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationActivityLog extends Model
{
    use HasFactory;

    public const ACTION_SENT = 'sent';

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'actor_name',
        'actor_email',
        'title',
        'description',
        'link_url',
        'target_roles',
        'recipient_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'recipient_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return 'Envío';
    }
}
