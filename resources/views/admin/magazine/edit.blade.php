@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">
                    Revista mensual
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-brand-secondary md:text-3xl">
                    Gestión de la revista mensual
                </h1>

                <p class="mt-2 text-sm text-brand-secondary/70">
                    Sube la edición del nuevo mes, define la etiqueta visible y el nombre base del archivo. La portada se actualizará al instante.
                </p>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                                    Estado actual
                                </span>
                                <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">
                                    {{ $magazine->tag_label }}
                                </h2>
                            </div>

                            <div class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                Activa
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 text-sm text-brand-secondary/75">
                            <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/40">Archivo</span>
                                <span class="mt-1 block break-all font-medium text-brand-secondary">{{ basename($magazine->pdf_path ?: \App\Models\MonthlyMagazineSetting::DEFAULT_PDF_PATH) }}</span>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/40">Ruta pública</span>
                                <span class="mt-1 block break-all font-medium text-brand-secondary">{{ $magazine->pdf_path ?: \App\Models\MonthlyMagazineSetting::DEFAULT_PDF_PATH }}</span>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/40">Última actualización</span>
                                <span class="mt-1 block font-medium text-brand-secondary">
                                    {{ $magazine->updated_at?->format('d/m/Y H:i') ?? 'Pendiente de publicar' }}
                                </span>
                                @if ($magazine->updatedBy)
                                    <span class="mt-1 block text-xs text-brand-secondary/55">
                                        Por {{ $magazine->updatedBy->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white">
                            <div class="flex items-center justify-between border-b border-brand-secondary/10 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-brand-secondary">Vista previa</p>
                                    <p class="text-xs text-brand-secondary/60">Se actualizará automáticamente al elegir un PDF nuevo.</p>
                                </div>
                                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                    PDF
                                </span>
                            </div>

                            <iframe
                                id="magazine-preview-frame"
                                src="{{ $magazine->pdf_url }}"
                                class="h-[32rem] w-full"
                                frameborder="0"
                                allowfullscreen
                                data-fallback-src="{{ $magazine->pdf_url }}"
                            ></iframe>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ $magazine->pdf_url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                Abrir PDF actual
                            </a>
                            <a href="{{ route('home') }}" target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                Ver portada
                            </a>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
                    <div class="max-w-2xl">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                            Nueva edición
                        </span>

                        <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">
                            Sube la revista del mes
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-brand-secondary/70">
                            Sube un PDF nuevo y escribe la etiqueta que quieres mostrar en la portada. Como ejemplo, puedes usar Abril, Mayo o el mes que toque.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.magazine.update') }}" enctype="multipart/form-data" class="mt-8 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="tag_label" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                                Etiqueta visible
                            </label>
                            <input
                                id="tag_label"
                                name="tag_label"
                                type="text"
                                value="{{ old('tag_label', $magazine->tag_label) }}"
                                placeholder="Abril"
                                required
                                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                            >
                            <p class="mt-2 pl-2 text-xs text-brand-secondary/60">
                                Pon aquí el texto que quieres ver en la portada. Si quieres seguir usando un mes de ejemplo, escribe Abril, Mayo o el que toque.
                            </p>
                        </div>

                        <div>
                            <label for="file_name" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                                Nombre del archivo
                            </label>
                            <input
                                id="file_name"
                                name="file_name"
                                type="text"
                                value="{{ old('file_name', 'revista ' . $magazine->tag_label . ' 2026') }}"
                                placeholder="revista abril 2026"
                                required
                                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                            >
                            <p class="mt-2 pl-2 text-xs text-brand-secondary/60">
                                Se convertirá automáticamente en formato limpio: por ejemplo, <span class="font-semibold">revista abril 2026</span> pasará a <span class="font-semibold">revista-abril-2026.pdf</span>.
                            </p>
                        </div>

                        <div>
                            <label for="magazine_file" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                                PDF de la revista
                            </label>
                            <input
                                id="magazine_file"
                                name="magazine_file"
                                type="file"
                                accept="application/pdf"
                                required
                                class="block w-full cursor-pointer rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-brand-primary focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                            >
                            <p class="mt-2 pl-2 text-xs text-brand-secondary/60">
                                Solo se admiten PDFs. Si subes uno con el mismo nombre base, se reemplazará el anterior.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-brand-primary/15 bg-brand-primary/5 px-4 py-4 text-sm text-brand-secondary/80">
                            La edición actual se actualizará en la portada del portal en cuanto guardes los cambios.
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <a href="{{ route('admin.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                Volver
                            </a>
                            <button type="submit" class="cursor-pointer rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                Publicar nueva revista
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const fileInput = document.getElementById('magazine_file');
            const previewFrame = document.getElementById('magazine-preview-frame');
            const fallbackSrc = previewFrame?.dataset?.fallbackSrc;
            let currentObjectUrl = null;

            if (!fileInput || !previewFrame) {
                return;
            }

            fileInput.addEventListener('change', () => {
                const file = fileInput.files?.[0];

                if (!file) {
                    if (currentObjectUrl) {
                        URL.revokeObjectURL(currentObjectUrl);
                        currentObjectUrl = null;
                    }

                    if (fallbackSrc) {
                        previewFrame.src = fallbackSrc;
                    }

                    return;
                }

                if (currentObjectUrl) {
                    URL.revokeObjectURL(currentObjectUrl);
                }

                currentObjectUrl = URL.createObjectURL(file);
                previewFrame.src = currentObjectUrl;
            });
        })();
    </script>
@endsection
