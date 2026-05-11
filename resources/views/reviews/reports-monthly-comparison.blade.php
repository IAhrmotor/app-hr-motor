@extends('layouts.app')

@php
    use Carbon\Carbon;

    $persistedQuery = request()->except(['month', 'sort', 'direction']);
    $sortDirection = function (string $column, string $sort, string $direction): string {
        if ($sort !== $column) {
            return 'asc';
        }

        return $direction === 'asc' ? 'desc' : 'asc';
    };
    $sortLink = function (string $column) use ($persistedQuery, $sort, $direction, $sortDirection): string {
        return route('reviews.reports.monthly.comparison', array_merge($persistedQuery, [
            'month' => request('month'),
            'sort' => $column,
            'direction' => $sortDirection($column, $sort, $direction),
        ]));
    };
@endphp

@section('title', 'Comparativa delegaciones')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Comparativa delegaciones</h1>
                <p class="mt-2 text-sm text-gray-600">Historial consolidado por delegación y mes.</p>
            </div>

            <a href="{{ $hubUrl }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Volver a informes mensuales
            </a>
        </div>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0 flex-1">
                    <label for="reports-month" class="text-sm font-semibold text-brand-secondary">Mes a comparar</label>
                    <p class="mt-1 text-sm text-gray-500">Selecciona el mes que quieres ver en la comparativa.</p>
                </div>

                <div class="flex w-full gap-3 sm:w-auto">
                    @foreach ($persistedQuery as $queryKey => $queryValue)
                        @if (is_array($queryValue))
                            @foreach ($queryValue as $nestedValue)
                                <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                        @endif
                    @endforeach

                    <select id="reports-month" name="month"
                        class="h-12 w-full min-w-[12rem] rounded-2xl border-gray-200 bg-gray-50 px-4 text-sm focus:border-brand-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                        onchange="this.form.submit()">
                        @foreach ($availableMonths as $month)
                            <option value="{{ $month->format('Y-m') }}" @selected($selectedMonth === $month->format('Y-m'))>
                                {{ $month->format('m/Y') }}
                            </option>
                        @endforeach
                    </select>

                    <noscript>
                        <button type="submit"
                            class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Filtrar
                        </button>
                    </noscript>
                </div>
            </form>
        </div>

        @if ($selectedMonth)
            <div class="mb-6 rounded-3xl border border-brand-primary/15 bg-brand-primary/5 px-5 py-4 text-sm text-brand-secondary">
                Estás comparando el mes de <span class="font-semibold">{{ Carbon::createFromFormat('Y-m', $selectedMonth)->format('m/Y') }}</span>.
            </div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            @foreach ([
                                'month' => 'Mes',
                                'dealership' => 'Delegación',
                                'total_reviews' => 'Total',
                                'average_rating' => 'Media',
                                'monthly_reviews' => 'Este mes',
                                'monthly_average_rating' => 'Media mes',
                                'unanswered_reviews' => 'Sin responder',
                            ] as $column => $label)
                                <th class="px-5 py-3">
                                    <a href="{{ $sortLink($column) }}" class="inline-flex items-center gap-1.5 transition hover:text-brand-primary">
                                        <span>{{ $label }}</span>
                                        @if (($sort ?? 'dealership') === $column)
                                            <span class="text-[10px] font-semibold tracking-[0.2em] text-brand-primary">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                            @endforeach
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
