@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                        Foro
                    </span>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Tags del foro</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Organiza las dudas del equipo con etiquetas de color. Puedes crear, editar y eliminar tags desde aquí.
                    </p>
                </div>

                <a href="{{ route('admin.forum-tags.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                    Crear tag
                </a>
            </div>

            @if (session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($tags as $tag)
                    <article class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="h-4 w-4 rounded-full ring-2 ring-white shadow-sm" style="background-color: {{ $tag->color }}"></span>
                                <div>
                                    <h2 class="text-lg font-semibold text-brand-secondary">{{ $tag->name }}</h2>
                                    <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] text-brand-secondary/50">{{ $tag->color }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.forum-tags.edit', $tag) }}"
                                    class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-3 py-2 text-xs font-semibold text-brand-secondary transition hover:bg-white">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.forum-tags.destroy', $tag) }}" onsubmit="return confirm('¿Seguro que quieres eliminar este tag?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex cursor-pointer items-center rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50">
                                        Borrar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <div class="rounded-[1.5rem] border border-dashed border-brand-secondary/20 bg-slate-50 px-6 py-12 text-center">
                            <h2 class="text-xl font-bold tracking-tight text-brand-secondary">Todavía no hay tags</h2>
                            <p class="mt-2 text-sm text-brand-secondary/70">Crea el primero para empezar a organizar los hilos del foro.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
