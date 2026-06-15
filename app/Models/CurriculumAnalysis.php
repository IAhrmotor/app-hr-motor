<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'job_title',
        'location',
        'offer_description',
        'mandatory_requirements',
        'valuable_requirements',
        'top_candidates_count',
        'status',
        'total_candidates',
        'processed_candidates',
        'report_data',
        'error_message',
        'openai_model',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'mandatory_requirements' => 'array',
            'valuable_requirements' => 'array',
            'report_data' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CurriculumAnalysisDocument::class);
    }

    public function completedDocuments(): HasMany
    {
        return $this->documents()->where('status', 'completed');
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

    public function getProgressAttribute(): int
    {
        return match ($this->status) {
            'completed' => 100,
            'failed' => $this->total_candidates > 0
                ? (int) round(($this->processed_candidates / max($this->total_candidates, 1)) * 100)
                : 0,
            'processing' => $this->total_candidates > 0
                ? min(95, max(5, (int) round(($this->processed_candidates / max($this->total_candidates, 1)) * 90)))
                : 10,
            default => 0,
        };
    }

    public function getOverallSummaryAttribute(): string
    {
        $reportData = $this->report_data ?? [];
        $fullRanking = collect($reportData['full_ranking'] ?? []);

        if ($fullRanking->isEmpty()) {
            return (string) ($reportData['overall_summary'] ?? '');
        }

        $count = $fullRanking->count();
        $scores = $fullRanking->pluck('score')->map(fn ($score): int => (int) $score)->all();
        $averageScore = (int) round(array_sum($scores) / max(1, count($scores)));
        $bestCandidate = (string) ($fullRanking->first()['candidate_name'] ?? 'el primer candidato');
        $bestScore = (int) ($fullRanking->first()['score'] ?? 0);
        $secondBestScore = (int) ($fullRanking->get(1)['score'] ?? $bestScore);
        $scoreSpread = max(0, $bestScore - $secondBestScore);

        return trim(
            'Se han analizado ' . $count . ' candidatos para este proceso. '
            . 'El ranking ya separa claramente los perfiles por encaje, con una media de ' . $averageScore . '/100. '
            . 'El mejor posicionado es ' . $bestCandidate . ' con ' . $bestScore . '/100. '
            . ($scoreSpread > 0
                ? 'Hay una diferencia de ' . $scoreSpread . ' puntos respecto al siguiente perfil, por lo que el top está bien diferenciado.'
                : 'Los primeros puestos están bastante ajustados y conviene revisar el detalle de los mejores candidatos.')
        );
    }
}
