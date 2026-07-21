@php
    $dealershipReportRows = collect($dealershipReportRows ?? []);
@endphp

@if ($dealershipReportRows->isEmpty())
    <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4 text-sm text-brand-secondary/65">
        No hay tickets suficientes para mostrar el informe por delegaciones.
    </div>
@else
    @php
        $dealershipChartRows = $dealershipReportRows->sortByDesc('totalTickets')->values();
        $dealershipMaxTickets = max((int) $dealershipChartRows->max('totalTickets'), 1);
    @endphp

    <div class="space-y-3">
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
