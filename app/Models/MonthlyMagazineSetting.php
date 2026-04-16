<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class MonthlyMagazineSetting extends Model
{
    public const DEFAULT_TAG_LABEL = 'Abril';

    public const DEFAULT_PDF_PATH = 'revista/revista-abril-2026.pdf';

    protected $fillable = [
        'tag_label',
        'pdf_path',
        'original_filename',
        'updated_by_user_id',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('monthly_magazine_settings')) {
            return static::make([
                'tag_label' => self::DEFAULT_TAG_LABEL,
                'pdf_path' => self::DEFAULT_PDF_PATH,
                'original_filename' => basename(self::DEFAULT_PDF_PATH),
            ]);
        }

        return static::query()->latest('updated_at')->first() ?? static::make([
            'tag_label' => self::DEFAULT_TAG_LABEL,
            'pdf_path' => self::DEFAULT_PDF_PATH,
            'original_filename' => basename(self::DEFAULT_PDF_PATH),
        ]);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function getPdfUrlAttribute(): string
    {
        return asset($this->pdf_path ?: self::DEFAULT_PDF_PATH);
    }
}
