<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChatRetentionUserHoldAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_user_id',
        'action',
        'reason',
        'previous_reason',
        'expires_at',
        'previous_expires_at',
        'ip_address',
        'user_agent',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'previous_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
