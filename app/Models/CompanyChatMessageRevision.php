<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChatMessageRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_chat_message_id',
        'body',
        'edited_at',
        'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CompanyChatMessage::class, 'company_chat_message_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
