@php
    $ticketToolReportRows = collect($ticketToolReportRows ?? []);
    $ticketToolTotal = max($ticketToolReportRows->sum('total'), 1);
    $ticketToolSegments = [];
    $ticketToolSliceStart = 0.0;
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
@endphp

@if ($ticketToolReportRows->isEmpty())
    <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">
        No hay tickets suficientes para mostrar el informe por tipo de incidencia.
    </div>
@else
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

    <div
        class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
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
