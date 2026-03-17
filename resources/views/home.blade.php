@extends('layouts.app')

@section('content')
    @php
        $generalSection = collect($buttonSections)->firstWhere('title', 'Herramientas generales');
        $communicationSection = collect($buttonSections)->firstWhere('title', 'Comunicación');
        $officeSection = collect($buttonSections)->firstWhere('title', 'Office 365 online');

        $otherSections = collect($buttonSections)->reject(function ($section) {
            return in_array($section['title'], ['Herramientas generales', 'Comunicación', 'Office 365 online']);
        });

        $magazineUrl = asset('revista/revista-marzo-2026.pdf');
        $magazineEmbedUrl = asset('revista/revista-marzo-2026.pdf');
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <div class="space-y-8">
            <div class="grid gap-8 lg:grid-cols-2 lg:items-stretch">
                <div class="flex h-full flex-col gap-8">
                    @if ($communicationSection)
                        <section class="rounded-3xl border border-brand-primary/20 bg-brand-primary/5 p-6 shadow-sm">
                            <div class="relative mb-5">
                                <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                                    {{ $communicationSection['title'] }}
                                </h2>

                                <div
                                    class="absolute right-0 top-1/2 inline-flex -translate-y-1/2 rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                                    Destacado
                                </div>
                            </div>

                            <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                                @foreach ($communicationSection['buttons'] as $button)
                                    <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-primary/20 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                        <div class="bg-white">
                                            <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                                class="block w-full">
                                        </div>

                                        <div
                                            class="flex flex-1 items-center justify-center border-t border-brand-primary/10 px-4 py-3">
                                            <h3
                                                class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                                {{ $button['label'] }}
                                            </h3>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section
                        class="flex flex-1 flex-col rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
                        <div class="mb-6 text-center">
                            <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                                Asistencia IT
                            </h2>

                            <p class="mt-2 text-sm text-brand-secondary/70">
                                Reporta incidencias o solicita ayuda al equipo técnico.
                            </p>
                        </div>

                        <div class="flex flex-1 items-center justify-center">
                            <a href="{{ config('portal.links.it_support') }}" target="_blank" rel="noopener noreferrer"
                                class="group flex w-full max-w-md flex-col overflow-hidden rounded-2xl border border-brand-primary/15 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                <div class="px-6 py-6">
                                    <div class="text-center">
                                        <div
                                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-brand-primary/10 text-brand-primary transition duration-200 group-hover:bg-brand-primary/15">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.5 6.75V6a4.5 4.5 0 10-9 0v.75m-1.5 0h12A1.5 1.5 0 0119.5 8.25v8.25A3 3 0 0116.5 19.5h-9a3 3 0 01-3-3V8.25A1.5 1.5 0 016 6.75z" />
                                            </svg>
                                        </div>

                                        <h3
                                            class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                            Abrir incidencia
                                        </h3>

                                        <p class="mt-4 text-sm leading-6 text-brand-secondary/70">
                                            Accede al portal de soporte para registrar una incidencia.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-center border-t border-brand-primary/10 bg-brand-primary/5 px-4 py-3">
                                    <span
                                        class="text-center text-sm font-semibold uppercase tracking-[0.12em] text-brand-primary">
                                        Solicitar asistencia
                                    </span>
                                </div>
                            </a>
                        </div>
                    </section>
                </div>

                @if ($generalSection)
                    <section class="h-full rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                                {{ $generalSection['title'] }}
                            </h2>
                        </div>

                        <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                            @foreach ($generalSection['buttons'] as $button)
                                <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                    <div class="bg-white">
                                        <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                            class="block w-full">
                                    </div>

                                    <div
                                        class="flex flex-1 items-center justify-center border-t border-brand-secondary/10 px-4 py-3">
                                        <h3
                                            class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                            {{ $button['label'] }}
                                        </h3>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            @if ($officeSection)
                <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                            {{ $officeSection['title'] }}
                        </h2>
                    </div>

                    <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                        @foreach ($officeSection['buttons'] as $button)
                            <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                <div class="bg-white">
                                    <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                        class="block w-full">
                                </div>

                                <div
                                    class="flex flex-1 items-center justify-center border-t border-brand-secondary/10 px-4 py-3">
                                    <h3
                                        class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                        {{ $button['label'] }}
                                    </h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @foreach ($otherSections as $section)
                <section>
                    <div class="mb-5">
                        <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                            {{ $section['title'] }}
                        </h2>
                    </div>

                    <div class="grid justify-center grid-cols-[repeat(auto-fit,minmax(136px,136px))] gap-6">
                        @foreach ($section['buttons'] as $button)
                            <a href="{{ $button['url'] }}" target="_blank" rel="noopener noreferrer"
                                class="group flex h-full flex-col overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
                                <div class="bg-white">
                                    <img src="{{ $button['image'] }}" alt="{{ $button['label'] }}"
                                        class="block w-full">
                                </div>

                                <div
                                    class="flex flex-1 items-center justify-center border-t border-brand-primary/10 px-4 py-3">
                                    <h3
                                        class="text-center text-sm font-semibold uppercase tracking-wide text-brand-secondary">
                                        {{ $button['label'] }}
                                    </h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        @if ($homeLeaderboardEntries->isNotEmpty())
            <section class="mt-8 overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-[radial-gradient(circle_at_top,_rgba(229,26,46,0.12),_transparent_42%),linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-6 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-brand-primary">Leaderboard</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-brand-secondary">
                            Top 10 comerciales del mes
                        </h2>
                        <p class="mt-2 text-sm text-brand-secondary/70">
                            Los tres primeros destacan arriba y el resto completa el ranking justo debajo.
                        </p>
                    </div>

                    <a href="{{ route('leaderboard.index') }}"
                        class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Ver leaderboard completo
                    </a>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    @foreach ($homeLeaderboardEntries->take(3) as $entry)
                        @php
                            $medalStyles = match ($entry->ranking_position) {
                                1 => [
                                    'card' => 'border-yellow-300/80 bg-[linear-gradient(180deg,rgba(255,248,214,0.98),rgba(255,255,255,1))] shadow-[0_20px_40px_rgba(217,167,34,0.18)]',
                                    'badge' => 'bg-gradient-to-r from-yellow-500 via-amber-400 to-yellow-300 text-amber-950',
                                    'ring' => 'ring-yellow-300/70',
                                    'accent' => 'text-amber-600',
                                ],
                                2 => [
                                    'card' => 'border-slate-300/80 bg-[linear-gradient(180deg,rgba(241,245,249,0.98),rgba(255,255,255,1))] shadow-[0_20px_40px_rgba(100,116,139,0.15)]',
                                    'badge' => 'border border-slate-300/80 bg-[linear-gradient(135deg,#64748b_0%,#e2e8f0_50%,#94a3b8_100%)] text-slate-900',
                                    'ring' => 'ring-slate-300/80',
                                    'accent' => 'text-slate-500',
                                ],
                                default => [
                                    'card' => 'border-orange-300/80 bg-[linear-gradient(180deg,rgba(255,237,213,0.98),rgba(255,255,255,1))] shadow-[0_20px_40px_rgba(180,83,9,0.14)]',
                                    'badge' => 'bg-gradient-to-r from-orange-700 via-amber-700 to-orange-300 text-white',
                                    'ring' => 'ring-orange-300/80',
                                    'accent' => 'text-orange-600',
                                ],
                            };
                        @endphp

                        <article class="relative overflow-hidden rounded-[1.75rem] border p-6 {{ $medalStyles['card'] }}">
                            <div class="absolute right-4 top-4 rounded-full px-3 py-1 text-xs font-semibold {{ $medalStyles['badge'] }}">
                                #{{ $entry->ranking_position }}
                            </div>
                            <div class="flex items-center gap-4">
                                <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                    alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                    class="h-16 w-16 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                                <div>
                                    <p class="text-xl font-semibold text-brand-secondary">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                    <p class="text-sm text-brand-secondary/60">
                                        {{ $entry->user?->email ?? ($entry->salesforce_user_id ?: 'Sin vincular con usuario interno') }}
                                    </p>
                                </div>
                            </div>
                            <p class="mt-6 text-sm uppercase tracking-[0.3em] text-brand-secondary/50">Ventas</p>
                            <p class="mt-2 text-3xl font-semibold {{ $medalStyles['accent'] }}">
                                {{ number_format((float) $entry->total_sales, 0, ',', '.') }}
                            </p>
                        </article>
                    @endforeach
                </div>

                @if ($homeLeaderboardEntries->count() > 3)
                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($homeLeaderboardEntries->slice(3) as $entry)
                            @php
                                $rankStyles = match ($entry->ranking_position) {
                                    4 => 'border-brand-primary/20 bg-brand-primary/[0.03]',
                                    5 => 'border-brand-secondary/12 bg-white',
                                    6 => 'border-brand-secondary/12 bg-white',
                                    default => 'border-brand-secondary/10 bg-white/90',
                                };
                            @endphp

                            <article class="flex items-center gap-4 rounded-[1.5rem] border px-4 py-4 shadow-sm {{ $rankStyles }}">
                                <div class="flex h-11 min-w-11 items-center justify-center rounded-full bg-brand-secondary text-sm font-semibold text-white">
                                    #{{ $entry->ranking_position }}
                                </div>
                                <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                    alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                    class="h-12 w-12 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-brand-secondary">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                    <p class="truncate text-xs text-brand-secondary/60">
                                        {{ $entry->user?->email ?? ($entry->salesforce_user_id ?: 'Sin vincular con usuario interno') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-secondary/45">Ventas</p>
                                    <p class="mt-1 text-2xl font-semibold text-brand-primary">
                                        {{ number_format((float) $entry->total_sales, 0, ',', '.') }}
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="relative mb-5">
                <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                    Revista mensual
                </h2>

                <div
                    class="absolute right-0 top-1/2 inline-flex -translate-y-1/2 rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                    Marzo
                </div>

                <p class="mt-2 text-center text-sm text-brand-secondary/70">
                    Consulta la última edición de la revista interna.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
                <iframe src="{{ $magazineEmbedUrl }}" class="h-110 w-full" frameborder="0" allowfullscreen></iframe>
            </div>

            <div class="mt-4 text-right">
                <a href="{{ $magazineUrl }}" target="_blank" rel="noopener noreferrer"
                    class="text-sm font-medium text-brand-primary hover:underline">
                    Abrir revista en una nueva pestaña
                </a>
            </div>
        </section>

        <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-center text-2xl font-bold tracking-tight text-brand-secondary">
                    Vídeos de formación
                </h2>

                <p class="mt-2 text-center text-brand-secondary/70">
                    Consulta aquí los vídeos destacados.
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($videos as $video)
                    <article class="overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm">
                        <div class="aspect-video">
                            <iframe class="h-full w-full"
                                src="https://www.youtube.com/embed/{{ $video['youtube_id'] }}"
                                title="{{ $video['title'] }}" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        </div>

                        <div class="px-4 py-3">
                            <h3 class="text-center text-sm font-semibold text-brand-secondary">
                                {{ $video['title'] }}
                            </h3>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
@endsection
