<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealership extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'salesforce_id',
        'image_path',
        'phone',
        'google_maps_url',
        'reviews_url',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }
}
