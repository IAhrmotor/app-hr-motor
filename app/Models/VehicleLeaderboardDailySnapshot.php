<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLeaderboardDailySnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'temperature',
        'ranking_position',
        'vehicle_salesforce_id',
        'vehicle_name',
        'vehicle_commercial_name',
        'vehicle_plate',
        'vehicle_image_url',
        'total_leads',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'total_leads' => 'integer',
            'captured_at' => 'datetime',
        ];
    }
}
