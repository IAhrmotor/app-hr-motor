@extends('layouts.app')

@section('title', 'Informes de reseñas')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <a href="{{ route('reviews.index') }}" class="text-sm font-semibold text-brand-primary">Volver a reseñas</a>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Informes mensuales</h1>
                <p class="mt-2 text-sm text-gray-600">Historial consolidado por delegacion y mes.</p>
            </div>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Mes</th>
                            <th class="px-5 py-3">Delegacion</th>
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
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">Aun no hay informes mensuales guardados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
