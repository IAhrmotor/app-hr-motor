<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyChatGroup extends Model
{
    use HasFactory;

    public const SYSTEM_GROUP_TYPE_EXTRA_ROLE = 'extra_role';

    public const SYSTEM_GROUP_TYPE_DEALERSHIP = 'dealership';

    protected $fillable = [
        'name',
        'system_group_type',
        'system_group_key',
    ];

    public function isSystemManaged(): bool
    {
        return filled($this->system_group_type) && filled($this->system_group_key);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CompanyChatGroupActivityLog::class, 'company_chat_group_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(CompanyChatConversation::class, 'company_chat_group_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_chat_group_user')
            ->withTimestamps()
            ->orderBy('name');
    }
}
