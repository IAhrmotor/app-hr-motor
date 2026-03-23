@extends('layouts.app')

@section('content')
    @php
        $allSections = collect($managementSections ?? $adminSections ?? []);
        $logs = collect($logSections ?? [])->when(
            empty($logSections ?? []),
            fn ($collection) => $collection->merge(
                $allSections->filter(fn ($section) => str_contains($section['route'], 'logs'))
            )
        );
        $management = collect($managementSections ?? [])->when(
            empty($managementSections ?? []),
            fn ($collection) => $collection->merge(
                $allSections->reject(fn ($section) => str_contains($section['route'], 'logs'))
            )
        );
    @endphp

    <section
        class="relative overflow-hidden"
        style="background-image: url('{{ asset('images/hero/hero-admin.jpg') }}'); background-size: cover; background-position: center;"
    >
        <div class="absolute inset-0 bg-black/55"></div>

        <div class="relative mx-auto flex min-h-[220px] max-w-7xl items-center px-6 py-6 sm:min-h-[240px] sm:py-8 lg:min-h-[260px] lg:px-8 lg:py-10">
            <div class="max-w-3xl">
                <span
                    class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white backdrop-blur-sm"
                >
                    Panel interno
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-white md:text-3xl lg:text-4xl">
                    {{ html_entity_decode('Administraci&oacute;n') }}
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 md:text-base">
                    {{ html_entity_decode('Centraliza desde aqu&iacute; los accesos administrativos del portal y entra r&aacute;pidamente en cada &aacute;rea de gesti&oacute;n.') }}
                </p>
            </div>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                    {{ html_entity_decode('Gesti&oacute;n interna') }}
                </span>

                <h2 class="mt-3 text-2xl font-semibold text-brand-secondary md:text-3xl">
                    {{ html_entity_decode('Panel de administraci&oacute;n') }}
                </h2>

                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    {{ html_entity_decode('Hemos separado las acciones operativas del portal de la parte de auditor&iacute;a para que todo quede m&aacute;s claro, ordenado y coherente.') }}
                </p>
            </div>

            <div class="mt-8 grid gap-6">
                <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-6 shadow-sm">
                    <div class="max-w-2xl">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                            Operativa
                        </span>

                        <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                            {{ html_entity_decode('Herramientas de gesti&oacute;n') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                            Accesos para crear, editar y mantener la estructura interna del portal.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        @foreach ($management as $section)
                            <a
                                href="{{ route($section['route']) }}"
                                class="group rounded-3xl border border-brand-secondary/10 bg-white p-7 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                            >
                                <div class="flex h-full flex-col">
                                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                                        {{ html_entity_decode('Gesti&oacute;n') }}
                                    </span>

                                    <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                        {{ $section['label'] }}
                                    </h3>

                                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                        {{ $section['description'] }}
                                    </p>

                                    <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary">
                                        {{ html_entity_decode('Ir a la secci&oacute;n') }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition group-hover:translate-x-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-6 shadow-sm">
                    <div class="max-w-2xl">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                            {{ html_entity_decode('Auditor&iacute;a') }}
                        </span>

                        <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                            Logs y trazabilidad
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                            {{ html_entity_decode('Consulta el historial de cambios para entender qui&eacute;n hizo cada gesti&oacute;n y cu&aacute;ndo ocurri&oacute;.') }}
                        </p>
                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        @foreach ($logs as $section)
                            <a
                                href="{{ route($section['route']) }}"
                                class="group rounded-3xl border border-brand-secondary/10 bg-white p-7 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                            >
                                <div class="flex h-full flex-col">
                                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                                        Log
                                    </span>

                                    <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                        {{ $section['label'] }}
                                    </h3>

                                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                        {{ str_contains($section['route'], 'dealership') ? 'Altas, cambios y bajas de delegaciones.' : 'Altas, cambios y bajas de usuarios.' }}
                                    </p>

                                    <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary">
                                        Ver historial
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition group-hover:translate-x-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>

        <section class="mt-8 rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                    {{ html_entity_decode('Acciones r&aacute;pidas') }}
                </span>

                <h2 class="mt-3 text-2xl font-semibold text-brand-secondary md:text-3xl">
                    {{ html_entity_decode('Sincronizaci&oacute;n rankings manual') }}
                </h2>

                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    {{ html_entity_decode('Lanza una sincronizaci&oacute;n inmediata para refrescar los rankings de ventas y compras sin esperar al siguiente ciclo autom&aacute;tico.') }}
                </p>
            </div>

            <div class="mt-8">
                <form method="POST" action="{{ route('leaderboard.sync') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex cursor-pointer items-center gap-3 rounded-2xl bg-brand-primary px-6 py-4 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992V4.356m-1.636 10.26a9 9 0 11-2.867-9.668L21 9.348" />
                        </svg>
                        Actualizar rankings ahora
                    </button>
                </form>
            </div>
        </section>
    </main>
@endsection
