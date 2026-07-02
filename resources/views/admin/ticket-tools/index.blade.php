@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                        Incidencias
                    </span>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary">
                            <x-icons.ticket-tools class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Herramientas tickets</h1>
                            <p class="mt-1 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                Gestiona las herramientas disponibles en el portal de incidencias con nombre y color.
                            </p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.ticket-tools.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                    Crear herramienta
                </a>
            </div>

            @if (session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($tools as $tool)
                    <article class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="h-4 w-4 rounded-full ring-2 ring-white shadow-sm" style="background-color: {{ $tool->color }}"></span>
                                <div>
                                    <h2 class="text-lg font-semibold text-brand-secondary">{{ $tool->name }}</h2>
                                    <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] text-brand-secondary/50">{{ $tool->color }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.ticket-tools.edit', $tool) }}"
                                    class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-3 py-2 text-xs font-semibold text-brand-secondary transition hover:bg-white">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.ticket-tools.destroy', $tool) }}" onsubmit="return confirm('¿Seguro que quieres eliminar esta herramienta?');">
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
                            <h2 class="text-xl font-bold tracking-tight text-brand-secondary">Todavía no hay herramientas</h2>
                            <p class="mt-2 text-sm text-brand-secondary/70">Crea la primera para que aparezca en el formulario de incidencias.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
