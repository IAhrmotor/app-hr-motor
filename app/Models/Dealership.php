<?php

namespace App\Models;

use App\Services\CompanyChatDefaultGroupSyncService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
        'zone_id',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $dealership): void {
            app(CompanyChatDefaultGroupSyncService::class)->syncDealership($dealership);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
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
        if (! $this->image_path) {
            return null;
        }

        $publicPath = public_path($this->image_path);

        if (File::exists($publicPath)) {
            return asset($this->image_path);
        }

        $storagePath = storage_path('app/public/' . ltrim($this->image_path, '/'));

        if (File::exists($storagePath)) {
            return asset('storage/' . ltrim($this->image_path, '/'));
        }

        return asset($this->image_path);
    }

    public static function storeImageFile(TemporaryUploadedFile $file): string
    {
        $directory = public_path('images/dealerships');
        File::ensureDirectoryExists($directory);

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'png';
        $filename = sprintf('%s.%s', Str::uuid(), strtolower($extension));

        File::copy($file->getRealPath(), $directory . DIRECTORY_SEPARATOR . $filename);

        return 'images/dealerships/' . $filename;
    }

    public static function deleteStoredImagePath(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        $publicPath = public_path($path);

        if (File::exists($publicPath)) {
            File::delete($publicPath);
        }

        $storagePath = storage_path('app/public/' . ltrim($path, '/'));

        if (File::exists($storagePath)) {
            File::delete($storagePath);
        }
    }
}
