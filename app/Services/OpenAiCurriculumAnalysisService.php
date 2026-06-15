<?php

namespace App\Services;

use App\Models\CurriculumAnalysis;
use App\Models\CurriculumAnalysisDocument;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiCurriculumAnalysisService
{
    public function analyzeDocument(CurriculumAnalysis $analysis, CurriculumAnalysisDocument $document): array
    {
        $response = $this->chatCompletion([
            [
                'role' => 'system',
                'content' => $this->candidateSystemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'file',
                        'file' => [
                            'filename' => $document->original_name,
                            'file_data' => $this->base64FileData($document->stored_path),
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $this->candidateUserPrompt($analysis),
                    ],
                ],
            ],
        ], $this->candidateSchema());

        return $response;
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidateAnalyses
     */
    public function generateReport(CurriculumAnalysis $analysis, array $candidateAnalyses): array
    {
        return $this->chatCompletion([
            [
                'role' => 'system',
                'content' => $this->finalSystemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $this->finalUserPrompt($analysis, $candidateAnalyses),
                    ],
                ],
            ],
        ], $this->finalReportSchema());
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function chatCompletion(array $messages, array $schema): array
    {
        $this->ensureConfigured();

        $response = Http::withToken(config('openai.api_key'))
            ->timeout((int) config('openai.timeout_seconds', 120))
            ->retry(2, 500)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('openai.model', 'gpt-5.5'),
                'messages' => $messages,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => $schema['name'],
                        'strict' => true,
                        'schema' => $schema['schema'],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI devolvió un error: ' . $response->body());
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->pluck('text')
                ->filter()
                ->implode('');
        }

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI no devolvió contenido utilizable.');
        }

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI no devolvió un JSON válido.');
        }

        return $decoded;
    }

    private function candidateSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un analista senior de selección para Recursos Humanos.
Tu trabajo es evaluar currículums contra una oferta concreta.
No inventes datos que no estén en el CV.
Si un dato no aparece o no es claro, dilo explícitamente en dudas o riesgos.
Para cada candidato, genera riesgos y preguntas sugeridas concretas, específicas y útiles para entrevista.
No dejes esos campos vacíos.
Devuelve solo información útil para decidir entrevistas.
PROMPT;
    }

    private function finalSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un analista senior de RRHH que consolida evaluaciones individuales de candidatos.
Debes devolver un ranking final claro, objetivo y accionable.
No repitas texto vacío ni inventes datos.
PROMPT;
    }

    private function candidateUserPrompt(CurriculumAnalysis $analysis): string
    {
        return $this->buildOfferPrompt($analysis)
            . "\n\nInstrucciones de salida:\n"
            . "- Calcula una puntuación de 0 a 100.\n"
            . "- Resume el encaje en una frase clara.\n"
            . "- En riesgos escribe frases breves y accionables, por ejemplo: 'Validar experiencia real cerrando ventas y disponibilidad'.\n"
            . "- Enumera fortalezas, riesgos, dudas y preguntas sugeridas para la entrevista.\n"
            . "- Señala el siguiente paso recomendado.\n"
            . "- Si detectas huecos importantes, inclúyelos como riesgos o dudas.\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidateAnalyses
     */
    private function finalUserPrompt(CurriculumAnalysis $analysis, array $candidateAnalyses): string
    {
        return $this->buildOfferPrompt($analysis)
            . "\n\nEvaluaciones individuales de candidatos:\n"
            . json_encode($this->compactCandidateAnalyses($candidateAnalyses), JSON_UNESCAPED_UNICODE)
            . "\n\nDevuelve un ranking final con todos los candidatos ordenados, explicación del encaje, riesgos comunes, preguntas de entrevista y una recomendación final para RRHH."
            . "\nNo omitas ningún candidato en el ranking completo.";
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidateAnalyses
     * @return array<int, array<string, mixed>>
     */
    private function compactCandidateAnalyses(array $candidateAnalyses): array
    {
        return array_map(function (array $candidate): array {
            $analysis = (array) ($candidate['analysis'] ?? []);

            return [
                'document' => (string) ($candidate['original_name'] ?? 'Candidato'),
                'candidate_name' => (string) ($analysis['candidate_name'] ?? $candidate['original_name'] ?? 'Candidato'),
                'score' => (int) ($analysis['score'] ?? 0),
                'fit_level' => (string) ($analysis['fit_level'] ?? ''),
                'summary' => Str::limit((string) ($analysis['summary'] ?? ''), 240, ''),
                'strengths' => array_slice(array_values(array_filter((array) ($analysis['strengths'] ?? []))), 0, 3),
                'doubts' => array_slice(array_values(array_filter((array) ($analysis['doubts'] ?? []))), 0, 3),
                'risks' => array_slice(array_values(array_filter((array) ($analysis['risks'] ?? []))), 0, 3),
                'recommended_interview_questions' => array_slice(array_values(array_filter((array) ($analysis['recommended_interview_questions'] ?? []))), 0, 3),
                'recommended_next_step' => (string) ($analysis['recommended_next_step'] ?? ''),
            ];
        }, $candidateAnalyses);
    }

    private function buildOfferPrompt(CurriculumAnalysis $analysis): string
    {
        $mandatory = collect($analysis->mandatory_requirements ?? [])
            ->filter()
            ->values()
            ->implode("\n- ");
        $valuable = collect($analysis->valuable_requirements ?? [])
            ->filter()
            ->values()
            ->implode("\n- ");
        $location = filled($analysis->location) ? $analysis->location : 'No especificada';

        return "Oferta: {$analysis->job_title}\n"
            . "Ubicación: {$location}\n\n"
            . "Descripción de la oferta:\n{$analysis->offer_description}\n\n"
            . "Requisitos imprescindibles:\n- {$mandatory}\n\n"
            . "Requisitos valorables:\n- {$valuable}\n";
    }

    private function candidateSchema(): array
    {
        return [
            'name' => 'candidate_analysis',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'candidate_name' => ['type' => 'string'],
                    'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    'fit_level' => ['type' => 'string', 'enum' => ['excelente', 'alto', 'medio', 'bajo']],
                    'summary' => ['type' => 'string'],
                    'strengths' => [
                        'type' => 'array',
                        'minItems' => 2,
                        'items' => ['type' => 'string'],
                    ],
                    'risks' => [
                        'type' => 'array',
                        'minItems' => 2,
                        'items' => ['type' => 'string'],
                    ],
                    'doubts' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'items' => ['type' => 'string'],
                    ],
                    'recommended_interview_questions' => [
                        'type' => 'array',
                        'minItems' => 3,
                        'items' => ['type' => 'string'],
                    ],
                    'recommended_next_step' => ['type' => 'string'],
                    'evidence' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => [
                    'candidate_name',
                    'score',
                    'fit_level',
                    'summary',
                    'strengths',
                    'risks',
                    'doubts',
                    'recommended_interview_questions',
                    'recommended_next_step',
                    'evidence',
                ],
            ],
        ];
    }

    private function finalReportSchema(): array
    {
        return [
            'name' => 'candidate_report',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'overall_summary' => ['type' => 'string'],
                    'top_candidates' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'candidate_name' => ['type' => 'string'],
                                'score' => ['type' => 'integer'],
                                'reason' => ['type' => 'string'],
                            ],
                            'required' => ['candidate_name', 'score', 'reason'],
                        ],
                    ],
                    'full_ranking' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'rank' => ['type' => 'integer'],
                                'candidate_name' => ['type' => 'string'],
                                'score' => ['type' => 'integer'],
                                'summary' => ['type' => 'string'],
                            ],
                            'required' => ['rank', 'candidate_name', 'score', 'summary'],
                        ],
                    ],
                    'common_risks' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'recommended_interview_questions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'recommended_next_steps' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => [
                    'overall_summary',
                    'top_candidates',
                    'full_ranking',
                    'common_risks',
                    'recommended_interview_questions',
                    'recommended_next_steps',
                ],
            ],
        ];
    }

    private function base64FileData(string $storedPath): string
    {
        $absolutePath = storage_path('app/private/' . $storedPath);
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el archivo del currículum.');
        }

        $mimeType = File::mimeType($absolutePath) ?: 'application/octet-stream';

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private function ensureConfigured(): void
    {
        if (! filled(config('openai.api_key'))) {
            throw new RuntimeException('Falta OPENAI_API_KEY en la configuración.');
        }
    }
}
