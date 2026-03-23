@extends('layouts.app')

@section('content')
    <section class="border-b border-brand-secondary/10 bg-white">
        <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                Administracion
            </span>

            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-semibold text-brand-secondary md:text-4xl">
                        Logs de delegaciones
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Aqui puedes revisar las altas, ediciones y eliminaciones de delegaciones con su fecha, hora y la persona que realizo la gestion.
                    </p>
                </div>

                <a href="{{ route('admin.dealership-logs.export', request()->only(['action', 'date_from', 'date_to', 'actor'])) }}"
                    data-logs-export-link
                    class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    Descargar CSV
                </a>
            </div>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        @if ($missingTable ?? false)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                La tabla de logs todavia no existe en esta base de datos. Ejecuta la migracion para empezar a registrar actividad.
            </div>
        @endif

        <div id="admin-logs-container">
            @include('admin.dealership-logs.partials.content')
        </div>
    </main>

    @include('admin.dealership-logs.partials.script')
@endsection
