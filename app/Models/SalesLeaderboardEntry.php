<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesLeaderboardEntry extends Model
{
    protected $fillable = [
        'ranking_position',
        'user_id',
        'salesforce_user_id',
        'seller_name',
        'total_sales',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'total_sales' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
