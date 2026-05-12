<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'google_business_profile_location_name',
        'google_business_profile_location_title',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function googleBusinessProfileReviews(): HasMany
    {
        return $this->hasMany(GoogleBusinessProfileReview::class);
    }

    public function googleBusinessProfileMonthlySnapshots(): HasMany
    {
        return $this->hasMany(GoogleBusinessProfileMonthlySnapshot::class);
    }

    public function scopeWithoutSalamanca(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereRaw('LOWER(COALESCE(name, "")) NOT LIKE ?', ['%salamanca%'])
                ->whereRaw('LOWER(COALESCE(google_business_profile_location_name, "")) NOT LIKE ?', ['%salamanca%'])
                ->whereRaw('LOWER(COALESCE(google_business_profile_location_title, "")) NOT LIKE ?', ['%salamanca%']);
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }
}
