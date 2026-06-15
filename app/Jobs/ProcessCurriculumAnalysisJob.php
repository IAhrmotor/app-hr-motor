<?php

namespace App\Jobs;

use App\Models\CurriculumAnalysis;
use App\Services\OpenAiCurriculumAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessCurriculumAnalysisJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $analysisId)
    {
        $this->onQueue('curriculum-analyses');
    }

    public function handle(OpenAiCurriculumAnalysisService $service): void
    {
        $analysis = CurriculumAnalysis::query()->with('documents')->find($this->analysisId);

        if (! $analysis) {
            return;
        }

        $analysis->forceFill([
            'status' => 'processing',
            'started_at' => $analysis->started_at ?? Carbon::now(),
            'openai_model' => config('openai.model', 'gpt-5.5'),
            'error_message' => null,
        ])->saveQuietly();

        $documents = $analysis->documents->sortBy('order_index')->values();
        $candidateAnalyses = [];

        foreach ($documents as $document) {
            try {
                $document->forceFill([
                    'status' => 'processing',
                    'error_message' => null,
                ])->saveQuietly();

                $candidateAnalysis = $service->analyzeDocument($analysis, $document);
                $candidateAnalysis = $this->normalizeCandidateAnalysis($candidateAnalysis, $document->original_name);

                $document->forceFill([
                    'status' => 'completed',
                    'analysis_data' => $candidateAnalysis,
                ])->saveQuietly();

                $candidateAnalyses[] = [
                    'document_id' => $document->id,
                    'original_name' => $document->original_name,
                    'analysis' => $candidateAnalysis,
                ];
            } catch (Throwable $exception) {
                $document->forceFill([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ])->saveQuietly();

                Log::warning('Curriculum analysis document failed.', [
                    'analysis_id' => $analysis->id,
                    'document_id' => $document->id,
                    'message' => $exception->getMessage(),
                ]);

                $candidateAnalyses[] = [
                    'document_id' => $document->id,
                    'original_name' => $document->original_name,
                    'analysis' => [
                        'candidate_name' => pathinfo($document->original_name, PATHINFO_FILENAME),
                        'score' => 0,
                        'fit_level' => 'bajo',
                        'summary' => 'No se pudo analizar este curriculo.',
                        'strengths' => [],
                        'risks' => [$exception->getMessage()],
                        'doubts' => [],
                        'recommended_interview_questions' => [],
                        'recommended_next_step' => 'Revisar el archivo manualmente.',
                        'evidence' => [],
                    ],
                ];
            }

            $analysis->forceFill([
                'processed_candidates' => $analysis->processed_candidates + 1,
            ])->saveQuietly();
        }

        $fullRanking = $this->buildFullRanking($candidateAnalyses);
        $topCandidatesCount = max(1, (int) ($analysis->top_candidates_count ?: config('openai.analysis_top_candidates', 5)));
        $topCandidates = array_slice($fullRanking, 0, $topCandidatesCount);

        try {
            $report = $service->generateReport($analysis, array_map(
                fn (array $item): array => $item['analysis'],
                $candidateAnalyses
            ));
        } catch (Throwable $exception) {
            Log::warning('Curriculum analysis report generation timed out or failed; using local fallback.', [
                'analysis_id' => $analysis->id,
                'message' => $exception->getMessage(),
            ]);

            $report = $this->buildLocalReport($analysis, $fullRanking, $topCandidates);
        }

        $overallSummary = $this->buildOverallSummary($analysis, $fullRanking, $topCandidates);

        $analysis->forceFill([
            'status' => 'completed',
            'report_data' => array_merge($report, [
                'overall_summary' => $overallSummary,
                'total_candidates_ranked' => count($fullRanking),
                'top_candidates' => array_map(function (array $candidate): array {
                    return [
                        'candidate_name' => $candidate['candidate_name'],
                        'score' => $candidate['score'],
                        'reason' => $candidate['summary'] !== '' ? $candidate['summary'] : 'Encaje valorado positivamente.',
                        'risks' => array_values(array_filter((array) ($candidate['risks'] ?? []))),
                        'recommended_interview_questions' => array_values(array_filter((array) ($candidate['recommended_interview_questions'] ?? []))),
                    ];
                }, $topCandidates),
                'full_ranking' => $fullRanking,
            ]),
            'finished_at' => Carbon::now(),
        ])->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $candidateAnalysis
     * @return array<string, mixed>
     */
    private function normalizeCandidateAnalysis(array $candidateAnalysis, string $fallbackName): array
    {
        $candidateName = (string) ($candidateAnalysis['candidate_name'] ?? pathinfo($fallbackName, PATHINFO_FILENAME));
        $summary = trim((string) ($candidateAnalysis['summary'] ?? ''));
        $strengths = array_values(array_filter(array_map('trim', (array) ($candidateAnalysis['strengths'] ?? []))));
        $doubts = array_values(array_filter(array_map('trim', (array) ($candidateAnalysis['doubts'] ?? []))));
        $risks = array_values(array_filter(array_map('trim', (array) ($candidateAnalysis['risks'] ?? []))));
        $questions = array_values(array_filter(array_map('trim', (array) ($candidateAnalysis['recommended_interview_questions'] ?? []))));

        if ($risks === []) {
            $risks = $this->buildFallbackRisks($candidateName, $summary, $strengths, $doubts);
        }

        if ($questions === []) {
            $questions = $this->buildFallbackQuestions($candidateName, $summary, $strengths, $doubts);
        }

        if ($summary === '') {
            $summary = 'Encaje pendiente de contraste con la entrevista.';
        }

        return array_merge($candidateAnalysis, [
            'candidate_name' => $candidateName,
            'summary' => $summary,
            'risks' => $risks,
            'recommended_interview_questions' => $questions,
        ]);
    }

    /**
     * @param  array<int, string>  $strengths
     * @param  array<int, string>  $doubts
     * @return array<int, string>
     */
    private function buildFallbackRisks(string $candidateName, string $summary, array $strengths, array $doubts): array
    {
        $context = $this->inferInterviewContext($candidateName, $summary, $strengths, $doubts);
        $risks = [];

        if ($doubts !== []) {
            $risks[] = 'Contrastar en entrevista: ' . Str::limit($doubts[0], 120, '');
        }

        $risks[] = "Validar experiencia real en {$context} y su nivel de autonomia.";
        $risks[] = 'Confirmar resultados medibles, volumen de trabajo y referencias concretas.';

        return array_values(array_unique(array_slice($risks, 0, 3)));
    }

    /**
     * @param  array<int, string>  $strengths
     * @param  array<int, string>  $doubts
     * @return array<int, string>
     */
    private function buildFallbackQuestions(string $candidateName, string $summary, array $strengths, array $doubts): array
    {
        $context = $this->inferInterviewContext($candidateName, $summary, $strengths, $doubts);

        return [
            "¿Puedes concretar tu experiencia más reciente en {$context}?",
            '¿Qué resultados medibles puedes aportar en un contexto similar?',
            '¿Qué disponibilidad real tienes para incorporarte y asumir el puesto?',
        ];
    }

    /**
     * @param  array<int, string>  $strengths
     * @param  array<int, string>  $doubts
     */
    private function inferInterviewContext(string $candidateName, string $summary, array $strengths, array $doubts): string
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $candidateName,
            $summary,
            implode(' ', $strengths),
            implode(' ', $doubts),
        ])));

        $keywords = [
            'ciberseguridad' => 'ciberseguridad',
            'seguridad' => 'seguridad',
            'ventas' => 'ventas',
            'comercial' => 'ventas',
            'cliente' => 'atencion al cliente',
            'atencion al cliente' => 'atencion al cliente',
            'automatizacion' => 'automatizacion',
            'analisis' => 'analisis',
            'desarrollo' => 'desarrollo',
            'programacion' => 'desarrollo',
            'rrhh' => 'seleccion y rrhh',
            'recursos humanos' => 'seleccion y rrhh',
            'administracion' => 'administracion',
            'marketing' => 'marketing',
            'operaciones' => 'operaciones',
        ];

        foreach ($keywords as $keyword => $label) {
            if (Str::contains($haystack, $keyword)) {
                return $label;
            }
        }

        return 'este puesto';
    }

    /**
     * @param  array<int, array<string, mixed>>  $fullRanking
     * @param  array<int, array<string, mixed>>  $topCandidates
     */
    private function buildOverallSummary(CurriculumAnalysis $analysis, array $fullRanking, array $topCandidates): string
    {
        $count = count($fullRanking);

        if ($count === 0) {
            return 'No se ha podido construir el resumen porque no hay candidatos procesados.';
        }

        $scores = collect($fullRanking)
            ->pluck('score')
            ->map(fn ($score): int => (int) $score)
            ->all();

        $averageScore = (int) round(array_sum($scores) / max(1, count($scores)));
        $bestCandidate = $topCandidates[0]['candidate_name'] ?? ($fullRanking[0]['candidate_name'] ?? 'el primer candidato');
        $bestScore = (int) ($topCandidates[0]['score'] ?? ($fullRanking[0]['score'] ?? 0));
        $secondBestScore = (int) ($topCandidates[1]['score'] ?? ($fullRanking[1]['score'] ?? $bestScore));
        $scoreSpread = max(0, $bestScore - $secondBestScore);

        return trim(
            'Se han analizado ' . $count . ' candidatos para el proceso "' . $analysis->title . '". '
            . 'El ranking ya separa claramente los perfiles por encaje, con una media de ' . $averageScore . '/100. '
            . 'El mejor posicionado es ' . $bestCandidate . ' con ' . $bestScore . '/100. '
            . ($scoreSpread > 0
                ? 'Hay una diferencia de ' . $scoreSpread . ' puntos respecto al siguiente perfil, por lo que el top está bien diferenciado.'
                : 'Los primeros puestos están bastante ajustados y conviene revisar el detalle de los mejores candidatos.')
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidateAnalyses
     * @return array<int, array<string, mixed>>
     */
    private function buildFullRanking(array $candidateAnalyses): array
    {
        return collect($candidateAnalyses)
            ->sortByDesc(fn (array $candidate): int => (int) data_get($candidate, 'analysis.score', 0))
            ->values()
            ->map(function (array $candidate, int $index): array {
                $candidateAnalysis = (array) ($candidate['analysis'] ?? []);

                return [
                    'rank' => $index + 1,
                    'candidate_name' => (string) data_get($candidateAnalysis, 'candidate_name', $candidate['original_name'] ?? 'Candidato'),
                    'score' => (int) data_get($candidateAnalysis, 'score', 0),
                    'summary' => (string) data_get($candidateAnalysis, 'summary', ''),
                    'fit_level' => (string) data_get($candidateAnalysis, 'fit_level', ''),
                    'strengths' => array_values(array_filter((array) data_get($candidateAnalysis, 'strengths', []))),
                    'doubts' => array_values(array_filter((array) data_get($candidateAnalysis, 'doubts', []))),
                    'risks' => array_values(array_filter((array) data_get($candidateAnalysis, 'risks', []))),
                    'recommended_interview_questions' => array_values(array_filter((array) data_get($candidateAnalysis, 'recommended_interview_questions', []))),
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fullRanking
     * @param  array<int, array<string, mixed>>  $topCandidates
     * @return array<string, mixed>
     */
    private function buildLocalReport(CurriculumAnalysis $analysis, array $fullRanking, array $topCandidates): array
    {
        $topNames = collect($topCandidates)
            ->pluck('candidate_name')
            ->filter()
            ->take(3)
            ->implode(', ');

        $commonRisks = collect($fullRanking)
            ->pluck('risks')
            ->flatten()
            ->filter()
            ->unique()
            ->take(6)
            ->values()
            ->all();

        $recommendedQuestions = collect($topCandidates)
            ->pluck('recommended_interview_questions')
            ->flatten()
            ->filter()
            ->unique()
            ->take(6)
            ->values()
            ->all();

        $recommendedNextSteps = array_values(array_filter([
            $topNames !== '' ? 'Entrevistar a: ' . $topNames . '.' : 'Entrevistar primero a los candidatos mejor puntuados.',
            'Revisar con detalle a los perfiles con dudas en disponibilidad, resultados o experiencia real.',
            'Guardar una reserva de candidatos con encaje medio por si falla el top inicial.',
        ]));

        return [
            'overall_summary' => 'Se han analizado ' . count($fullRanking) . ' candidatos y se ha generado el ranking completo. ' . ($topNames !== '' ? 'Los primeros perfiles destacados son ' . $topNames . '.' : ''),
            'common_risks' => $commonRisks !== [] ? $commonRisks : ['Validar experiencia real, resultados y disponibilidad.'],
            'recommended_interview_questions' => $recommendedQuestions !== [] ? $recommendedQuestions : [
                '¿Puedes ampliar tu experiencia más relevante para este puesto?',
                '¿Qué resultados medibles puedes aportar en un contexto similar?',
                '¿Qué disponibilidad real tienes para incorporarte y asumir el puesto?',
            ],
            'recommended_next_steps' => $recommendedNextSteps,
        ];
    }
}
