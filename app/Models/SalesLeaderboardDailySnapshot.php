<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesLeaderboardDailySnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'ranking_position',
        'user_id',
        'salesforce_user_id',
        'seller_name',
        'total_sales',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'total_sales' => 'decimal:2',
            'captured_at' => 'datetime',
        ];
    }
}
