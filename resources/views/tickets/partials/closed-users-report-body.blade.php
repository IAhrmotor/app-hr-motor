@php
    $closedReportRows = collect($closedReportRows ?? []);
    $maxClosedTickets = (int) ($closedReportRows->max('totalClosedTickets') ?? 0);
@endphp

@if ($closedReportRows->isEmpty())
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
