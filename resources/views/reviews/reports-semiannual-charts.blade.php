@extends('layouts.app')

@section('title', 'Gráficas comparativas')

@php
    $charts = collect($charts ?? []);
    $chartWidth = 760;
    $chartHeight = 300;
    $chartPadding = [
        'top' => 20,
        'right' => 18,
        'bottom' => 54,
        'left' => 48,
    ];
    $plotWidth = $chartWidth - $chartPadding['left'] - $chartPadding['right'];
    $plotHeight = $chartHeight - $chartPadding['top'] - $chartPadding['bottom'];
    $gridValues = [5, 4, 3, 2, 1, 0];
    $buildChartPoints = function (array $series) use ($chartPadding, $plotWidth, $plotHeight): array {
        $series = collect($series)->values();
        $count = max(1, $series->count());

        return $series->map(function (array $point, int $index) use ($series, $count, $chartPadding, $plotWidth, $plotHeight): array {
            $x = $chartPadding['left'] + ($count === 1 ? $plotWidth / 2 : ($plotWidth * $index) / ($count - 1));
            $average = (float) ($point['average'] ?? 0);
            $normalized = max(0, min(5, $average)) / 5;
            $y = $chartPadding['top'] + ($plotHeight - ($plotHeight * $normalized));
            $labelX = $x;
            $labelAnchor = 'middle';

            if ($index === 0) {
                $labelX = $x + 12;
                $labelAnchor = 'start';
            } elseif ($index === $count - 1) {
                $labelX = $x - 12;
                $labelAnchor = 'end';
            }

            return $point + [
                'x' => $x,
                'y' => $y,
                'label_x' => $labelX,
                'label_anchor' => $labelAnchor,
                'formatted_average' => number_format($average, 2),
            ];
        })->all();
    };
@endphp

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Gráficas comparativas</h1>
                <p class="mt-2 text-sm text-gray-600">Evolución de la media mensual por delegación en los últimos seis meses, incluyendo el mes actual.</p>
            </div>

            <a href="{{ $hubUrl }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Volver
            </a>
        </div>

        @if ($charts->isNotEmpty())
            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($charts as $chart)
                    @php
                        $series = collect($chart['series'] ?? []);
                        $hasData = $series->contains(fn (array $point): bool => (bool) ($point['has_data'] ?? false));
                        $chartPoints = collect($buildChartPoints($series->all()));
                        $linePoints = $chartPoints->map(fn (array $point): string => number_format((float) $point['x'], 2, '.', '') . ',' . number_format((float) $point['y'], 2, '.', ''))->implode(' ');
                    @endphp

                    <article class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-brand-secondary">{{ $chart['title'] }}</h2>
                                <p class="text-sm text-gray-500">Media mensual de los últimos seis meses.</p>
                            </div>
                            <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                                {{ number_format($chart['total_reviews'] ?? 0) }} reseñas
                            </span>
                        </div>

                        @if ($hasData)
                            <div class="mt-5 overflow-hidden rounded-[1.5rem] border border-gray-100 bg-gradient-to-b from-gray-50 to-white p-4 sm:p-5">
                                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-auto w-full" role="img" aria-label="Evolución histórica de {{ $chart['title'] }}">
                                    <title>Evolución histórica de {{ $chart['title'] }}</title>
                                    <desc>Gráfica de líneas con la media mensual de las últimas seis mensualidades.</desc>

                                    @foreach ($gridValues as $gridValue)
                                        @php
                                            $gridY = $chartPadding['top'] + ($plotHeight - ($plotHeight * ($gridValue / 5)));
                                        @endphp
                                        <line x1="{{ $chartPadding['left'] }}" y1="{{ $gridY }}" x2="{{ $chartWidth - $chartPadding['right'] }}" y2="{{ $gridY }}" stroke="#e5e7eb" stroke-dasharray="4 5" stroke-width="1" />
                                        <text x="16" y="{{ $gridY + 4 }}" fill="#6b7280" font-size="12" font-weight="600">{{ $gridValue }}</text>
                                    @endforeach

                                    <line x1="{{ $chartPadding['left'] }}" y1="{{ $chartPadding['top'] + $plotHeight }}" x2="{{ $chartWidth - $chartPadding['right'] }}" y2="{{ $chartPadding['top'] + $plotHeight }}" stroke="#d1d5db" stroke-width="1.25" />

                                    <polyline
                                        fill="none"
                                        stroke="#c2410c"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="4"
                                        points="{{ $linePoints }}"
                                    />

                                    @foreach ($chartPoints as $point)
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6" fill="#fff" stroke="#c2410c" stroke-width="3" />
                                        <text
                                            x="{{ $point['x'] }}"
                                            y="{{ max(16, $point['y'] - 14) }}"
                                            fill="#111827"
                                            font-size="12"
                                            font-weight="700"
                                            text-anchor="middle"
                                        >
                                            {{ $point['formatted_average'] }}
                                        </text>
                                        <text
                                            x="{{ $point['label_x'] }}"
                                            y="{{ $chartHeight - 18 }}"
                                            fill="#4b5563"
                                            font-size="12"
                                            font-weight="600"
                                            text-anchor="{{ $point['label_anchor'] }}"
                                        >
                                            {{ $point['label'] }}
                                        </text>
                                    @endforeach
                                </svg>
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                                No hay datos suficientes para mostrar esta evolución.
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center text-sm text-gray-500">
                Aún no hay gráficas comparativas disponibles.
            </div>
        @endif
    </div>
@endsection
