@extends('layouts.app')

@section('content')
    @php
        $totalOpenTickets = collect($reportCards)->sum('totalOpenTickets');
        $peopleWithOpenTickets = collect($reportCards)->count();
        $topCard = collect($reportCards)->sortByDesc('totalOpenTickets')->first();
        $ticketToolReportRows = collect(data_get($ticketToolReport ?? [], 'rows', []));
        $ticketToolReportTotal = (int) data_get($ticketToolReport, 'totalTickets', 0);
        $maxClosedTickets = (int) (collect($closedReportRows ?? [])->max('totalClosedTickets') ?? 0);
        $resolutionRows = collect(data_get($resolutionReport ?? [], 'rows', []));
        $resolutionChartRows = $resolutionRows->sortBy('averageMinutes')->values();
        $resolutionMaxMinutes = (int) ($resolutionRows->max('averageMinutes') ?? 0);
        $resolutionOverallAverageMinutes = (int) data_get($resolutionReport, 'overallAverageMinutes', 0);
        $dealershipReportRows = collect(data_get($dealershipReport ?? [], 'rows', []));
        $dealershipReportTotal = (int) data_get($dealershipReport, 'totalTickets', 0);
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
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">Sin incidencias</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-brand-secondary">No hay tickets abiertos para mostrar</h2>
        <p class="mt-3 text-sm leading-6 text-brand-secondary/65">Cuando haya incidencias sin resolver asignadas a IT, aparecerán aquí repartidas por persona y por estado.</p>
    </section>
@else
    <section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">Tickets abiertos por usuario</h2>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">Leyenda</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($openStatusOrder as $statusKey)
                        @php $status = $openStatusMeta[$statusKey] ?? null; @endphp
                        @if ($status)
                            <span class="inline-flex items-center gap-2 rounded-full border border-brand-secondary/10 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-brand-secondary/70">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $status["badgeColor"] ?? "#94a3b8" }}"></span>
                                {{ $status["label"] }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="max-w-xl text-sm leading-6 text-brand-secondary/65">Solo se incluyen tickets en curso, pendientes de usuario y reaperturas. Los cerrados y clausurados quedan fuera de este informe.</div>
        </div>
        <div class="mt-6 grid gap-4 xl:grid-cols-6">
            @foreach ($reportCards as $card)
                @php
                    $sliceStart = 0.0;
                    $chartSegments = collect($card["segments"])->map(function ($segment) use (&$sliceStart) {
                        $start = $sliceStart;
                        $end = $sliceStart + ($segment["percentage"] * 3.6);
                        $sliceStart = $end;
                        return [
                            "key" => $segment["key"],
                            "label" => $segment["label"],
                            "count" => $segment["count"],
                            "color" => $segment["color"],
                            "start" => $start,
                            "end" => $end,
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
                            <svg viewBox="0 0 280 280" class="h-auto w-full" role="img" aria-label="Rosco de incidencias de {{ $card["name"] }}">
                                @foreach ($chartSegments as $segment)
                                    @php
                                        $slicePath = $describePieSlice($centerX, $centerY, $outerRadius, $segment["start"], $segment["end"]);
                                        $labelPosition = $segment["count"] > 0 ? $segmentLabelPosition($centerX, $centerY, $innerRadius, $outerRadius, $segment["start"], $segment["end"]) : ["x" => $centerX, "y" => $centerY];
                                        $labelColor = $contrastTextColor($segment["color"]);
                                        $strokeColor = $singleSegment ? "none" : "#ffffff";
                                        $strokeWidth = $singleSegment ? "0" : "5";
                                        $segmentLink = route('tickets.index', [
                                            'managed_search' => $card['name'],
                                            'managed_status' => $segment['key'],
                                        ]);
                                    @endphp
                                    <a href="{{ $segmentLink }}" class="group">
                                        <path
                                            d="{{ $slicePath }}"
                                            fill="{{ $segment["color"] }}"
                                            stroke="{{ $strokeColor }}"
                                            stroke-width="{{ $strokeWidth }}"
                                            stroke-linejoin="round"
                                            class="cursor-pointer transition duration-150 ease-out group-hover:opacity-90 group-hover:scale-[1.02]"
                                            style="transform-box: fill-box; transform-origin: center;"
                                        />
                                        @if (! $singleSegment)
                                            <text x="{{ $labelPosition["x"] }}" y="{{ $labelPosition["y"] }}" fill="{{ $labelColor }}" text-anchor="middle" dominant-baseline="middle" font-size="30" font-weight="900" style="paint-order: stroke; stroke: rgba(15, 23, 42, 0.18); stroke-width: 3px;">{{ $segment["count"] }}</text>
                                        @endif
                                    </a>
                                @endforeach
                                @if ($singleSegment)
                                    <text x="{{ $centerX }}" y="{{ $centerY }}" fill="{{ $contrastTextColor($chartSegments[0]["color"]) }}" text-anchor="middle" dominant-baseline="middle" font-size="46" font-weight="900" style="paint-order: stroke; stroke: rgba(15, 23, 42, 0.18); stroke-width: 3px;">{{ $chartSegments[0]["count"] }}</text>
                                @endif
                            </svg>
                        </div>
                        <div class="text-center">
                            <h2 class="text-lg font-bold tracking-tight text-brand-secondary">{{ $card["name"] }}</h2>
                            <p class="mt-1 text-xs text-brand-secondary/65">{{ $card["totalOpenTickets"] }} incidencias abiertas</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

<section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">Tickets cerrados por usuario</h2>
    @if (empty($closedReportRows))
        <div class="mt-6 rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">No hay tickets cerrados ni clausurados para mostrar.</div>
    @else
        @php
            $closedChartWidth = max(1260, count($closedReportRows) * 190);
            $closedChartHeight = 290;
            $closedAxisY = 220;
            $closedMaxBarHeight = 145;
            $closedChartLeft = 70;
            $closedChartRight = 70;
            $closedChartInnerWidth = $closedChartWidth - $closedChartLeft - $closedChartRight;
        @endphp
        <div class="mt-6 overflow-x-auto">
            <svg viewBox="0 0 {{ $closedChartWidth }} {{ $closedChartHeight }}" class="mx-auto block h-auto" style="width: {{ $closedChartWidth }}px; max-width: 100%;" role="img" aria-label="Gráfica de tickets cerrados por usuario">
                <line x1="{{ $closedChartLeft }}" y1="{{ $closedAxisY }}" x2="{{ $closedChartWidth - $closedChartRight }}" y2="{{ $closedAxisY }}" stroke="#cbd5e1" stroke-width="2" />
                @foreach ($closedReportRows as $index => $row)
                    @php
                        $slotWidth = $closedChartInnerWidth / max(count($closedReportRows), 1);
                        $barWidth = min(62, max(30, $slotWidth * 0.38));
                        $barX = $closedChartLeft + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
                        $barHeight = $maxClosedTickets > 0 ? (($row["totalClosedTickets"] / $maxClosedTickets) * $closedMaxBarHeight) : 0;
                        $barTop = $closedAxisY - $barHeight;
                        $labelX = $barX + ($barWidth / 2);
                        $barRadius = min(8, $barHeight / 2);
                    @endphp
                    <g>
                        @if ($barHeight > 0)
                            <path d="M {{ $barX }} {{ $closedAxisY }} H {{ $barX + $barWidth }} V {{ $barTop + $barRadius }} A {{ $barRadius }} {{ $barRadius }} 0 0 0 {{ $barX + $barWidth - $barRadius }} {{ $barTop }} H {{ $barX + $barRadius }} A {{ $barRadius }} {{ $barRadius }} 0 0 0 {{ $barX }} {{ $barTop + $barRadius }} Z" fill="#E51A2E" />
                        @endif
                        <text x="{{ $labelX }}" y="{{ max(36, $barTop - 8) }}" text-anchor="middle" fill="#0f172a" font-size="22" font-weight="800">{{ $row["totalClosedTickets"] }}</text>
                        <text x="{{ $labelX }}" y="{{ $closedAxisY + 30 }}" text-anchor="middle" fill="#334155" font-size="16" font-weight="700">{{ $row["name"] }}</text>
                    </g>
                @endforeach
            </svg>
        </div>
    @endif
</section>

<section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">Tiempo medio de resolución</h2>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4"><p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Media total</p><p class="mt-2 text-3xl font-bold text-brand-secondary">{{ data_get($resolutionReport, "overallAverageLabel", "0 min") }}</p></div>
        <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4"><p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Tickets medidos</p><p class="mt-2 text-3xl font-bold text-brand-secondary">{{ data_get($resolutionReport, "totalResolvedTickets", 0) }}</p></div>
    </div>

    @if ($resolutionRows->isEmpty())
        <div class="mt-6 rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">No hay tickets cerrados o clausurados suficientes para calcular el tiempo medio.</div>
    @else
        @php
            $resolutionChartWidth = max(1260, count($resolutionChartRows) * 190);
            $resolutionChartHeight = 290;
            $resolutionAxisY = 220;
            $resolutionMaxBarHeight = 145;
            $resolutionChartLeft = 70;
            $resolutionChartRight = 70;
            $resolutionChartInnerWidth = $resolutionChartWidth - $resolutionChartLeft - $resolutionChartRight;
            $resolutionAverageColor = '#22c55e';
            $resolutionOverColor = '#E51A2E';
        @endphp

        <div class="mt-6 overflow-x-auto">
            <svg
                viewBox="0 0 {{ $resolutionChartWidth }} {{ $resolutionChartHeight }}"
                class="mx-auto block h-auto"
                style="width: {{ $resolutionChartWidth }}px; max-width: 100%;"
                role="img"
                aria-label="Gráfica de tiempo medio de resolución por usuario"
            >
                <line x1="{{ $resolutionChartLeft }}" y1="{{ $resolutionAxisY }}" x2="{{ $resolutionChartWidth - $resolutionChartRight }}" y2="{{ $resolutionAxisY }}" stroke="#cbd5e1" stroke-width="2" />

                @if ($resolutionOverallAverageMinutes > 0)
                    @php
                        $overallLineY = $resolutionAxisY - (($resolutionOverallAverageMinutes / max($resolutionMaxMinutes, 1)) * $resolutionMaxBarHeight);
                        $overallLineY = max(36, min($resolutionAxisY - 4, $overallLineY));
                    @endphp
                    <line x1="{{ $resolutionChartLeft }}" y1="{{ $overallLineY }}" x2="{{ $resolutionChartWidth - $resolutionChartRight }}" y2="{{ $overallLineY }}" stroke="#94a3b8" stroke-dasharray="6 6" stroke-width="2" />
                    <text x="{{ $resolutionChartWidth - 22 }}" y="{{ $overallLineY - 8 }}" text-anchor="end" fill="#475569" font-size="14" font-weight="700">Media total</text>
                @endif

                @foreach ($resolutionChartRows as $index => $row)
                    @php
                        $slotWidth = $resolutionChartInnerWidth / max(count($resolutionChartRows), 1);
                        $barWidth = min(62, max(30, $slotWidth * 0.38));
                        $barX = $resolutionChartLeft + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
                        $barHeight = $resolutionMaxMinutes > 0 ? (($row["averageMinutes"] / $resolutionMaxMinutes) * $resolutionMaxBarHeight) : 0;
                        $barTop = $resolutionAxisY - $barHeight;
                        $labelX = $barX + ($barWidth / 2);
                        $barRadius = min(8, $barHeight / 2);
                        $barColor = $row["averageMinutes"] <= $resolutionOverallAverageMinutes ? $resolutionAverageColor : $resolutionOverColor;
                    @endphp
                    <g>
                        @if ($barHeight > 0)
                            <path d="M {{ $barX }} {{ $resolutionAxisY }} H {{ $barX + $barWidth }} V {{ $barTop + $barRadius }} A {{ $barRadius }} {{ $barRadius }} 0 0 0 {{ $barX + $barWidth - $barRadius }} {{ $barTop }} H {{ $barX + $barRadius }} A {{ $barRadius }} {{ $barRadius }} 0 0 0 {{ $barX }} {{ $barTop + $barRadius }} Z" fill="{{ $barColor }}" />
                        @endif

                        <text x="{{ $labelX }}" y="{{ max(36, $barTop - 8) }}" text-anchor="middle" fill="#0f172a" font-size="22" font-weight="800">{{ $row["averageLabel"] }}</text>
                        <text x="{{ $labelX }}" y="{{ $resolutionAxisY + 30 }}" text-anchor="middle" fill="#334155" font-size="16" font-weight="700">{{ $row["name"] }}</text>
                    </g>
                @endforeach
            </svg>
        </div>
    @endif
</section>
<section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">Tickets por tipo de incidencia</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-brand-secondary/65">
                Este gráfico agrupa todos los tickets, sin importar su estado o la persona asignada. Pasa el cursor sobre cada segmento para ver a qué herramienta corresponde.
            </p>
        </div>
        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3 text-sm font-semibold text-brand-secondary/70">
            {{ $ticketToolReportTotal }} incidencias en total
        </div>
    </div>

    @if ($ticketToolReportRows->isEmpty())
        <div class="mt-6 rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">
            No hay tickets suficientes para mostrar el informe por tipo de incidencia.
        </div>
    @else
        @php
            $ticketToolSegments = [];
            $ticketToolSliceStart = 0.0;
            $ticketToolTotal = max($ticketToolReportRows->sum('total'), 1);
        @endphp

        <div
            class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
            x-data="{
                ticketToolTooltip: null,
                ticketToolTooltipVisible: false,
                ticketToolTooltipTimer: null,
                scheduleTicketToolTooltip(payload) {
                    clearTimeout(this.ticketToolTooltipTimer);
                    this.ticketToolTooltipTimer = setTimeout(() => {
                        this.ticketToolTooltip = payload;
                        this.ticketToolTooltipVisible = true;
                    }, 220);
                },
                hideTicketToolTooltip() {
                    clearTimeout(this.ticketToolTooltipTimer);
                    this.ticketToolTooltipTimer = null;
                    this.ticketToolTooltipVisible = false;
                    window.setTimeout(() => {
                        if (!this.ticketToolTooltipVisible) {
                            this.ticketToolTooltip = null;
                        }
                    }, 160);
                },
            }"
        >
            <div class="relative mx-auto w-full max-w-[20rem] shrink-0">
                <svg viewBox="0 0 280 280" class="h-auto w-full" role="img" aria-label="Gráfica de tickets por tipo de incidencia">
                    @foreach ($ticketToolReportRows as $row)
                        @php
                            $percentage = ($row['total'] / $ticketToolTotal) * 100;
                            $ticketToolSegments[] = [
                                'name' => $row['name'],
                                'total' => $row['total'],
                                'color' => $row['color'],
                                'start' => $ticketToolSliceStart,
                                'end' => $ticketToolSliceStart + ($percentage * 3.6),
                            ];
                            $ticketToolSliceStart += ($percentage * 3.6);
                        @endphp
                    @endforeach

                    @foreach ($ticketToolSegments as $segment)
                        @php
                            $slicePath = $describePieSlice(140, 140, 122, $segment['start'], $segment['end']);
                            $isSingleSegment = count($ticketToolSegments) === 1;
                            $strokeColor = $isSingleSegment ? 'none' : '#ffffff';
                            $strokeWidth = $isSingleSegment ? '0' : '1.25';
                        @endphp
                        <path
                            d="{{ $slicePath }}"
                            fill="{{ $segment['color'] }}"
                            stroke="{{ $strokeColor }}"
                            stroke-width="{{ $strokeWidth }}"
                            stroke-linejoin="round"
                            class="cursor-help"
                            @mouseenter="scheduleTicketToolTooltip({ name: @js($segment['name']), total: {{ $segment['total'] }} })"
                            @mousemove="hideTicketToolTooltip(); scheduleTicketToolTooltip({ name: @js($segment['name']), total: {{ $segment['total'] }} })"
                            @mouseleave="hideTicketToolTooltip()"
                        />
                    @endforeach
                </svg>

                <div
                    x-cloak
                    x-show="ticketToolTooltipVisible"
                    x-transition.opacity.duration.150ms
                    class="pointer-events-none absolute left-1/2 top-full z-50 mt-3 w-max max-w-[18rem] -translate-x-1/2 rounded-xl bg-brand-secondary px-3 py-2 text-xs font-semibold text-white shadow-lg"
                >
                    <div class="whitespace-nowrap" x-text="ticketToolTooltip?.name"></div>
                    <div class="mt-0.5 text-white/75" x-text="ticketToolTooltip ? `${ticketToolTooltip.total} ${ticketToolTooltip.total === 1 ? 'incidencia' : 'incidencias'}` : ''"></div>
                </div>
            </div>

            <div class="w-full max-w-[32rem] shrink-0 space-y-3 lg:ml-auto">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">Leyenda</p>
                <div class="grid grid-cols-2 gap-x-10 gap-y-2 lg:grid-cols-3">
                    @foreach ($ticketToolReportRows as $row)
                        <div class="flex items-center gap-3 py-1.5">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <span class="h-3.5 w-3.5 shrink-0 rounded-full" style="background-color: {{ $row['color'] }}"></span>
                                <span class="truncate text-sm font-medium leading-tight text-brand-secondary">{{ $row['name'] }}</span>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-brand-secondary/65">{{ $row['total'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>

<section class="mt-6 rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">Tickets por delegaciones</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-brand-secondary/65">
                Este gráfico agrupa todos los tickets por la delegación de la persona que los abrió, para detectar qué ubicaciones generan más carga de trabajo.
            </p>
        </div>
        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3 text-sm font-semibold text-brand-secondary/70">
            {{ $dealershipReportTotal }} incidencias en total
        </div>
    </div>

    @if ($dealershipReportRows->isEmpty())
        <div class="mt-6 rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">
            No hay tickets suficientes para mostrar el informe por delegaciones.
        </div>
    @else
        @php
            $dealershipChartRows = $dealershipReportRows->sortByDesc('totalTickets')->values();
            $dealershipMaxTickets = max((int) $dealershipChartRows->max('totalTickets'), 1);
        @endphp

        <div class="mt-6 space-y-3">
            @foreach ($dealershipChartRows as $row)
                @php
                    $barPercentage = ($row['totalTickets'] / $dealershipMaxTickets) * 100;
                @endphp
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-44 shrink-0 truncate font-semibold text-brand-secondary">{{ $row['name'] }}</span>
                    <div class="h-3.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#E51A2E]" style="width: {{ max(3, $barPercentage) }}%;" aria-hidden="true"></div>
                    </div>
                    <span class="w-10 shrink-0 text-right font-bold text-brand-secondary/70">{{ $row['totalTickets'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</section>
</main>
@endsection




