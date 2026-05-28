<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChatRetentionHoldAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_chat_conversation_id',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CompanyChatConversation::class, 'company_chat_conversation_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
