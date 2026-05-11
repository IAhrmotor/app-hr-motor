@extends('layouts.app')

@section('title', 'Informes mensuales')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Informes mensuales</h1>
                <p class="mt-2 text-sm text-gray-600">Selecciona el informe disponible para el periodo mensual.</p>
            </div>

            <a href="{{ $hubUrl }}"
                class="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-600 transition hover:border-gray-300 hover:text-gray-800">
                Volver
            </a>
        </div>

        @include('reviews.partials.reports-hub', [
            'showHeader' => false,
            'cards' => [
                [
                    'title' => 'Comparativa delegaciones',
                    'description' => 'Tabla consolidada por delegación y mes.',
                    'url' => $comparisonUrl,
                ],
            ],
        ])
    </div>
@endsection
