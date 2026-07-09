@extends('layouts.app')

@section('content')
    @php
        $totalOpenTickets = collect($reportCards)->sum('totalOpenTickets');
        $peopleWithOpenTickets = collect($reportCards)->count();
        $topCard = collect($reportCards)->sortByDesc('totalOpenTickets')->first();
        $maxClosedTickets = (int) (collect($closedReportRows ?? [])->max('totalClosedTickets') ?? 0);
        $polarToCartesian = function (float $centerX, float $centerY, float $radius, float $angleDeg): array {
            $angleRad = deg2rad($angleDeg - 90);

            return [
                'x' => $centerX + ($radius * cos($angleRad)),
                'y' => $centerY + ($radius * sin($angleRad)),
            ];
        };
        $describePieSlice = function (float $centerX, float $centerY, float $radius, float $startAngle, float $endAngle) use ($polarToCartesian): string {
            $sweep = $endAngle - $startAngle;
            $outerStart = $polarToCartesian($centerX, $centerY, $radius, $startAngle);

            if ($sweep >= 359.999) {
                $midPoint = $polarToCartesian($centerX, $centerY, $radius, $startAngle + 180);

                return sprintf(
                    'M %.3f %.3f L %.3f %.3f A %.3f %.3f 0 1 1 %.3f %.3f A %.3f %.3f 0 1 1 %.3f %.3f Z',
                    $centerX,
                    $centerY,
                    $outerStart['x'],
                    $outerStart['y'],
                    $radius,
                    $radius,
                    $midPoint['x'],
                    $midPoint['y'],
                    $radius,
                    $radius,
                    $outerStart['x'],
                    $outerStart['y']
                );
            }

            $outerEnd = $polarToCartesian($centerX, $centerY, $radius, $endAngle);
            $largeArc = ($sweep > 180) ? 1 : 0;

            return sprintf(
                'M %.3f %.3f L %.3f %.3f A %.3f %.3f 0 %d 1 %.3f %.3f Z',
                $centerX,
                $centerY,
                $outerStart['x'],
                $outerStart['y'],
                $radius,
                $radius,
                $largeArc,
                $outerEnd['x'],
                $outerEnd['y']
            );
        };
        $contrastTextColor = function (string $hexColor): string {
            $hex = ltrim($hexColor, '#');

            if (strlen($hex) === 3) {
                $hex = preg_replace('/(.)/u', '$1$1', $hex);
            }

            if (! is_string($hex) || strlen($hex) !== 6) {
                return '#ffffff';
            }

            $red = hexdec(substr($hex, 0, 2));
            $green = hexdec(substr($hex, 2, 2));
            $blue = hexdec(substr($hex, 4, 2));
            $luminance = (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);

            return $luminance > 170 ? '#0f172a' : '#ffffff';
        };
        $segmentLabelPosition = function (float $centerX, float $centerY, float $innerRadius, float $outerRadius, float $startAngle, float $endAngle) use ($polarToCartesian): array {
            $midAngle = ($startAngle + $endAngle) / 2;
            $labelRadius = $innerRadius + (($outerRadius - $innerRadius) * 0.62);

            return $polarToCartesian($centerX, $centerY, $labelRadius, $midAngle);
        };
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-8 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
            <div
                class="relative bg-cover bg-no-repeat px-6 py-10 sm:px-8 sm:py-12"
                style="background-image: url('{{ $heroImageUrl }}'); background-position: center 50%;"
            >
                <div class="absolute inset-0 bg-slate-950/70"></div>
                <div class="relative flex flex-col gap-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-3">
                            <div class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm">
                                Tickets IT
                            </div>
                            <div class="space-y-2">
                                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-5xl">
                                    Informes de ticketing
                                </h1>
                                <p class="max-w-3xl text-sm leading-6 text-white/80 sm:text-base">
                                    Este primer informe muestra las incidencias actuales sin resolver, agrupadas por cada persona de IT y coloreadas según su estado.
                                </p>
                            </div>
                        </div>

                        <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-brand-secondary shadow-sm transition hover:-translate-y-0.5">
                            Volver a tickets
                        </a>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.5rem] border border-white/15 bg-white/10 p-4 text-white backdrop-blur-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/65">Total abiertos</p>
                            <p class="mt-2 text-3xl font-bold">{{ $totalOpenTickets }}</p>
                        </div>

                        <div class="rounded-[1.5rem] border border-white/15 bg-white/10 p-4 text-white backdrop-blur-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/65">Personas con carga</p>
                            <p class="mt-2 text-3xl font-bold">{{ $peopleWithOpenTickets }}</p>
                        </div>

                        <div class="rounded-[1.5rem] border border-white/15 bg-white/10 p-4 text-white backdrop-blur-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/65">Más cargada</p>
                            <p class="mt-2 text-lg font-bold leading-tight">
                                {{ $topCard['name'] ?? 'Sin datos' }}
                            </p>
                            <p class="mt-1 text-sm text-white/75">
                                {{ $topCard['totalOpenTickets'] ?? 0 }} incidencias abiertas
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if (empty($reportCards))
            <section class="mt-6 rounded-[2rem] border border-dashed border-brand-secondary/15 bg-white p-10 text-center shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">
                    Sin incidencias
                </p>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-brand-secondary">
                    No hay tickets abiertos para mostrar
                </h2>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/65">
                    Cuando haya incidencias sin resolver asignadas a IT, aparecerán aquí repartidas por persona y por estado.
                </p>
            </section>
        @else
            <section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                    Tickets abiertos por usuario
                </h2>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">
                            Leyenda
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($openStatusOrder as $statusKey)
                                @php
                                    $status = $openStatusMeta[$statusKey] ?? null;
                                @endphp
                                @if ($status)
                                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-secondary/10 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-brand-secondary/70">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $status['badgeColor'] ?? '#94a3b8' }}"></span>
                                        {{ $status['label'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="max-w-xl text-sm leading-6 text-brand-secondary/65">
                        Solo se incluyen tickets en curso, pendientes de usuario y reaperturas. Los cerrados y clausurados quedan fuera de este informe.
                    </div>
                </div>

                <div class="mt-6 grid gap-4 xl:grid-cols-6">
                    @foreach ($reportCards as $card)
                        @php
                            $sliceStart = 0.0;
                            $chartSegments = collect($card['segments'])->map(function ($segment) use (&$sliceStart) {
                                $start = $sliceStart;
                                $end = $sliceStart + ($segment['percentage'] * 3.6);
                                $sliceStart = $end;

                                return [
                                    'key' => $segment['key'],
                                    'label' => $segment['label'],
                                    'count' => $segment['count'],
                                    'color' => $segment['color'],
                                    'start' => $start,
                                    'end' => $end,
                                ];
                            })->all();
                            $centerX = 140;
                            $centerY = 140;
                            $outerRadius = 122;
                            $innerRadius = 0;
                            $singleSegment = count($chartSegments) === 1;
                        @endphp

                        <article class="rounded-[2rem] bg-white p-4">
                            <div class="flex flex-col items-center gap-4">
                                <div class="relative w-full max-w-[11rem] shrink-0">
                                    <svg viewBox="0 0 280 280" class="h-auto w-full" role="img" aria-label="Rosco de incidencias de {{ $card['name'] }}">

                                        @foreach ($chartSegments as $segment)
                                            @php
                                                $slicePath = $describePieSlice($centerX, $centerY, $outerRadius, $segment['start'], $segment['end']);
                                                $labelPosition = $segment['count'] > 0
                                                    ? $segmentLabelPosition($centerX, $centerY, $innerRadius, $outerRadius, $segment['start'], $segment['end'])
                                                    : ['x' => $centerX, 'y' => $centerY];
                                                $labelColor = $contrastTextColor($segment['color']);
                                                $strokeColor = $singleSegment ? 'none' : '#ffffff';
                                                $strokeWidth = $singleSegment ? '0' : '5';
                                            @endphp

                                            <path d="{{ $slicePath }}" fill="{{ $segment['color'] }}" stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}" stroke-linejoin="round" />

                                            @if (! $singleSegment)
                                                <text
                                                    x="{{ $labelPosition['x'] }}"
                                                    y="{{ $labelPosition['y'] }}"
                                                    fill="{{ $labelColor }}"
                                                    text-anchor="middle"
                                                    dominant-baseline="middle"
                                                    font-size="30"
                                                    font-weight="900"
                                                    style="paint-order: stroke; stroke: rgba(15, 23, 42, 0.18); stroke-width: 3px;"
                                                >
                                                    {{ $segment['count'] }}
                                                </text>
                                            @endif
                                        @endforeach

                                        @if ($singleSegment)
                                            <text
                                                x="{{ $centerX }}"
                                                y="{{ $centerY }}"
                                                fill="{{ $contrastTextColor($chartSegments[0]['color']) }}"
                                                text-anchor="middle"
                                                dominant-baseline="middle"
                                                font-size="46"
                                                font-weight="900"
                                                style="paint-order: stroke; stroke: rgba(15, 23, 42, 0.18); stroke-width: 3px;"
                                            >
                                                {{ $chartSegments[0]['count'] }}
                                            </text>
                                        @endif

                                    </svg>
                                </div>

                                <div class="text-center">
                                    <h2 class="text-lg font-bold tracking-tight text-brand-secondary">
                                        {{ $card['name'] }}
                                    </h2>
                                    <p class="mt-1 text-xs text-brand-secondary/65">
                                        {{ $card['totalOpenTickets'] }} incidencias abiertas
                                    </p>
                                </div>
                            </div>

                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                    Tickets cerrados por usuario
                </h2>

                @if (empty($closedReportRows))
                    <div class="mt-6 rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">
                        No hay tickets cerrados ni clausurados para mostrar.
                    </div>
                @else
                    @php
                        $closedChartWidth = max(1260, count($closedReportRows) * 190);
                        $closedChartHeight = 290;
                        $closedBarAreaHeight = 180;
                        $closedAxisY = 220;
                        $closedMaxBarHeight = 145;
                        $closedChartLeft = 70;
                        $closedChartRight = 70;
                        $closedChartInnerWidth = $closedChartWidth - $closedChartLeft - $closedChartRight;
                    @endphp

                    <div class="mt-6 overflow-x-auto">
                        <svg
                            viewBox="0 0 {{ $closedChartWidth }} {{ $closedChartHeight }}"
                            class="mx-auto block h-auto"
                            style="width: {{ $closedChartWidth }}px; max-width: 100%;"
                            role="img"
                            aria-label="Gráfica de tickets cerrados por usuario"
                        >
                            <line x1="{{ $closedChartLeft }}" y1="{{ $closedAxisY }}" x2="{{ $closedChartWidth - $closedChartRight }}" y2="{{ $closedAxisY }}" stroke="#cbd5e1" stroke-width="2" />

                            @foreach ($closedReportRows as $index => $row)
                                @php
                                    $slotWidth = $closedChartInnerWidth / max(count($closedReportRows), 1);
                                    $barWidth = min(62, max(30, $slotWidth * 0.38));
                                    $barX = $closedChartLeft + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
                                    $barHeight = $maxClosedTickets > 0 ? (($row['totalClosedTickets'] / $maxClosedTickets) * $closedMaxBarHeight) : 0;
                                    $barTop = $closedAxisY - $barHeight;
                                    $labelX = $barX + ($barWidth / 2);
                                    $barRadius = min(8, $barHeight / 2);
                                @endphp

                                <g>
                                    @if ($barHeight > 0)
                                        <path
                                            d="M {{ $barX }} {{ $closedAxisY }} H {{ $barX + $barWidth }} V {{ $barTop + $barRadius }} A {{ $barRadius }} {{ $barRadius }} 0 0 0 {{ $barX + $barWidth - $barRadius }} {{ $barTop }} H {{ $barX + $barRadius }} A {{ $barRadius }} {{ $barRadius }} 0 0 0 {{ $barX }} {{ $barTop + $barRadius }} Z"
                                            fill="#E51A2E"
                                        />
                                    @endif

                                    <text
                                        x="{{ $labelX }}"
                                        y="{{ max(36, $barTop - 8) }}"
                                        text-anchor="middle"
                                        fill="#0f172a"
                                        font-size="22"
                                        font-weight="800"
                                    >
                                        {{ $row['totalClosedTickets'] }}
                                    </text>

                                    <text
                                        x="{{ $labelX }}"
                                        y="{{ $closedAxisY + 30 }}"
                                        text-anchor="middle"
                                        fill="#334155"
                                        font-size="16"
                                        font-weight="700"
                                    >
                                        {{ $row['name'] }}
                                    </text>
                                </g>
                            @endforeach
                        </svg>
                    </div>
                @endif
            </section>
        @endif
    </main>
@endsection
