@extends('layouts.app')

@section('title', 'Informes de reseñas')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Informes</h1>
                <p class="mt-2 text-sm text-gray-600">Accede a los distintos informes de reseñas disponibles.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <a href="{{ $monthlyReportsUrl }}"
                class="group rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-brand-secondary">Informes mensuales</p>
                        <p class="mt-1 text-sm text-gray-500">Resumen detallado por mes.</p>
                    </div>
                    <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                        Abrir
                    </span>
                </div>
            </a>

            <a href="{{ $semiannualReportsUrl }}"
                class="group rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-brand-secondary">Informes semestrales</p>
                        <p class="mt-1 text-sm text-gray-500">Resumen comparativo por semestre.</p>
                    </div>
                    <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                        Abrir
                    </span>
                </div>
            </a>
        </div>
    </div>
@endsection
