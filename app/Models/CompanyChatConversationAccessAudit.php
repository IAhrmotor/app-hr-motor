<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChatConversationAccessAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_chat_conversation_id',
        'admin_user_id',
        'admin_email',
        'action',
        'conversation_type',
        'affected_user_ids',
        'reason',
        'accessed_at',
        'ip_address',
        'user_agent',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'affected_user_ids' => 'array',
            'accessed_at' => 'datetime',
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
