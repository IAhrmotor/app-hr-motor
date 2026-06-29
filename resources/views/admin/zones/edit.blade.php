@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Administración</span>
                    <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Editar zona</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Actualiza el nombre y los destinos vinculados a esta zona.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.zones.update', $zone) }}" class="space-y-8" data-zone-form-root>
                    @csrf
                    @method('PUT')
                    @include('admin.zones._form', ['zone' => $zone, 'availableDealerships' => $availableDealerships])

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('admin.zones.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:cursor-pointer hover:bg-brand-secondary/5">
                            Volver
                        </a>

                        <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:cursor-pointer hover:-translate-y-0.5 hover:shadow-md">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
