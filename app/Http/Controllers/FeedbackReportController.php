<?php

namespace App\Http\Controllers;

use App\Mail\FeedbackReportMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeedbackReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('feedbackReport', [
            'type' => ['required', 'in:bug,suggestion'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'page_url' => ['required', 'string', 'max:2048'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'screenshots' => ['nullable', 'array', 'max:3'],
            'screenshots.*' => ['file', 'image', 'max:5120'],
        ]);

        $authUser = $request->user();
        $screenshots = collect($validated['screenshots'] ?? [])
            ->map(fn (UploadedFile $file) => $file)
            ->values()
            ->all();

        try {
            Mail::to(config('support.feedback_recipient'))
                ->send(new FeedbackReportMail(
                    reportType: $validated['type'],
                    title: $validated['title'],
                    description: $validated['description'],
                    reporterName: $authUser?->name ?? 'Usuario de la plataforma',
                    reporterEmail: $authUser?->email ?? config('mail.from.address'),
                    pageUrl: $validated['page_url'],
                    pageTitle: $validated['page_title'] ?? null,
                    screenshots: $screenshots,
                ));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('feedback_report_error', 'No se ha podido enviar el reporte ahora mismo. Inténtalo de nuevo en unos minutos.');
        }

        return back()
            ->with('feedback_report_success', 'Gracias. Tu reporte se ha enviado correctamente.');
    }
}
