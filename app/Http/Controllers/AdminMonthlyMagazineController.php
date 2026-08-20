<?php

namespace App\Http\Controllers;

use App\Models\MonthlyMagazineActivityLog;
use App\Models\MonthlyMagazineSetting;
use App\Services\MonthlyMagazineActivityLogWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminMonthlyMagazineController extends Controller
{
    public function edit(): View
    {
        $magazine = MonthlyMagazineSetting::current();

        return view('admin.magazine.edit', compact('magazine'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tag_label' => ['required', 'string', 'max:80'],
            'file_name' => ['required', 'string', 'max:120'],
            'magazine_file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:51200',
            ],
        ]);

        $magazine = MonthlyMagazineSetting::query()->first() ?? new MonthlyMagazineSetting();
        $previousTagLabel = $magazine->tag_label ?? MonthlyMagazineSetting::DEFAULT_TAG_LABEL;
        $previousPdfPath = $magazine->pdf_path ?? MonthlyMagazineSetting::DEFAULT_PDF_PATH;
        $previousOriginalFilename = $magazine->original_filename;
        $file = $request->file('magazine_file');
        $directory = public_path('revista');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = 'pdf';
        $customFileName = Str::slug($validated['file_name']) ?: 'revista';
        $filename = $customFileName . '.' . $extension;

        if (File::exists($directory . DIRECTORY_SEPARATOR . $filename)) {
            File::delete($directory . DIRECTORY_SEPARATOR . $filename);
        }

        $file->move($directory, $filename);

        $isCreate = ! $magazine->exists;
        $targetPath = 'revista/' . $filename;
        $targetAbsolutePath = public_path($targetPath);

        DB::transaction(function () use ($magazine, $validated, $filename, $file, $previousTagLabel, $previousPdfPath, $previousOriginalFilename, $isCreate, $targetPath): void {
            $magazine->fill([
                'tag_label' => $validated['tag_label'],
                'pdf_path' => $targetPath,
                'original_filename' => $file->getClientOriginalName(),
                'updated_by_user_id' => request()->user()?->id,
            ]);

            $magazine->save();

            app(MonthlyMagazineActivityLogWriter::class)->record(
                actor: request()->user(),
                action: $isCreate ? MonthlyMagazineActivityLog::ACTION_CREATED : MonthlyMagazineActivityLog::ACTION_UPDATED,
                targetName: $validated['tag_label'],
                targetReference: $targetPath,
                changes: [
                    'tag_label' => ['from' => $previousTagLabel, 'to' => $validated['tag_label']],
                    'pdf_path' => ['from' => $previousPdfPath, 'to' => $targetPath],
                    'original_filename' => ['from' => $previousOriginalFilename, 'to' => $file->getClientOriginalName()],
                ],
            );
        });

        if (
            ($previousPdfPath !== MonthlyMagazineSetting::DEFAULT_PDF_PATH) &&
            ($previousPdfPath !== $targetPath) &&
            File::exists(public_path($previousPdfPath))
        ) {
            File::delete(public_path($previousPdfPath));
        }

        return redirect()
            ->route('admin.magazine.edit')
            ->with('success', 'La revista mensual se ha actualizado correctamente.');
    }
}
