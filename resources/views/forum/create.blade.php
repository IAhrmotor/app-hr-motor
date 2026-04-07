@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-6">
        <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
            <div class="border-b border-brand-secondary/10 bg-brand-secondary px-6 py-8 text-white sm:px-8">
                <a href="{{ route('forum.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 transition hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Volver al foro
                </a>

                <div class="mt-5 max-w-3xl">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-white/90">
                        Nuevo hilo
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Abre una nueva duda</h1>
                    <p class="mt-4 text-sm leading-6 text-white/80 sm:text-base">
                        Describe bien el problema, añade contexto útil y adjunta capturas si ayudan a entenderlo mejor. Así será más fácil que otro compañero te eche una mano rápido.
                    </p>
                </div>
            </div>

            <div class="px-6 py-6 sm:px-8">
                <form method="POST" action="{{ route('forum.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
                        <label for="title" class="mb-2 block text-sm font-semibold text-brand-secondary">Título</label>
                        <input id="title" type="text" name="title" value="{{ old('title') }}"
                            placeholder="Ej. Error al cerrar una oportunidad en Salesforce"
                            class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        @error('title')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
                        <label for="content" class="mb-2 block text-sm font-semibold text-brand-secondary">Descripción del problema</label>
                        <textarea id="content" name="content" rows="8"
                            placeholder="Explica qué estabas intentando hacer, dónde te bloqueas, qué mensaje aparece y qué has probado ya."
                            class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
                        <label for="attachments" class="mb-2 block text-sm font-semibold text-brand-secondary">Imágenes adjuntas</label>
                        <input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp"
                            class="block w-full cursor-pointer rounded-2xl border border-dashed border-brand-secondary/20 bg-white px-4 py-4 text-sm text-brand-secondary/75 file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:font-semibold file:text-white hover:file:opacity-90">
                        <p class="mt-2 text-sm text-brand-secondary/60">Puedes adjuntar hasta 4 imágenes en JPG, PNG o WEBP.</p>
                        @error('attachments')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('attachments.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('forum.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/15 px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Cancelar
                        </a>
                        <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                            Publicar hilo
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
