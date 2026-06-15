@extends('layouts.app')

@section('content')
    <div class="relative overflow-hidden bg-slate-50">
        <section
            class="relative isolate overflow-hidden bg-slate-900"
            style="background-image: linear-gradient(180deg, rgba(3, 7, 18, 0.72) 0%, rgba(3, 7, 18, 0.68) 100%), url('{{ asset('images/hero/hero-curriculums.jpg') }}'); background-size: cover; background-position: center bottom;"
        >
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="relative mx-auto max-w-7xl px-6 py-10 lg:px-8 lg:py-14">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/85 backdrop-blur">
                        Recursos Humanos
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl xl:text-5xl">
                        Currículums HR Motor
                    </h1>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-white/80 md:text-base">
                        Sube la oferta, adjunta los CVs y recibe un informe con los candidatos mejor encajados, sus dudas y las preguntas que conviene hacer en entrevista.
                    </p>
                </div>
            </div>
        </section>

        <main class="relative mx-auto -mt-6 max-w-7xl px-6 pb-14 lg:px-8">
            @if (session('curriculum_analysis_created'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    El análisis se ha puesto en cola. En unos minutos deberías ver el informe completo.
                </div>
            @endif

            @if (session('curriculum_analysis_deleted'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    El análisis se ha eliminado correctamente.
                </div>
            @endif

            <section class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <article class="rounded-[1.5rem] border border-white/70 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-primary">Nuevo análisis</p>
                            <h2 class="mt-1 text-xl font-semibold tracking-tight text-brand-secondary md:text-2xl">
                                Análisis IA de candidatos
                            </h2>
                        </div>
                        <p class="max-w-xl text-sm leading-6 text-slate-500">
                            Recomendación MVP: entre 3 y 10 CVs por proceso para revisarlos con calma, aunque puedes subir hasta 20 por análisis.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('curriculums.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-5">
                        @csrf

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="title" class="mb-2 block text-sm font-semibold text-brand-secondary">Nombre del proceso</label>
                                <input id="title" name="title" type="text" value="{{ old('title') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white"
                                    placeholder="Ej. Comercial Madrid junio 2026">
                                @error('title')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="job_title" class="mb-2 block text-sm font-semibold text-brand-secondary">Puesto/oferta</label>
                                <input id="job_title" name="job_title" type="text" value="{{ old('job_title') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white"
                                    placeholder="Ej. Asesor comercial de vehículos">
                                @error('job_title')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="location" class="mb-2 block text-sm font-semibold text-brand-secondary">Ubicación</label>
                                <input id="location" name="location" type="text" value="{{ old('location') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white"
                                    placeholder="Ej. Madrid">
                                @error('location')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="top_candidates_count" class="mb-2 block text-sm font-semibold text-brand-secondary">Top a devolver</label>
                                <select id="top_candidates_count" name="top_candidates_count"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white">
                                    @foreach ([3, 5, 10] as $option)
                                        <option value="{{ $option }}" @selected((int) old('top_candidates_count', 5) === $option)>
                                            Top {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('top_candidates_count')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="offer_description" class="mb-2 block text-sm font-semibold text-brand-secondary">Descripción de la oferta</label>
                            <textarea id="offer_description" name="offer_description" rows="6"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white"
                                placeholder="Pega aquí la descripción completa de la oferta">{{ old('offer_description') }}</textarea>
                            @error('offer_description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="mandatory_requirements" class="mb-2 block text-sm font-semibold text-brand-secondary">Requisitos imprescindibles</label>
                                <textarea id="mandatory_requirements" name="mandatory_requirements" rows="6"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white"
                                    placeholder="Un requisito por línea">{{ old('mandatory_requirements') }}</textarea>
                                @error('mandatory_requirements')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="valuable_requirements" class="mb-2 block text-sm font-semibold text-brand-secondary">Requisitos valorables</label>
                                <textarea id="valuable_requirements" name="valuable_requirements" rows="6"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white"
                                    placeholder="Un requisito por línea">{{ old('valuable_requirements') }}</textarea>
                                @error('valuable_requirements')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="cv_files" class="mb-2 block text-sm font-semibold text-brand-secondary">Adjuntar CVs</label>
                            <input id="cv_files" name="cv_files[]" type="file" multiple
                                accept=".pdf,.doc,.docx,.txt,.rtf,.csv,.xls,.xlsx,.ppt,.pptx"
                                class="block w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-brand-secondary file:mr-4 file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-primary/90">
                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Formatos recomendados: PDF o DOCX. Límite MVP: 20 archivos por análisis.
                            </p>
                            @error('cv_files')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('cv_files.*')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-slate-500">
                                La IA analizará cada CV uno a uno y luego generará el ranking final.
                            </p>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-full bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-primary/90">
                                Lanzar análisis
                            </button>
                        </div>
                    </form>
                </article>

                <aside class="space-y-6">
                    <section class="rounded-[1.5rem] border border-brand-secondary/10 bg-brand-secondary p-5 text-white shadow-[0_20px_50px_rgba(15,23,42,0.12)] lg:p-6">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/60">Flujo MVP</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-white">
                            Qué hace el sistema
                        </h2>
                        <ol class="mt-4 space-y-3 text-sm leading-6 text-white/80">
                            <li>1. RRHH pega la oferta y sube varios CVs.</li>
                            <li>2. El sistema analiza cada documento por separado.</li>
                            <li>3. OpenAI devuelve un análisis estructurado por candidato.</li>
                            <li>4. Se genera el ranking y el informe final.</li>
                        </ol>
                    </section>

                    <section class="rounded-[1.5rem] border border-white/70 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] lg:p-6">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-primary">Histórico</p>
                                <h2 class="mt-1 text-xl font-semibold tracking-tight text-brand-secondary">
                                    Últimos análisis
                                </h2>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($analyses as $analysis)
                                <a href="{{ route('curriculums.show', $analysis) }}"
                                    class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-brand-primary/30 hover:bg-white">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-brand-secondary">{{ $analysis->title }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $analysis->job_title }}</p>
                                        </div>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide
                                            {{ $analysis->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($analysis->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                            {{ $analysis->status_label }}
                                        </span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                                        <span>{{ $analysis->documents_count }} CVs</span>
                                        <span>{{ optional($analysis->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                                    Todavía no hay análisis guardados.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </section>
        </main>
    </div>
@endsection
