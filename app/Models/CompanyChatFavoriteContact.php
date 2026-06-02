<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChatFavoriteContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favorite_user_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function favoriteUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'favorite_user_id');
    }
}
