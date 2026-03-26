<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLeaderboardEntry extends Model
{
    protected $fillable = [
        'temperature',
        'ranking_position',
        'vehicle_salesforce_id',
        'vehicle_name',
        'total_leads',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'total_leads' => 'integer',
            'synced_at' => 'datetime',
        ];
    }
}
