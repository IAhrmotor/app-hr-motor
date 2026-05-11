@extends('layouts.app')

@section('title', 'Informes semestrales')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Informes semestrales</h1>
                <p class="mt-2 text-sm text-gray-600">Esta sección queda preparada para los informes por semestre.</p>
            </div>

            <a href="{{ route('reviews.reports') }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Volver a informes
            </a>
        </div>

        <div class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center shadow-sm">
            <p class="text-lg font-semibold text-brand-secondary">Informe semestral en preparación</p>
            <p class="mt-2 text-sm text-gray-500">
                Cuando lo activemos, aquí mostraremos la comparativa por semestre con el mismo estilo de navegación.
            </p>
        </div>
    </div>
@endsection
