@extends('layouts.app')

@section('content')
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
                    Administración
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 md:text-base">
                    Centraliza desde aquí los accesos administrativos del portal y entra rápidamente en cada área de gestión.
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
                    Gestión interna
                </span>

                <h2 class="mt-3 text-2xl font-semibold text-brand-secondary md:text-3xl">
                    Herramientas de administración
                </h2>

                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Este bloque agrupa los accesos de gestión del portal.
                </p>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                @foreach ($adminSections as $section)
                    <a
                        href="{{ route($section['route']) }}"
                        class="group rounded-3xl border border-brand-secondary/10 bg-slate-50 p-8 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="flex h-full flex-col">
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                                Acceso directo
                            </span>

                            <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                {{ $section['label'] }}
                            </h3>

                            <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                {{ $section['description'] }}
                            </p>

                            <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary">
                                Ir a la sección
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

        <section class="mt-8 rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                    Acciones rápidas
                </span>

                <h2 class="mt-3 text-2xl font-semibold text-brand-secondary md:text-3xl">
                    Sincronización rankings manual
                </h2>

                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Lanza una sincronización inmediata para refrescar los rankings de ventas y compras sin esperar al
                    siguiente ciclo automático.
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
