@extends('layouts.app')

@section('content')
    <main class="mx-auto min-h-screen max-w-7xl px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                        Administraci&oacute;n
                    </span>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Tabl&oacute;n</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Crea, edita y elimina anuncios visibles para toda la plantilla. Las publicaciones activas aparecer&aacute;n en el tabl&oacute;n p&uacute;blico.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tablon.index') }}" target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Ver tabl&oacute;n p&uacute;blico
                    </a>
                    <a href="{{ route('admin.tablon.create') }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        Nueva publicaci&oacute;n
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mt-8 grid gap-5">
                @forelse ($posts as $post)
                    @php
                        $isEdited = $post->updated_at && $post->created_at && $post->updated_at->gt($post->created_at);
                    @endphp
                    <article class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm md:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $post->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $post->is_published ? 'Publicado' : 'Borrador' }}
                                    </span>
                                    <span class="text-xs font-medium uppercase tracking-[0.18em] text-brand-secondary/45">
                                        {{ $isEdited ? 'Editado · ' . $post->updated_at?->format('d/m/Y H:i') : ($post->published_at?->format('d/m/Y H:i') ?? 'Sin fecha de publicaci&oacute;n') }}
                                    </span>
                                </div>

                                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-brand-secondary">
                                    {{ $post->title }}
                                </h2>

                                <div class="mt-4 max-w-none text-sm leading-7 text-brand-secondary/75">
                                    {{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish($post->body), 260) }}
                                </div>

                                @if ($post->creator)
                                    <p class="mt-4 text-xs font-medium uppercase tracking-[0.18em] text-brand-secondary/45">
                                        Creado por {{ $post->creator->name }}
                                    </p>
                                @endif
                            </div>

                            <div class="ml-auto flex flex-none flex-nowrap items-center gap-2 overflow-x-auto lg:justify-end">
                                <a href="{{ route('admin.tablon.edit', $post) }}"
                                    class="inline-flex shrink-0 items-center rounded-xl border border-brand-secondary/15 bg-white px-4 py-2.5 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                    Editar
                                </a>
                                @if (! $post->is_published)
                                    <form method="POST" action="{{ route('admin.tablon.update', $post) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="title" value="{{ $post->title }}">
                                        <input type="hidden" name="body" value="{{ $post->body }}">
                                        <input type="hidden" name="is_published" value="1">
                                        @foreach ($post->attachments as $attachment)
                                            <input type="hidden" name="keep_attachment_ids[]" value="{{ $attachment->id }}">
                                        @endforeach
                                        <button type="submit"
                                            class="inline-flex shrink-0 cursor-pointer items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                            Publicar
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.tablon.destroy', $post) }}" onsubmit="return confirm('&iquest;Seguro que quieres eliminar esta publicaci&oacute;n?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex shrink-0 cursor-pointer items-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                        Borrar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.75rem] border border-dashed border-brand-secondary/20 bg-slate-50 px-6 py-14 text-center">
                        <h2 class="text-2xl font-semibold text-brand-secondary">Todav&iacute;a no hay publicaciones</h2>
                        <p class="mt-2 text-sm text-brand-secondary/70">Crea el primer anuncio para empezar a usar el tabl&oacute;n.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        </section>
    </main>
@endsection
