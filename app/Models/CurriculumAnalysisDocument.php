<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumAnalysisDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'curriculum_analysis_id',
        'original_name',
        'stored_path',
        'mime_type',
        'file_size',
        'order_index',
        'status',
        'openai_file_id',
        'analysis_data',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'analysis_data' => 'array',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(CurriculumAnalysis::class, 'curriculum_analysis_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'queued' => 'En cola',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'failed' => 'Con incidencias',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'emerald',
            'failed' => 'red',
            default => 'amber',
        };
    }
}
