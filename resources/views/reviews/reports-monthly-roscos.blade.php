@extends('layouts.app')

@php
    use Carbon\Carbon;

    $comparisonTitle = $comparisonTitle ?? 'Comparativa delegaciones roscos';
    $persistedQuery = request()->except(['month', 'sort', 'direction']);
    $selectedMonthLabel = $selectedMonth ? Carbon::createFromFormat('Y-m', $selectedMonth)->format('m/Y') : null;
    $sort = $sort ?? 'total';
    $direction = $direction ?? 'desc';
    $sortDirection = function (string $column, string $sort, string $direction): string {
        if ($sort !== $column) {
            return $column === 'title' ? 'asc' : 'desc';
        }

        return $direction === 'asc' ? 'desc' : 'asc';
    };
    $sortLink = function (string $column) use ($persistedQuery, $selectedMonth, $sort, $direction, $sortDirection): string {
        return route('reviews.reports.monthly.roscos', array_merge($persistedQuery, [
            'month' => $selectedMonth,
            'sort' => $column,
            'direction' => $sortDirection($column, $sort, $direction),
        ]));
    };
    $buildGradient = function (array $rosco): string {
        $redEnd = (float) ($rosco['red_angle'] ?? 0);
        $yellowEnd = $redEnd + (float) ($rosco['yellow_angle'] ?? 0);
        $greenEnd = $yellowEnd + (float) ($rosco['green_angle'] ?? 0);

        if ((int) ($rosco['total'] ?? 0) === 0) {
            return 'background: #e5e7eb;';
        }

        return sprintf(
            'background: conic-gradient(from -90deg, #ef4444 0deg %.2fdeg, #f59e0b %.2fdeg %.2fdeg, #10b981 %.2fdeg %.2fdeg);',
            $redEnd,
            $redEnd,
            $yellowEnd,
            $yellowEnd,
            $greenEnd
        );
    };
@endphp

@section('title', $comparisonTitle)

@section('content')
    <div
        class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
        x-data="{
            search: '',
            debouncedSearch: '',
            searchTimeout: null,
            init() {
                this.debouncedSearch = this.search;

                this.$watch('search', (value) => {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.debouncedSearch = value;
                    }, 180);
                });
            },
            normalize(value) {
                return String(value ?? '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            },
            matchesText(value) {
                const term = this.normalize(this.debouncedSearch).trim();

                if (! term) {
                    return true;
                }

                return this.normalize(value).includes(term);
            },
        }"
    >
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">{{ $comparisonTitle }}</h1>
                <p class="mt-2 text-sm text-gray-600">Selecciona un mes para ver los roscos por delegación.</p>
            </div>

            <a href="{{ $hubUrl }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Volver
            </a>
        </div>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0 flex-1">
                    <label for="reports-month" class="text-sm font-semibold text-brand-secondary">Mes a comparar</label>
                    <p class="mt-1 text-sm text-gray-500">Selecciona el mes que quieres visualizar.</p>
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

        @if ($selectedMonthLabel)
            <div class="mb-6 rounded-3xl border border-brand-primary/15 bg-brand-primary/5 px-5 py-4 text-sm text-brand-secondary">
                Estás comparando el mes de <span class="font-semibold">{{ $selectedMonthLabel }}</span>.
            </div>
        @endif

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-brand-secondary">Ordenar rosco</p>
                    <p class="text-sm text-gray-500">Puedes ordenar por total, nombre o por color.</p>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="w-full lg:max-w-md">
                        <label for="roscos-search" class="mb-2 block text-sm font-semibold text-brand-secondary">
                            Buscar roscos
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                id="roscos-search"
                                x-model="search"
                                type="text"
                                placeholder="Buscar delegación..."
                                class="w-full rounded-2xl border-gray-200 bg-gray-50 py-3 pl-12 pr-4 text-sm placeholder:text-gray-400 focus:border-brand-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                            >
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'total' => 'Total',
                        'title' => 'Nombre',
                        'red' => 'Rojo',
                        'yellow' => 'Amarillo',
                        'green' => 'Verde',
                    ] as $column => $label)
                        <a href="{{ $sortLink($column) }}"
                            class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition {{ $sort === $column ? 'border-brand-primary bg-brand-primary/10 text-brand-primary' : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300 hover:bg-gray-100' }}">
                            <span>{{ $label }}</span>
                            @if ($sort === $column)
                                <span class="text-[10px] tracking-[0.18em]">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($roscos->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach ($roscos as $rosco)
                    <article x-cloak x-show="matchesText(@js($rosco['title'] ?? ''))" class="rounded-[1.75rem] border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-center">
                            <p class="text-lg font-semibold text-brand-secondary">{{ $rosco['title'] }}</p>
                            <p class="mt-1 text-sm text-gray-500">Total: {{ number_format($rosco['total']) }}</p>
                        </div>

                        <div class="mt-5 flex justify-center">
                            <div class="relative h-48 w-48">
                                <div class="absolute inset-0 rounded-full" style="{{ $buildGradient($rosco) }}"></div>
                                <div class="absolute inset-[22%] rounded-full bg-white shadow-inner"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <p class="text-3xl font-bold text-brand-secondary">{{ number_format($rosco['total']) }}</p>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Reseñas</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                1-2: {{ number_format($rosco['red']) }}
                            </span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                3: {{ number_format($rosco['yellow']) }}
                            </span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                4-5: {{ number_format($rosco['green']) }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center text-sm text-gray-500">
                No hay reseñas de delegación para el mes seleccionado.
            </div>
        @endif
    </div>
@endsection
