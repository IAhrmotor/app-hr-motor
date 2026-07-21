@php
    $resolutionRows = collect(data_get($resolutionReport ?? [], 'rows', []));
    $resolutionChartRows = $resolutionRows->sortBy('averageMinutes')->values();
    $resolutionMaxMinutes = (int) ($resolutionRows->max('averageMinutes') ?? 0);
    $resolutionOverallAverageMinutes = (int) data_get($resolutionReport, 'overallAverageMinutes', 0);
@endphp

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
