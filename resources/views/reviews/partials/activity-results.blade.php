<div class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-6 py-6 lg:px-8">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-brand-secondary">Filtros y actividad reciente</h2>
            <p class="mt-2 text-sm leading-6 text-gray-500">
                Busca por texto, delegaci&oacute;n, estado, puntuaci&oacute;n o rango de fechas sin recargar la p&aacute;gina.
            </p>
        </div>

        <form
            method="GET"
            action="{{ $filterAction ?? route('reviews.all') }}"
            class="space-y-4"
            data-reviews-filter-form
        >
            <input type="hidden" name="ajax" value="1">

            <div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50/80 p-3 sm:p-4">
                <div class="grid gap-3 xl:grid-cols-[minmax(0,1.6fr)_minmax(220px,0.9fr)]">
                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                        <label for="reviews-table-search" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                            Buscar
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                id="reviews-table-search"
                                type="text"
                                name="search"
                                value="{{ $filters['search'] ?? '' }}"
                                placeholder="Cliente, rese&ntilde;a, delegaci&oacute;n..."
                                class="h-11 w-full rounded-xl border-gray-200 bg-white pl-12 pr-4 text-sm text-brand-secondary placeholder:text-gray-400 transition cursor-text focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                            >
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                        <label for="reviews-table-sort" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                            Orden
                        </label>
                        <select
                            id="reviews-table-sort"
                            name="sort"
                            class="h-11 w-full rounded-xl border-gray-200 bg-white px-4 text-sm text-brand-secondary transition hover:cursor-pointer focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                        >
                            <option value="">M&aacute;s recientes</option>
                            <option value="rating_desc" @selected(($filters['sort'] ?? '') === 'rating_desc')>Mejor valoradas</option>
                            <option value="rating_asc" @selected(($filters['sort'] ?? '') === 'rating_asc')>Peor valoradas</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-[minmax(240px,280px)_minmax(0,1fr)_minmax(180px,220px)]">
                    <div class="space-y-3">
                        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                            <label for="reviews-table-dealership" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                                Delegaci&oacute;n
                            </label>
                            <select
                                id="reviews-table-dealership"
                                name="dealership_id"
                                class="h-11 w-full rounded-xl border-gray-200 bg-white px-4 text-sm text-brand-secondary transition hover:cursor-pointer focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                            >
                                <option value="">Todas las delegaciones</option>
                                @foreach ($dealerships as $dealership)
                                    <option value="{{ $dealership->id }}" @selected((string) ($filters['dealership_id'] ?? '') === (string) $dealership->id)>
                                        {{ $dealership->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                            <label for="reviews-table-status" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                                Estado
                            </label>
                            <select
                                id="reviews-table-status"
                                name="status"
                                class="h-11 w-full rounded-xl border-gray-200 bg-white px-4 text-sm text-brand-secondary transition hover:cursor-pointer focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                            >
                                <option value="">Todas</option>
                                <option value="answered" @selected(($filters['status'] ?? '') === 'answered')>Respondidas</option>
                                <option value="unanswered" @selected(($filters['status'] ?? '') === 'unanswered')>Sin responder</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid min-h-[8.25rem] grid-rows-[auto_1fr] rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2.5 transition">
                        <div data-display-range-label="" class="text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                            Rango de fechas
                        </div>

                        <div class="mt-1.5 grid h-full gap-2 sm:grid-cols-2">
                            <button
                                type="button"
                                data-date-trigger="from"
                                class="relative inline-flex h-full min-h-0 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-transparent bg-transparent px-4 py-2 text-sm font-semibold text-brand-secondary outline-none transition hover:cursor-pointer hover:bg-brand-secondary/5 focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/20"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v2.25m7.5-2.25v2.25M3.75 8.25h16.5M4.5 5.25h15A2.25 2.25 0 0121.75 7.5v11.25A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V7.5A2.25 2.25 0 014.5 5.25z"></path>
                                </svg>
                                <span data-display-date-from="">Desde</span>
                                <input
                                    type="date"
                                    name="date_from"
                                    value="{{ $filters['date_from'] ?? '' }}"
                                    data-date-input="from"
                                    max="{{ $filters['date_to'] ?? '' }}"
                                    class="pointer-events-none absolute inset-0 opacity-0"
                                    tabindex="-1"
                                >
                            </button>

                            <button
                                type="button"
                                data-date-trigger="to"
                                class="relative inline-flex h-full min-h-0 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-transparent bg-transparent px-4 py-2 text-sm font-semibold text-brand-secondary outline-none transition hover:cursor-pointer hover:bg-brand-secondary/5 focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/20"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v2.25m7.5-2.25v2.25M3.75 8.25h16.5M4.5 5.25h15A2.25 2.25 0 0121.75 7.5v11.25A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V7.5A2.25 2.25 0 014.5 5.25z"></path>
                                </svg>
                                <span data-display-date-to="">Hasta</span>
                                <input
                                    type="date"
                                    name="date_to"
                                    value="{{ $filters['date_to'] ?? '' }}"
                                    data-date-input="to"
                                    min="{{ $filters['date_from'] ?? '' }}"
                                    class="pointer-events-none absolute inset-0 opacity-0"
                                    tabindex="-1"
                                >
                            </button>
                        </div>
                    </div>

                    <div class="flex h-full flex-col gap-2.5 self-stretch">
                        <button
                            type="submit"
                            class="inline-flex flex-1 cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3.5 text-left text-sm font-semibold text-white transition hover:cursor-pointer hover:bg-brand-primary/90"
                            data-reviews-apply
                        >
                            <span class="block">
                                <span class="block text-[10px] font-semibold uppercase tracking-[0.28em] text-white/80">Aplicar</span>
                                <span class="mt-1 block text-base">Filtrar</span>
                            </span>
                        </button>
                        <button
                            type="button"
                            class="inline-flex flex-1 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 py-3.5 text-left text-sm font-semibold text-brand-secondary transition hover:cursor-pointer hover:border-brand-primary/30 hover:text-brand-primary"
                            data-reviews-reset
                        >
                            <span class="block">
                                <span class="block text-[10px] font-semibold uppercase tracking-[0.28em] text-brand-secondary/40">Reset</span>
                                <span class="mt-1 block text-base">Limpiar</span>
                            </span>
                        </button>
                        <span class="text-sm text-gray-500" data-reviews-loading hidden>
                            Actualizando...
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @php
        $ratingDistribution = $reviewsRatingDistribution ?? ['total' => 0, 'red' => 0, 'orange' => 0, 'green' => 0, 'red_percent' => 0, 'orange_percent' => 0, 'green_percent' => 0];
        $ratingTotal = (int) ($ratingDistribution['total'] ?? 0);
        $ratingRed = (int) ($ratingDistribution['red'] ?? 0);
        $ratingOrange = (int) ($ratingDistribution['orange'] ?? 0);
        $ratingGreen = (int) ($ratingDistribution['green'] ?? 0);
        $ratingRedPercent = (float) ($ratingDistribution['red_percent'] ?? 0);
        $ratingOrangePercent = (float) ($ratingDistribution['orange_percent'] ?? 0);
        $ratingGreenPercent = (float) ($ratingDistribution['green_percent'] ?? 0);
        $ratingChartStyle = $ratingTotal > 0
            ? sprintf(
                'background: conic-gradient(#ef4444 0%% %s%%, #f59e0b %s%% %s%%, #16a34a %s%% 100%%);',
                $ratingRedPercent,
                $ratingRedPercent,
                $ratingRedPercent + $ratingOrangePercent,
                $ratingRedPercent + $ratingOrangePercent
            )
            : 'background: conic-gradient(#e5e7eb 0% 100%);';
    @endphp

    <div class="border-t border-gray-100 px-6 py-6 lg:px-8" data-reviews-results>
        <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative h-40 w-40 shrink-0 rounded-full" style="{{ $ratingChartStyle }}">
                        <div class="absolute inset-8 rounded-full bg-white shadow-inner"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-brand-secondary">{{ number_format($ratingTotal, 0, ',', '.') }}</p>
                                <p class="text-[9px] font-semibold uppercase tracking-[0.22em] text-brand-secondary/45">Filtradas</p>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-secondary/45">Distribuci&oacute;n</p>
                                <h3 class="mt-1 text-lg font-semibold text-brand-secondary">Valoraciones filtradas</h3>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <div class="flex items-center gap-2 rounded-full border border-red-100 bg-red-50 px-3 py-2">
                                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                                <span class="text-sm font-semibold text-red-700">1 y 2 estrellas</span>
                                <span class="text-sm font-bold text-red-700">{{ number_format($ratingRed, 0, ',', '.') }}</span>
                                <span class="text-xs text-red-600/80">{{ $ratingRedPercent }}%</span>
                            </div>

                            <div class="flex items-center gap-2 rounded-full border border-amber-100 bg-amber-50 px-3 py-2">
                                <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                                <span class="text-sm font-semibold text-amber-700">3 estrellas</span>
                                <span class="text-sm font-bold text-amber-700">{{ number_format($ratingOrange, 0, ',', '.') }}</span>
                                <span class="text-xs text-amber-600/80">{{ $ratingOrangePercent }}%</span>
                            </div>

                            <div class="flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-2">
                                <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                                <span class="text-sm font-semibold text-emerald-700">4 y 5 estrellas</span>
                                <span class="text-sm font-bold text-emerald-700">{{ number_format($ratingGreen, 0, ',', '.') }}</span>
                                <span class="text-xs text-emerald-600/80">{{ $ratingGreenPercent }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-brand-secondary">Tabla filtrada</h2>
                <p class="text-sm text-gray-500">La tabla se actualiza sin recargar y la dona refleja exactamente el resultado visible.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Delegaci&oacute;n</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Cliente</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Puntuaci&oacute;n</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Rese&ntilde;a</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Respuesta</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Fecha</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($reviews as $review)
                            @php
                                $reviewLocationLabel = $review->dealership?->name ?? $review->location_title ?? 'Sin asignar';
                            @endphp
                            <tr class="align-top">
                                <td class="px-6 py-5 text-sm font-semibold text-brand-secondary">
                                    {{ $reviewLocationLabel }}
                                </td>
                                <td class="px-6 py-5 text-sm text-brand-secondary/75">
                                    {{ $review->reviewer_name ?? 'An&oacute;nimo' }}
                                </td>
                                <td class="px-6 py-5 text-sm text-brand-secondary/75">
                                    <span class="font-semibold text-brand-secondary">{{ $review->rating ?? 0 }}</span>/5
                                </td>
                                <td class="px-6 py-5 text-sm text-brand-secondary/75">
                                    <p class="max-w-xl line-clamp-2">{{ $review->comment ?? 'Sin texto' }}</p>
                                </td>
                                <td class="px-6 py-5 text-sm text-brand-secondary/75">
                                    @if ($review->reply_comment)
                                        <p class="max-w-xl line-clamp-2">{{ $review->reply_comment }}</p>
                                    @else
                                        <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Sin responder</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-sm text-brand-secondary/60">
                                    {{ $review->review_created_at?->format('d/m/Y H:i') ?? $review->created_at?->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-brand-secondary/65">
                                    No hay rese&ntilde;as con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reviews->hasPages())
                <div class="border-t border-gray-100 px-4 py-4" data-reviews-pagination>
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
