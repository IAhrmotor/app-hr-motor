@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                    Incidencias
                </span>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary md:text-4xl">Crear herramienta</h1>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Define el nombre y el color de la nueva herramienta que verán los usuarios al abrir una incidencia.
                </p>
            </div>

            <div class="mt-8">
                @include('admin.ticket-tools._form', [
                    'action' => route('admin.ticket-tools.store'),
                    'method' => 'POST',
                    'submitLabel' => 'Crear herramienta',
                ])
            </div>
        </section>
    </main>
@endsection
