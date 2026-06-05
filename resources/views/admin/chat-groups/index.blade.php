@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col gap-6">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Administracion del chat</span>
                    <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Grupos del chat</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Gestiona los grupos internos del chat, sus participantes y el historial de cambios desde un panel claro y centralizado.
                    </p>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Herramientas internas</p>
                        <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                            Crea nuevos grupos, actualiza sus miembros y elimina los que ya no se necesiten.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.chat-groups.create') }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        Crear grupo
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.chat-groups.index') }}" class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="relative w-full md:max-w-md">
                            <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Buscar por nombre"
                                class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                            >
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex cursor-pointer items-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                Buscar
                            </button>

                            @if ($search || $sort !== 'name' || $direction !== 'asc')
                                <a href="{{ route('admin.chat-groups.index') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                    Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <div data-chat-groups-results>
                    @include('admin.chat-groups.partials.index-results', [
                        'groups' => $groups,
                        'search' => $search,
                        'sort' => $sort,
                        'direction' => $direction,
                    ])
                </div>
            </div>
        </section>
    </main>
@endsection
