<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCurriculumAnalysisJob;
use App\Models\CurriculumAnalysis;
use App\Models\CurriculumAnalysisDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CurriculumsController extends Controller
{
    public function index(): View
    {
        abort_unless(app_can_access_curriculums(), 403);

        $analyses = CurriculumAnalysis::query()
            ->withCount('documents')
            ->latest()
            ->limit(6)
            ->get();

        return view('curriculums.index', compact('analyses'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(app_can_access_curriculums(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'job_title' => ['required', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:120'],
            'offer_description' => ['required', 'string', 'max:10000'],
            'mandatory_requirements' => ['required', 'string', 'max:6000'],
            'valuable_requirements' => ['nullable', 'string', 'max:6000'],
            'top_candidates_count' => ['required', 'integer', 'in:3,5,10'],
            'cv_files' => ['required', 'array', 'min:1', 'max:20'],
            'cv_files.*' => ['file', 'mimes:pdf,doc,docx,txt,rtf,csv,xls,xlsx,ppt,pptx', 'max:10240'],
        ]);

        $user = Auth::user();
        $analysis = CurriculumAnalysis::query()->create([
            'user_id' => $user?->id,
            'title' => $validated['title'],
            'job_title' => $validated['job_title'],
            'location' => $validated['location'] ?: null,
            'offer_description' => $validated['offer_description'],
            'mandatory_requirements' => $this->parseRequirements($validated['mandatory_requirements']),
            'valuable_requirements' => $this->parseRequirements($validated['valuable_requirements'] ?? ''),
            'top_candidates_count' => (int) $validated['top_candidates_count'],
            'status' => 'queued',
            'total_candidates' => count($validated['cv_files']),
            'processed_candidates' => 0,
            'openai_model' => config('openai.model', 'gpt-5.5'),
        ]);

        $storageDirectory = 'curriculums/' . $analysis->id;
        $absoluteDirectory = storage_path('app/private/' . $storageDirectory);
        File::ensureDirectoryExists($absoluteDirectory);

        foreach ($validated['cv_files'] as $index => $file) {
            $originalName = $file->getClientOriginalName();
            $storedName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $storedPath = $storageDirectory . '/' . $storedName;
            $file->storeAs($storageDirectory, $storedName, 'local');

            CurriculumAnalysisDocument::query()->create([
                'curriculum_analysis_id' => $analysis->id,
                'original_name' => $originalName,
                'stored_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize() ?: 0,
                'order_index' => $index,
                'status' => 'queued',
            ]);
        }

        ProcessCurriculumAnalysisJob::dispatch($analysis->id);

        return redirect()
            ->route('curriculums.show', $analysis)
            ->with('curriculum_analysis_created', true);
    }

    public function show(CurriculumAnalysis $analysis): View
    {
        abort_unless(app_can_access_curriculums(), 403);

        $analysis->load(['documents' => fn ($query) => $query->orderBy('order_index')]);

        return view('curriculums.show', compact('analysis'));
    }

    public function status(CurriculumAnalysis $analysis): JsonResponse
    {
        abort_unless(app_can_access_curriculums(), 403);

        $analysis->load(['documents' => fn ($query) => $query->orderBy('order_index')]);

        return response()->json([
            'status' => $analysis->status,
            'status_label' => $analysis->status_label,
            'progress' => $analysis->progress,
            'processed_candidates' => $analysis->processed_candidates,
            'total_candidates' => $analysis->total_candidates,
            'has_report' => filled($analysis->report_data),
            'documents' => $analysis->documents->map(function ($document): array {
                return [
                    'id' => $document->id,
                    'status' => $document->status,
                    'status_label' => $document->status_label,
                    'status_tone' => $document->status_tone,
                    'analysis_link' => route('curriculums.show', $document->curriculum_analysis_id),
                ];
            })->values(),
        ]);
    }

    public function destroy(CurriculumAnalysis $analysis): RedirectResponse
    {
        abort_unless(app_can_access_curriculums(), 403);

        $storageDirectory = storage_path('app/private/curriculums/' . $analysis->id);

        if (File::exists($storageDirectory)) {
            File::deleteDirectory($storageDirectory);
        }

        $analysis->delete();

        return redirect()
            ->route('curriculums.index')
            ->with('curriculum_analysis_deleted', true);
    }

    /**
     * @return array<int, string>
     */
    private function parseRequirements(string $requirements): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $requirements) ?: [])
            ->map(fn (string $line): string => trim($line, " \t\n\r\0\x0B-•"))
            ->filter()
            ->values()
            ->all();
    }
}
