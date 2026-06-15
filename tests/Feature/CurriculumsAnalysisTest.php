<?php

namespace Tests\Feature;

use App\Jobs\ProcessCurriculumAnalysisJob;
use App\Models\CurriculumAnalysis;
use App\Models\CurriculumAnalysisDocument;
use App\Models\User;
use App\Services\OpenAiCurriculumAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CurriculumsAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['openai.api_key' => 'test-key']);
    }

    public function test_hr_user_can_create_a_curriculum_analysis_and_queue_the_job(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh-intranet@example.com',
        ]);

        $cvOne = UploadedFile::fake()->create('candidato-1.pdf', 120, 'application/pdf');
        $cvTwo = UploadedFile::fake()->create('candidato-2.pdf', 160, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('curriculums.store'), [
                'title' => 'Proceso comercial Madrid junio 2026',
                'job_title' => 'Asesor comercial',
                'location' => 'Madrid',
                'offer_description' => 'Buscamos una persona con experiencia en venta consultiva y atención al cliente.',
                'mandatory_requirements' => "Experiencia previa en ventas\nDisponibilidad presencial",
                'valuable_requirements' => "Sector automoción\nCarnet B",
                'top_candidates_count' => 5,
                'cv_files' => [$cvOne, $cvTwo],
            ]);

        $analysis = CurriculumAnalysis::query()->firstOrFail();

        $response
            ->assertRedirect(route('curriculums.show', $analysis))
            ->assertSessionHas('curriculum_analysis_created');

        $this->assertDatabaseHas('curriculum_analyses', [
            'id' => $analysis->id,
            'title' => 'Proceso comercial Madrid junio 2026',
            'status' => 'queued',
            'total_candidates' => 2,
        ]);

        $this->assertDatabaseCount('curriculum_analysis_documents', 2);

        Queue::assertPushed(ProcessCurriculumAnalysisJob::class, function (ProcessCurriculumAnalysisJob $job) use ($analysis): bool {
            return $job->analysisId === $analysis->id;
        });
    }

    public function test_hr_user_can_upload_up_to_twenty_cv_files(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh-20@example.com',
        ]);

        $cvFiles = [];

        for ($index = 1; $index <= 20; $index++) {
            $cvFiles[] = UploadedFile::fake()->create("candidato-{$index}.pdf", 120, 'application/pdf');
        }

        $response = $this->actingAs($user)
            ->post(route('curriculums.store'), [
                'title' => 'Proceso 20 CVs',
                'job_title' => 'Asesor comercial',
                'location' => 'Madrid',
                'offer_description' => 'Oferta de prueba para validar el límite máximo de adjuntos.',
                'mandatory_requirements' => "Experiencia previa en ventas\nDisponibilidad presencial",
                'valuable_requirements' => "Automoción",
                'top_candidates_count' => 5,
                'cv_files' => $cvFiles,
            ]);

        $analysis = CurriculumAnalysis::query()->firstOrFail();

        $response
            ->assertRedirect(route('curriculums.show', $analysis))
            ->assertSessionHas('curriculum_analysis_created');

        $this->assertDatabaseHas('curriculum_analyses', [
            'id' => $analysis->id,
            'total_candidates' => 20,
            'status' => 'queued',
        ]);

        $this->assertDatabaseCount('curriculum_analysis_documents', 20);

        Queue::assertPushed(ProcessCurriculumAnalysisJob::class, function (ProcessCurriculumAnalysisJob $job) use ($analysis): bool {
            return $job->analysisId === $analysis->id;
        });
    }

    public function test_processed_report_keeps_all_twenty_candidates_and_fills_missing_risks_and_questions(): void
    {
        $analysis = CurriculumAnalysis::query()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Proceso de 20 candidatos',
            'job_title' => 'Asesor comercial',
            'location' => 'Madrid',
            'offer_description' => 'Oferta con foco en ventas, atencion al cliente y seguimiento comercial.',
            'mandatory_requirements' => ['Experiencia en ventas'],
            'valuable_requirements' => ['Automocion'],
            'top_candidates_count' => 5,
            'status' => 'queued',
            'total_candidates' => 20,
            'processed_candidates' => 0,
            'openai_model' => 'gpt-5.5',
        ]);

        for ($index = 1; $index <= 20; $index++) {
            $documentPath = 'curriculums/' . $analysis->id . '/candidato-' . $index . '.pdf';
            $absolutePath = storage_path('app/private/' . $documentPath);
            File::ensureDirectoryExists(dirname($absolutePath));
            File::put($absolutePath, 'Contenido CV ' . $index);

            CurriculumAnalysisDocument::query()->create([
                'curriculum_analysis_id' => $analysis->id,
                'original_name' => 'candidato-' . $index . '.pdf',
                'stored_path' => $documentPath,
                'mime_type' => 'application/pdf',
                'file_size' => 2048,
                'order_index' => $index - 1,
                'status' => 'queued',
            ]);
        }

        $responseSequence = Http::sequence();

        for ($index = 1; $index <= 20; $index++) {
            $responseSequence->push($this->chatCompletionResponse([
                'candidate_name' => "Candidato {$index}",
                'score' => 100 - $index,
                'fit_level' => 'alto',
                'summary' => "Perfil {$index} con encaje correcto.",
                'strengths' => ['Experiencia relevante'],
                'risks' => [],
                'doubts' => [],
                'recommended_interview_questions' => [],
                'recommended_next_step' => 'Entrevista.',
                'evidence' => ['Evidencia del CV'],
            ]), 200);
        }

        $responseSequence->push($this->chatCompletionResponse([
            'overall_summary' => 'Resumen global.',
            'top_candidates' => [
                [
                    'candidate_name' => 'Candidato 1',
                    'score' => 99,
                    'reason' => 'Encaje alto.',
                ],
            ],
            'full_ranking' => [
                [
                    'rank' => 1,
                    'candidate_name' => 'Candidato 1',
                    'score' => 99,
                    'summary' => 'Encaje alto.',
                ],
            ],
            'common_risks' => ['Riesgo general'],
            'recommended_interview_questions' => ['Pregunta general'],
            'recommended_next_steps' => ['Paso siguiente'],
        ]), 200);

        Http::fake([
            '*api.openai.com/v1/chat/completions*' => $responseSequence,
        ]);

        $job = new ProcessCurriculumAnalysisJob($analysis->id);
        $job->handle(app(OpenAiCurriculumAnalysisService::class));

        $analysis->refresh();

        $this->assertSame('completed', $analysis->status);
        $this->assertCount(20, $analysis->report_data['full_ranking']);
        $this->assertCount(5, $analysis->report_data['top_candidates']);
        $this->assertStringContainsString('20 candidatos', $analysis->report_data['overall_summary']);

        foreach ($analysis->report_data['top_candidates'] as $candidate) {
            $this->assertNotEmpty($candidate['risks']);
            $this->assertNotEmpty($candidate['recommended_interview_questions']);
        }

        $this->assertNotEmpty($analysis->report_data['full_ranking'][0]['risks']);
        $this->assertNotEmpty($analysis->report_data['full_ranking'][0]['recommended_interview_questions']);
    }

    public function test_processed_report_uses_local_fallback_when_final_openai_call_fails(): void
    {
        $analysis = CurriculumAnalysis::query()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Fallback local',
            'job_title' => 'Asesor comercial',
            'location' => 'Madrid',
            'offer_description' => 'Oferta con foco en ventas y atencion al cliente.',
            'mandatory_requirements' => ['Experiencia en ventas'],
            'valuable_requirements' => ['Automocion'],
            'top_candidates_count' => 5,
            'status' => 'queued',
            'total_candidates' => 3,
            'processed_candidates' => 0,
            'openai_model' => 'gpt-5.5',
        ]);

        for ($index = 1; $index <= 3; $index++) {
            $documentPath = 'curriculums/' . $analysis->id . '/candidato-' . $index . '.pdf';
            $absolutePath = storage_path('app/private/' . $documentPath);
            File::ensureDirectoryExists(dirname($absolutePath));
            File::put($absolutePath, 'Contenido CV ' . $index);

            CurriculumAnalysisDocument::query()->create([
                'curriculum_analysis_id' => $analysis->id,
                'original_name' => 'candidato-' . $index . '.pdf',
                'stored_path' => $documentPath,
                'mime_type' => 'application/pdf',
                'file_size' => 2048,
                'order_index' => $index - 1,
                'status' => 'queued',
            ]);
        }

        $service = $this->mock(OpenAiCurriculumAnalysisService::class, function ($mock): void {
            $mock->shouldReceive('analyzeDocument')->times(3)->andReturnUsing(function ($analysis, $document): array {
                $baseName = pathinfo($document->original_name, PATHINFO_FILENAME);

                return [
                    'candidate_name' => $baseName,
                    'score' => 70,
                    'fit_level' => 'medio',
                    'summary' => 'Perfil válido.',
                    'strengths' => ['Experiencia relevante'],
                    'risks' => [],
                    'doubts' => [],
                    'recommended_interview_questions' => [],
                    'recommended_next_step' => 'Entrevista.',
                    'evidence' => ['CV'],
                ];
            });

            $mock->shouldReceive('generateReport')->once()->andThrow(new \RuntimeException('final timeout'));
        });

        $job = new ProcessCurriculumAnalysisJob($analysis->id);
        $job->handle($service);

        $analysis->refresh();

        $this->assertSame('completed', $analysis->status);
        $this->assertCount(3, $analysis->report_data['full_ranking']);
        $this->assertCount(3, $analysis->report_data['top_candidates']);
        $this->assertStringContainsString('3 candidatos', $analysis->report_data['overall_summary']);
        $this->assertNotEmpty($analysis->report_data['common_risks']);
        $this->assertNotEmpty($analysis->report_data['recommended_interview_questions']);
        $this->assertNotEmpty($analysis->report_data['recommended_next_steps']);
    }

    public function test_hr_user_cannot_upload_more_than_twenty_cv_files(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh-21@example.com',
        ]);

        $cvFiles = [];

        for ($index = 1; $index <= 21; $index++) {
            $cvFiles[] = UploadedFile::fake()->create("candidato-{$index}.pdf", 120, 'application/pdf');
        }

        $this->actingAs($user)
            ->from(route('curriculums.index'))
            ->post(route('curriculums.store'), [
                'title' => 'Proceso 21 CVs',
                'job_title' => 'Asesor comercial',
                'location' => 'Madrid',
                'offer_description' => 'Oferta de prueba para validar el tope máximo de 20 adjuntos.',
                'mandatory_requirements' => "Experiencia previa en ventas\nDisponibilidad presencial",
                'valuable_requirements' => "Automoción",
                'top_candidates_count' => 5,
                'cv_files' => $cvFiles,
            ])
            ->assertSessionHasErrors(['cv_files']);

        $this->assertDatabaseCount('curriculum_analyses', 0);
        Queue::assertNothingPushed();
    }

    public function test_process_curriculum_analysis_job_generates_a_final_report(): void
    {
        $analysis = CurriculumAnalysis::query()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Proceso de ejemplo',
            'job_title' => 'Asesor comercial',
            'location' => 'Madrid',
            'offer_description' => 'Oferta con foco en ventas y atención al cliente.',
            'mandatory_requirements' => ['Experiencia en ventas'],
            'valuable_requirements' => ['Automoción'],
            'top_candidates_count' => 3,
            'status' => 'queued',
            'total_candidates' => 2,
            'processed_candidates' => 0,
            'openai_model' => 'gpt-5.5',
        ]);

        $documents = [
            [
                'original_name' => 'ana-gomez.pdf',
                'stored_path' => 'curriculums/' . $analysis->id . '/ana-gomez.pdf',
            ],
            [
                'original_name' => 'luis-perez.pdf',
                'stored_path' => 'curriculums/' . $analysis->id . '/luis-perez.pdf',
            ],
        ];

        foreach ($documents as $index => $documentData) {
            $absolutePath = storage_path('app/private/' . $documentData['stored_path']);
            File::ensureDirectoryExists(dirname($absolutePath));
            File::put($absolutePath, 'Contenido de CV ' . ($index + 1));

            CurriculumAnalysisDocument::query()->create([
                'curriculum_analysis_id' => $analysis->id,
                'original_name' => $documentData['original_name'],
                'stored_path' => $documentData['stored_path'],
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'order_index' => $index,
                'status' => 'queued',
            ]);
        }

        Http::fake([
            '*api.openai.com/v1/chat/completions*' => Http::sequence()
                ->push($this->chatCompletionResponse([
                    'candidate_name' => 'Ana Gómez',
                    'score' => 91,
                    'fit_level' => 'excelente',
                    'summary' => 'Encaje muy alto.',
                    'strengths' => ['Ventas consultivas', 'Experiencia en el sector'],
                    'risks' => ['No aparece experiencia específica en HR Motor'],
                    'doubts' => ['Falta detalle sobre disponibilidad'],
                    'recommended_interview_questions' => ['¿Cómo gestionas objeciones?'],
                    'recommended_next_step' => 'Pasar a entrevista.',
                    'evidence' => ['Ha liderado ventas con cierre recurrente'],
                ]), 200)
                ->push($this->chatCompletionResponse([
                    'candidate_name' => 'Luis Pérez',
                    'score' => 68,
                    'fit_level' => 'medio',
                    'summary' => 'Encaje correcto, con dudas en experiencia comercial.',
                    'strengths' => ['Buena orientación al cliente'],
                    'risks' => ['Menor experiencia en ventas directas'],
                    'doubts' => ['No detalla resultados medibles'],
                    'recommended_interview_questions' => ['¿Qué cierre de ventas has logrado?'],
                    'recommended_next_step' => 'Entrevista telefónica breve.',
                    'evidence' => ['Ha trabajado en atención al cliente'],
                ]), 200)
                ->push($this->chatCompletionResponse([
                    'overall_summary' => 'Ana es la mejor candidata del proceso.',
                    'top_candidates' => [
                        [
                            'candidate_name' => 'Ana Gómez',
                            'score' => 91,
                            'reason' => 'Es la que mejor combina experiencia y encaje.',
                        ],
                    ],
                    'full_ranking' => [
                        [
                            'rank' => 1,
                            'candidate_name' => 'Ana Gómez',
                            'score' => 91,
                            'summary' => 'Encaje muy alto.',
                        ],
                        [
                            'rank' => 2,
                            'candidate_name' => 'Luis Pérez',
                            'score' => 68,
                            'summary' => 'Encaje medio.',
                        ],
                    ],
                    'common_risks' => ['Poca evidencia cuantitativa en algunos CVs'],
                    'recommended_interview_questions' => ['¿Qué objetivos comerciales has superado?'],
                    'recommended_next_steps' => ['Entrevistar a Ana primero', 'Mantener a Luis como reserva'],
                ]), 200),
        ]);

        $job = new ProcessCurriculumAnalysisJob($analysis->id);
        $job->handle(app(OpenAiCurriculumAnalysisService::class));

        $analysis->refresh()->load('documents');

        $this->assertSame('completed', $analysis->status);
        $this->assertNotNull($analysis->report_data);
        $this->assertStringContainsString('2 candidatos', $analysis->overall_summary);
        $this->assertStringContainsString('Ana Gómez', $analysis->overall_summary);
        $this->assertSame('Ana Gómez', $analysis->report_data['top_candidates'][0]['candidate_name']);
        $this->assertSame(['No aparece experiencia específica en HR Motor'], $analysis->report_data['top_candidates'][0]['risks']);
        $this->assertSame(['¿Cómo gestionas objeciones?'], $analysis->report_data['top_candidates'][0]['recommended_interview_questions']);
        $this->assertSame(['Menor experiencia en ventas directas'], $analysis->report_data['full_ranking'][1]['risks']);
        $this->assertSame(['¿Qué cierre de ventas has logrado?'], $analysis->report_data['full_ranking'][1]['recommended_interview_questions']);
        $this->assertSame(2, $analysis->processed_candidates);
        $this->assertSame(2, $analysis->documents->where('status', 'completed')->count());
    }

    public function test_curriculum_status_endpoint_returns_progress_and_labels(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh-status@example.com',
        ]);

        $analysis = CurriculumAnalysis::query()->create([
            'user_id' => $user->id,
            'title' => 'Estado en vivo',
            'job_title' => 'RRHH',
            'location' => 'Madrid',
            'offer_description' => 'Oferta',
            'mandatory_requirements' => ['Uno'],
            'valuable_requirements' => ['Dos'],
            'top_candidates_count' => 5,
            'status' => 'processing',
            'total_candidates' => 4,
            'processed_candidates' => 2,
            'openai_model' => 'gpt-4o-mini',
        ]);

        CurriculumAnalysisDocument::query()->create([
            'curriculum_analysis_id' => $analysis->id,
            'original_name' => 'candidato.pdf',
            'stored_path' => 'curriculums/' . $analysis->id . '/candidato.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'order_index' => 0,
            'status' => 'processing',
        ]);

        $this->actingAs($user)
            ->getJson(route('curriculums.status', $analysis))
            ->assertOk()
            ->assertJsonPath('status', 'processing')
            ->assertJsonPath('status_label', 'Procesando')
            ->assertJsonPath('progress', 45)
            ->assertJsonPath('processed_candidates', 2)
            ->assertJsonPath('total_candidates', 4)
            ->assertJsonCount(1, 'documents');
    }

    public function test_hr_user_can_delete_a_curriculum_analysis(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'extra_role' => User::ROLE_HUMAN_RESOURCES,
            'email' => 'rrhh-delete@example.com',
        ]);

        $analysis = CurriculumAnalysis::query()->create([
            'user_id' => $user->id,
            'title' => 'Borrado',
            'job_title' => 'RRHH',
            'location' => 'Madrid',
            'offer_description' => 'Oferta',
            'mandatory_requirements' => ['Uno'],
            'valuable_requirements' => ['Dos'],
            'top_candidates_count' => 5,
            'status' => 'completed',
            'total_candidates' => 1,
            'processed_candidates' => 1,
            'openai_model' => 'gpt-4o-mini',
        ]);

        $documentPath = 'curriculums/' . $analysis->id . '/candidato.pdf';
        $absolutePath = storage_path('app/private/' . $documentPath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'CV');

        CurriculumAnalysisDocument::query()->create([
            'curriculum_analysis_id' => $analysis->id,
            'original_name' => 'candidato.pdf',
            'stored_path' => $documentPath,
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'order_index' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->delete(route('curriculums.destroy', $analysis))
            ->assertRedirect(route('curriculums.index'))
            ->assertSessionHas('curriculum_analysis_deleted');

        $this->assertDatabaseMissing('curriculum_analyses', [
            'id' => $analysis->id,
        ]);

        $this->assertDatabaseMissing('curriculum_analysis_documents', [
            'curriculum_analysis_id' => $analysis->id,
        ]);

        $this->assertFileDoesNotExist($absolutePath);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function chatCompletionResponse(array $payload): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ],
        ];
    }
}
