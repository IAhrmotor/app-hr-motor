<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleBusinessProfileMonthlySnapshot extends Model
{
    protected $fillable = [
        'dealership_id',
        'snapshot_month',
        'total_reviews',
        'average_rating',
        'monthly_reviews',
        'monthly_average_rating',
        'unanswered_reviews',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_month' => 'date',
            'average_rating' => 'decimal:2',
            'monthly_average_rating' => 'decimal:2',
            'captured_at' => 'datetime',
        ];
    }

    public function dealership(): BelongsTo
    {
        return $this->belongsTo(Dealership::class);
    }
}
