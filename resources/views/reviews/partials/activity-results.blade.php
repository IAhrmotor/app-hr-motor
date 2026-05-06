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
            action="{{ route('reviews.index') }}"
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

            <p class="text-xs text-brand-secondary/45">
                Los filtros se aplican en tiempo real y la tabla se actualiza sin recargar la p&aacute;gina.
            </p>
        </form>
    </div>

    <div class="border-t border-gray-100" data-reviews-results>
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
