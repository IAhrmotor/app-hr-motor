<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function dealerships(): HasMany
    {
        return $this->hasMany(Dealership::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ZoneActivityLog::class, 'target_zone_id');
    }
}
