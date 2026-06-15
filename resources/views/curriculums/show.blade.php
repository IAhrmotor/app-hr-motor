@extends('layouts.app')

@section('content')
    <div class="relative overflow-hidden bg-slate-50"
        x-data="{ deleteModalOpen: false }"
        x-effect="window.bodyScrollLock?.set('curriculum-delete-modal', deleteModalOpen)"
        @keydown.escape.window="deleteModalOpen = false"
        data-curriculum-status-url="{{ route('curriculums.status', $analysis) }}"
        data-curriculum-terminal-state="{{ in_array($analysis->status, ['completed', 'failed'], true) ? '1' : '0' }}">
        <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-primary">Análisis IA</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $analysis->title }}</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ $analysis->job_title }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('curriculums.index') }}"
                        class="inline-flex cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary/30 hover:text-brand-primary">
                        Volver
                    </a>

                    <button type="button"
                        @click="deleteModalOpen = true"
                        class="inline-flex cursor-pointer items-center justify-center rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-100">
                            Eliminar
                    </button>
                </div>
            </div>

            <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <article class="rounded-[1.5rem] border border-white/70 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-primary">Estado</p>
                            <h2 id="curriculum-status-heading" class="mt-1 text-xl font-semibold text-brand-secondary">
                                {{ $analysis->status === 'completed' ? 'Informe listo' : ($analysis->status === 'failed' ? 'Proceso con incidencias' : 'Procesando') }}
                            </h2>
                        </div>
                        <span id="curriculum-status-badge" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide
                            {{ $analysis->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($analysis->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $analysis->status_label }}
                        </span>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-brand-secondary">Progreso</p>
                            <p id="curriculum-progress-label" class="text-sm font-medium text-slate-500">{{ $analysis->progress }}%</p>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-200">
                            <div id="curriculum-progress-bar"
                                class="h-full rounded-full bg-brand-primary transition-all duration-500 ease-out"
                                style="width: {{ $analysis->progress }}%;"></div>
                        </div>
                        <p id="curriculum-progress-text" class="mt-3 text-xs leading-5 text-slate-500">
                            {{ $analysis->status_label }}. {{ $analysis->processed_candidates }} de {{ $analysis->total_candidates }} CVs procesados.
                        </p>
                    </div>

                    @if ($analysis->report_data)
                        <div class="mt-6 space-y-6">
                            <section>
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Resumen general</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $analysis->overall_summary }}</p>
                            </section>

                            <section>
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Top candidatos</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach (($analysis->report_data['top_candidates'] ?? []) as $candidate)
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-brand-secondary">{{ $candidate['candidate_name'] ?? 'Candidato' }}</p>
                                                    <p class="mt-1 text-sm text-slate-600">{{ $candidate['reason'] ?? '' }}</p>
                                                </div>
                                                <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                                                    {{ $candidate['score'] ?? 0 }}/100
                                                </span>
                                            </div>

                                            <div class="mt-4 grid gap-3">
                                                <div class="rounded-xl bg-white px-3 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-primary">Riesgos</p>
                                                    <ul class="mt-2 space-y-2 text-sm leading-6 text-slate-600">
                                                        @forelse (($candidate['risks'] ?? []) as $risk)
                                                            <li class="flex gap-2">
                                                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-primary"></span>
                                                                <span>{{ $risk }}</span>
                                                            </li>
                                                        @empty
                                                            <li class="text-slate-400">Sin riesgos destacados.</li>
                                                        @endforelse
                                                    </ul>
                                                </div>

                                                <div class="rounded-xl bg-white px-3 py-3">
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-primary">Preguntas sugeridas</p>
                                                    <ul class="mt-2 space-y-2 text-sm leading-6 text-slate-600">
                                                        @forelse (($candidate['recommended_interview_questions'] ?? []) as $question)
                                                            <li class="flex gap-2">
                                                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-primary"></span>
                                                                <span>{{ $question }}</span>
                                                            </li>
                                                        @empty
                                                            <li class="text-slate-400">Sin preguntas sugeridas.</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            @if (! empty($analysis->report_data['full_ranking'] ?? []))
                                <section>
                                    <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-primary">Ranking completo</h3>
                                    <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                                        <div class="grid grid-cols-[4rem_minmax(0,1fr)_5rem] bg-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <span>#</span>
                                            <span>Candidato</span>
                                            <span class="text-right">Nota</span>
                                        </div>
                                        <div class="divide-y divide-slate-200 bg-white">
                                            @foreach ($analysis->report_data['full_ranking'] as $candidate)
                                                <div class="grid grid-cols-[4rem_minmax(0,1fr)_5rem] items-start gap-3 px-4 py-4">
                                                    <span class="text-sm font-semibold text-brand-secondary">{{ $candidate['rank'] ?? '—' }}</span>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-brand-secondary">{{ $candidate['candidate_name'] ?? 'Candidato' }}</p>
                                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $candidate['summary'] ?? '' }}</p>

                                                        @if (! empty($candidate['risks'] ?? []))
                                                            <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-primary">Riesgos</p>
                                                            <ul class="mt-2 space-y-1 text-sm leading-6 text-slate-600">
                                                                @foreach ($candidate['risks'] as $risk)
                                                                    <li class="flex gap-2">
                                                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-primary"></span>
                                                                        <span>{{ $risk }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif

                                                        @if (! empty($candidate['recommended_interview_questions'] ?? []))
                                                            <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-primary">Preguntas sugeridas</p>
                                                            <ul class="mt-2 space-y-1 text-sm leading-6 text-slate-600">
                                                                @foreach ($candidate['recommended_interview_questions'] as $question)
                                                                    <li class="flex gap-2">
                                                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-primary"></span>
                                                                        <span>{{ $question }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                    <span class="text-right text-sm font-semibold text-brand-primary">{{ $candidate['score'] ?? 0 }}/100</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </section>
                            @endif
                        </div>
                    @else
                        <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            El análisis todavía está en curso o no ha generado informe aún.
                        </div>
                    @endif
                </article>

                <aside class="space-y-6">
                    <section class="rounded-[1.5rem] border border-white/70 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                        <h2 class="text-lg font-semibold text-brand-secondary">CVs recibidos</h2>
                        <div id="curriculum-documents-list" class="mt-4 space-y-3">
                            @foreach ($analysis->documents as $document)
                                <div data-curriculum-document-id="{{ $document->id }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-brand-secondary">{{ $document->original_name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ number_format($document->file_size / 1024, 1) }} KB</p>
                                        </div>
                                        <span data-curriculum-document-status class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide
                                            {{ $document->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($document->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                            {{ $document->status_label }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @if ($analysis->error_message)
                        <section class="rounded-[1.5rem] border border-red-200 bg-red-50 p-5 text-red-800 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                            <h2 class="text-lg font-semibold">Incidencia</h2>
                            <p class="mt-2 text-sm leading-7">{{ $analysis->error_message }}</p>
                        </section>
                    @endif
                </aside>
            </section>
        </main>

        <div
            x-show="deleteModalOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/65 p-4"
            @click.self="deleteModalOpen = false"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="curriculum-delete-title"
                class="w-full max-w-lg overflow-hidden rounded-[1.75rem] bg-white shadow-2xl"
            >
                <div class="border-b border-slate-200 bg-brand-secondary px-6 py-5 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">Confirmar eliminación</p>
                    <h2 id="curriculum-delete-title" class="mt-1 text-xl font-semibold">
                        ¿Eliminar análisis?
                    </h2>
                </div>

                <div class="px-6 py-6">
                    <p class="text-sm leading-7 text-slate-600">
                        Vas a eliminar el análisis <span class="font-semibold text-brand-secondary">{{ $analysis->title }}</span>.
                        Se borrarán también sus CVs y no podrás recuperarlo después.
                    </p>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="deleteModalOpen = false"
                        class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <form method="POST" action="{{ route('curriculums.destroy', $analysis) }}" class="inline-flex">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700">
                            Sí, eliminar
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-curriculum-status-url]');

            if (!root) {
                return;
            }

            const statusUrl = root.dataset.curriculumStatusUrl;
            const terminalState = root.dataset.curriculumTerminalState === '1';
            const heading = document.getElementById('curriculum-status-heading');
            const badge = document.getElementById('curriculum-status-badge');
            const progressBar = document.getElementById('curriculum-progress-bar');
            const progressLabel = document.getElementById('curriculum-progress-label');
            const progressText = document.getElementById('curriculum-progress-text');
            const documentsList = document.getElementById('curriculum-documents-list');

            if (!statusUrl || terminalState) {
                return;
            }

            const toneClasses = {
                amber: ['bg-amber-100', 'text-amber-700'],
                emerald: ['bg-emerald-100', 'text-emerald-700'],
                red: ['bg-red-100', 'text-red-700'],
            };

            let reloadScheduled = false;

            const paintStatus = (payload) => {
                const status = payload.status || 'queued';
                const statusLabel = payload.status_label || status;
                const progress = Math.max(0, Math.min(100, Number(payload.progress || 0)));
                const processed = Number(payload.processed_candidates || 0);
                const total = Number(payload.total_candidates || 0);

                if (heading) {
                    heading.textContent = status === 'completed'
                        ? 'Informe listo'
                        : (status === 'failed' ? 'Proceso con incidencias' : 'Procesando');
                }

                if (badge) {
                    badge.textContent = statusLabel;
                    badge.classList.remove('bg-amber-100', 'text-amber-700', 'bg-emerald-100', 'text-emerald-700', 'bg-red-100', 'text-red-700');
                    const tone = status === 'completed' ? 'emerald' : (status === 'failed' ? 'red' : 'amber');
                    badge.classList.add(...toneClasses[tone]);
                }

                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                }

                if (progressLabel) {
                    progressLabel.textContent = `${progress}%`;
                }

                if (progressText) {
                    progressText.textContent = `${statusLabel}. ${processed} de ${total} CVs procesados.`;
                }

                if (documentsList && Array.isArray(payload.documents)) {
                    payload.documents.forEach((document) => {
                        const row = documentsList.querySelector(`[data-curriculum-document-id="${document.id}"]`);
                        const pill = row?.querySelector('[data-curriculum-document-status]');

                        if (!row || !pill) {
                            return;
                        }

                        pill.textContent = document.status_label || document.status || '';
                        pill.classList.remove('bg-amber-100', 'text-amber-700', 'bg-emerald-100', 'text-emerald-700', 'bg-red-100', 'text-red-700');
                        const tone = document.status_tone || (document.status === 'completed' ? 'emerald' : (document.status === 'failed' ? 'red' : 'amber'));
                        pill.classList.add(...toneClasses[tone]);
                    });
                }
            };

            const refreshStatus = async () => {
                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    paintStatus(payload);

                    if ((payload.status === 'completed' || payload.status === 'failed') && !reloadScheduled) {
                        reloadScheduled = true;
                        window.setTimeout(() => window.location.reload(), 1000);
                    }
                } catch (error) {
                    console.error(error);
                }
            };

            void refreshStatus();
            const refreshHandle = window.setInterval(refreshStatus, 3000);

            window.addEventListener('beforeunload', () => {
                window.clearInterval(refreshHandle);
            });
        })();
    </script>
@endsection
