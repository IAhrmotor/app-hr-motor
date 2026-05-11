@extends('layouts.app')

@section('title', 'Informes mensuales')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Informes mensuales</h1>
                <p class="mt-2 text-sm text-gray-600">Historial consolidado por delegación y mes.</p>
            </div>

            <a href="{{ route('reviews.reports') }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Volver a informes
            </a>
        </div>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <span class="inline-flex h-11 items-center justify-center rounded-2xl bg-brand-primary px-4 text-sm font-semibold text-white">
                    Comparativa delegaciones
                </span>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Mes</th>
                            <th class="px-5 py-3">Delegación</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Media</th>
                            <th class="px-5 py-3">Este mes</th>
                            <th class="px-5 py-3">Media mes</th>
                            <th class="px-5 py-3">Sin responder</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($snapshots as $snapshot)
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $snapshot->snapshot_month?->format('m/Y') }}</td>
                                <td class="px-5 py-4 text-sm font-medium text-brand-secondary">{{ $snapshot->dealership?->name }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $snapshot->total_reviews }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ number_format((float) $snapshot->average_rating, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $snapshot->monthly_reviews }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ number_format((float) $snapshot->monthly_average_rating, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $snapshot->unanswered_reviews }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">Aún no hay informes mensuales guardados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
