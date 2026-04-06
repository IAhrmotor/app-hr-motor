@php
    $entries = $leaderboard['entries'];
    $entryItems = $leaderboard['entryItems'];
    $topEntries = $leaderboard['topEntries'];
    $entryMovements = $leaderboard['entryMovements'];
    $topEntryMovements = $leaderboard['topEntryMovements'];
    $search = $leaderboard['search'];
    $hasLeaderboardData = $leaderboard['hasLeaderboardData'];
    $searchParam = $leaderboard['searchParam'];
    $pageParam = $leaderboard['pageParam'];
    $routeName = $leaderboard['routeName'];
    $persistedQuery = request()->except([$searchParam, $pageParam]);
    $theme = $leaderboard['theme'];
    $isHot = $theme === 'hot';

    $themeStyles = $isHot
        ? [
            'section' => 'border-amber-200/70 bg-[radial-gradient(circle_at_top,rgba(251,191,36,0.12),transparent_34%),linear-gradient(180deg,rgba(255,251,235,0.9),rgba(255,255,255,0.96))]',
            'eyebrow' => 'text-amber-700',
            'search' => 'focus:border-amber-400 focus:bg-white',
            'button' => 'bg-brand-secondary text-white hover:brightness-110',
            'pill' => 'border-amber-300/60 bg-amber-100/80 text-amber-800',
            'metric' => 'text-amber-700',
            'tableMetric' => 'text-amber-700',
            'headerLine' => 'from-amber-300/60 via-orange-300/50 to-transparent',
            'topAura' => 'shadow-[0_7px_0_0_rgba(217,167,34,0.24)]',
            'topCard' => 'border-amber-200/70 bg-[linear-gradient(180deg,rgba(255,251,235,0.98),rgba(255,255,255,1))]',
            'rankBadge' => 'bg-gradient-to-r from-amber-500 via-orange-400 to-amber-300 text-amber-950',
            'avatar' => 'from-amber-500/20 to-orange-500/10 text-amber-800 ring-amber-300/50',
            'tableRow' => 'hover:bg-amber-50/40',
            'miniBadge' => 'border-amber-200/70 bg-amber-50 text-amber-800',
        ]
        : [
            'section' => 'border-sky-200/70 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.11),transparent_34%),linear-gradient(180deg,rgba(240,249,255,0.92),rgba(255,255,255,0.96))]',
            'eyebrow' => 'text-sky-700',
            'search' => 'focus:border-sky-400 focus:bg-white',
            'button' => 'bg-brand-secondary text-white hover:brightness-110',
            'pill' => 'border-sky-300/60 bg-sky-100/80 text-sky-800',
            'metric' => 'text-sky-700',
            'tableMetric' => 'text-sky-700',
            'headerLine' => 'from-sky-300/60 via-cyan-300/50 to-transparent',
            'topAura' => 'shadow-[0_7px_0_0_rgba(14,116,144,0.22)]',
            'topCard' => 'border-sky-200/70 bg-[linear-gradient(180deg,rgba(240,249,255,0.98),rgba(255,255,255,1))]',
            'rankBadge' => 'bg-gradient-to-r from-sky-600 via-cyan-500 to-sky-300 text-white',
            'avatar' => 'from-sky-500/20 to-cyan-500/10 text-sky-800 ring-sky-300/50',
            'tableRow' => 'hover:bg-sky-50/45',
            'miniBadge' => 'border-sky-200/70 bg-sky-50 text-sky-800',
        ];

    $movementPillBaseClasses = 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1';
    $movementPillCompactClasses = 'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ring-1';
    $movementIconClasses = 'h-3.5 w-3.5 shrink-0';
    $vehicleTitle = static fn ($entry) => $entry->vehicle_commercial_name ?: $entry->vehicle_name;
    $vehicleImageAlt = static fn ($entry) => 'Imagen de '.$vehicleTitle($entry);
    $tableEntries = $topEntries->isNotEmpty()
        ? $entryItems->reject(fn ($entry) => $topEntries->contains('id', $entry->id))->values()
        : $entryItems;
@endphp

<section class="rounded-[1.85rem] border p-4 sm:p-6 {{ $themeStyles['section'] }}">
    <div class="relative overflow-hidden rounded-[1.6rem] border border-white/70 bg-white/80 p-4 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-5">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r {{ $themeStyles['headerLine'] }}"></div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.32em] {{ $themeStyles['eyebrow'] }}">
                    {{ $isHot ? 'Alta tracción' : 'Baja tracción' }}
                </p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-brand-secondary">
                    {{ $leaderboard['title'] }}
                </h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-brand-secondary/68">
                    {{ $leaderboard['description'] }}
                </p>
            </div>

            <span class="inline-flex w-fit shrink-0 rounded-full border px-3 py-1 text-xs font-semibold sm:self-start lg:inline-flex {{ $themeStyles['pill'] }}">
                Top 10
            </span>
        </div>

        @if ($hasLeaderboardData)
            <form method="GET" action="{{ route($routeName) }}"
                class="mt-5 rounded-[1.4rem] border border-brand-secondary/10 bg-white/90 p-3.5 shadow-[0_10px_24px_rgba(15,23,42,0.04)] sm:p-4">
                @foreach ($persistedQuery as $queryKey => $queryValue)
                    @if (is_array($queryValue))
                        @foreach ($queryValue as $nestedValue)
                            <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                    @endif
                @endforeach

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-secondary/35"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="{{ $searchParam }}" value="{{ $search }}"
                            placeholder="{{ $leaderboard['searchPlaceholder'] }}"
                            class="w-full rounded-2xl border border-brand-secondary/10 bg-slate-50 py-3 pl-12 pr-4 text-sm text-brand-secondary outline-none transition {{ $themeStyles['search'] }}">
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold transition sm:w-auto {{ $themeStyles['button'] }}">
                            Buscar
                        </button>
                        @if ($search !== '')
                            <a href="{{ route($routeName, $persistedQuery) }}"
                                class="inline-flex w-full items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5 sm:w-auto">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        @endif

        @if ($entryItems->isEmpty())
            <div class="mt-5 rounded-[1.6rem] border border-dashed border-brand-secondary/15 bg-white/60 px-6 py-12 text-center text-brand-secondary/75">
                @if ($hasLeaderboardData && $search !== '')
                    <p class="text-lg font-semibold text-brand-secondary">No hay resultados para tu búsqueda</p>
                    <p class="mt-2 text-sm">Prueba con otro modelo o versión.</p>
                @else
                    <p class="text-lg font-semibold text-brand-secondary">{{ $leaderboard['emptyTitle'] }}</p>
                    <p class="mt-2 text-sm">{{ $emptyDescription }}</p>
                @endif
            </div>
        @else
            @if ($topEntries->isNotEmpty())
                <div class="mt-6 grid auto-rows-fr gap-4">
                    @foreach ($topEntries as $entry)
                        @php
                            $movement = $topEntryMovements[$entry->id] ?? ['direction' => 'same', 'amount' => 0, 'label' => 'Se mantiene igual que ayer'];
                        @endphp
                        <article class="relative flex h-full flex-col overflow-hidden rounded-[1.7rem] border p-4 {{ $themeStyles['topCard'] }} {{ $themeStyles['topAura'] }} sm:min-h-[15.5rem] sm:p-5">
                            <div class="flex items-start justify-between gap-3 sm:absolute sm:right-4 sm:top-4 sm:justify-start">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $themeStyles['rankBadge'] }}">
                                    #{{ $entry->ranking_position }}
                                </span>
                                @if ($movement['direction'] === 'up')
                                    <span class="{{ $movementPillCompactClasses }} bg-emerald-100 text-emerald-800 ring-emerald-200" title="{{ $movement['label'] }}">
                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                        </svg>
                                        <span>{{ $movement['amount'] }}</span>
                                    </span>
                                @elseif ($movement['direction'] === 'down')
                                    <span class="{{ $movementPillCompactClasses }} bg-red-100 text-red-700 ring-red-200" title="{{ $movement['label'] }}">
                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                        </svg>
                                        <span>{{ $movement['amount'] }}</span>
                                    </span>
                                @else
                                    <span class="{{ $movementPillCompactClasses }} justify-center bg-slate-200 text-slate-600 ring-slate-300" title="{{ $movement['label'] }}">
                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                        </svg>
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-1 flex-col justify-between gap-5">
                                <div class="min-w-0 sm:pr-24">
                                    <div class="flex items-start gap-3 sm:block">
                                        @if ($entry->vehicle_image_url)
                                            <button
                                                type="button"
                                                @click="$dispatch('open-vehicle-image', { src: @js($entry->vehicle_image_url), alt: @js($vehicleImageAlt($entry)), title: @js($vehicleTitle($entry)) })"
                                                class="group relative block shrink-0 cursor-pointer overflow-hidden rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                                aria-label="Abrir imagen de {{ $vehicleTitle($entry) }}"
                                            >
                                                <img src="{{ $entry->vehicle_image_url }}" alt="{{ $vehicleImageAlt($entry) }}"
                                                    class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 transition duration-300 group-hover:scale-105 group-hover:brightness-75 {{ $themeStyles['avatar'] }}">
                                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-2xl bg-brand-secondary/0 text-[10px] font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                                                    Ver
                                                </span>
                                            </button>
                                        @else
                                            <div class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br ring-1 {{ $themeStyles['avatar'] }}">
                                                <x-icons.car class="h-7 w-7" />
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-lg font-semibold leading-tight text-brand-secondary sm:mt-4 sm:max-w-xl sm:text-xl">
                                                {{ $vehicleTitle($entry) }}
                                            </p>
                                            @if ($entry->vehicle_plate)
                                                <p class="mt-1 text-sm font-medium text-brand-secondary/55">{{ $entry->vehicle_plate }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-brand-secondary/45">Leads</p>
                                    <p class="mt-2 text-3xl font-semibold {{ $themeStyles['metric'] }}">
                                        {{ number_format((int) $entry->total_leads, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($tableEntries->isNotEmpty())
            <div class="mt-6 overflow-hidden rounded-[1.6rem] border border-brand-secondary/10 bg-white/92">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-4 sm:px-6">
                    <div class="hidden grid-cols-[110px_minmax(0,1fr)_110px] gap-6 md:grid">
                        <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Puesto</div>
                        <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Vehículo</div>
                        <div class="text-right text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Leads</div>
                    </div>
                    <div class="md:hidden text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">{{ $leaderboard['title'] }}</div>
                </div>

                <div>
                    @foreach ($tableEntries as $entry)
                        @php
                            $movement = $entryMovements[$entry->id] ?? ['direction' => 'same', 'amount' => 0, 'label' => 'Se mantiene igual que ayer'];
                        @endphp
                        <div class="border-b border-slate-100 px-4 py-4 transition last:border-b-0 sm:px-6 {{ $themeStyles['tableRow'] }}">
                            <div class="hidden grid-cols-[110px_minmax(0,1fr)_110px] items-center gap-6 md:grid">
                                <div class="flex items-center gap-3 text-sm font-semibold text-brand-secondary">
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $themeStyles['miniBadge'] }}">
                                        #{{ $entry->ranking_position }}
                                    </span>
                                    @if ($movement['direction'] === 'up')
                                        <span class="{{ $movementPillBaseClasses }} bg-emerald-100 text-emerald-800 ring-emerald-200" title="{{ $movement['label'] }}">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                            </svg>
                                            <span>{{ $movement['amount'] }}</span>
                                        </span>
                                    @elseif ($movement['direction'] === 'down')
                                        <span class="{{ $movementPillBaseClasses }} bg-red-100 text-red-700 ring-red-200" title="{{ $movement['label'] }}">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                            </svg>
                                            <span>{{ $movement['amount'] }}</span>
                                        </span>
                                    @else
                                        <span class="{{ $movementPillBaseClasses }} justify-center bg-slate-200 text-slate-600 ring-slate-300" title="{{ $movement['label'] }}">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                            </svg>
                                        </span>
                                    @endif
                                </div>

                                <div class="flex min-w-0 items-center gap-3">
                                    @if ($entry->vehicle_image_url)
                                        <button
                                            type="button"
                                            @click="$dispatch('open-vehicle-image', { src: @js($entry->vehicle_image_url), alt: @js($vehicleImageAlt($entry)), title: @js($vehicleTitle($entry)) })"
                                            class="group relative block shrink-0 cursor-pointer overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                            aria-label="Abrir imagen de {{ $vehicleTitle($entry) }}"
                                        >
                                            <img src="{{ $entry->vehicle_image_url }}" alt="{{ $vehicleImageAlt($entry) }}"
                                                class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 transition duration-300 group-hover:scale-105 group-hover:brightness-75 {{ $themeStyles['avatar'] }}">
                                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-xl bg-brand-secondary/0 text-[9px] font-semibold uppercase tracking-[0.16em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                                                Ver
                                            </span>
                                        </button>
                                    @else
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ring-1 {{ $themeStyles['avatar'] }}">
                                            <x-icons.car class="h-5 w-5" />
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-brand-secondary">{{ $vehicleTitle($entry) }}</p>
                                        @if ($entry->vehicle_plate)
                                            <p class="truncate text-xs text-brand-secondary/55">{{ $entry->vehicle_plate }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-right text-sm font-semibold {{ $themeStyles['tableMetric'] }}">
                                    {{ number_format((int) $entry->total_leads, 0, ',', '.') }}
                                </div>
                            </div>

                            <div class="space-y-3 md:hidden">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 text-sm font-semibold text-brand-secondary">
                                        <span>#{{ $entry->ranking_position }}</span>
                                        @if ($movement['direction'] === 'up')
                                            <span class="{{ $movementPillCompactClasses }} bg-emerald-100 text-emerald-800 ring-emerald-200">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                                </svg>
                                                <span>{{ $movement['amount'] }}</span>
                                            </span>
                                        @elseif ($movement['direction'] === 'down')
                                            <span class="{{ $movementPillCompactClasses }} bg-red-100 text-red-700 ring-red-200">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                                </svg>
                                                <span>{{ $movement['amount'] }}</span>
                                            </span>
                                        @else
                                            <span class="{{ $movementPillCompactClasses }} justify-center bg-slate-200 text-slate-600 ring-slate-300">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-secondary/45">Leads</p>
                                        <p class="mt-1 text-lg font-semibold {{ $themeStyles['tableMetric'] }}">{{ number_format((int) $entry->total_leads, 0, ',', '.') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    @if ($entry->vehicle_image_url)
                                        <button
                                            type="button"
                                            @click="$dispatch('open-vehicle-image', { src: @js($entry->vehicle_image_url), alt: @js($vehicleImageAlt($entry)), title: @js($vehicleTitle($entry)) })"
                                            class="group relative block shrink-0 cursor-pointer overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                            aria-label="Abrir imagen de {{ $vehicleTitle($entry) }}"
                                        >
                                            <img src="{{ $entry->vehicle_image_url }}" alt="{{ $vehicleImageAlt($entry) }}"
                                                class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 transition duration-300 group-hover:scale-105 group-hover:brightness-75 {{ $themeStyles['avatar'] }}">
                                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-xl bg-brand-secondary/0 text-[9px] font-semibold uppercase tracking-[0.16em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                                                Ver
                                            </span>
                                        </button>
                                    @else
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ring-1 {{ $themeStyles['avatar'] }}">
                                            <x-icons.car class="h-5 w-5" />
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold leading-snug text-brand-secondary">{{ $vehicleTitle($entry) }}</p>
                                        @if ($entry->vehicle_plate)
                                            <p class="mt-1 text-xs text-brand-secondary/55">{{ $entry->vehicle_plate }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if ($entries->hasPages())
                <div class="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-brand-secondary/65">
                        Mostrando del {{ $entries->firstItem() }} al {{ $entries->lastItem() }} de {{ $entries->total() }} coches.
                    </p>

                    <nav class="flex items-center gap-2" aria-label="Paginacion del ranking">
                        @if ($entries->onFirstPage())
                            <span class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-slate-100 px-4 py-2 text-sm font-semibold text-brand-secondary/35">Anterior</span>
                        @else
                            <a href="{{ $entries->previousPageUrl() }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                Anterior
                            </a>
                        @endif

                        <span class="inline-flex items-center justify-center rounded-2xl bg-brand-secondary px-4 py-2 text-sm font-semibold text-white">
                            Pagina {{ $entries->currentPage() }} de {{ $entries->lastPage() }}
                        </span>

                        @if ($entries->hasMorePages())
                            <a href="{{ $entries->nextPageUrl() }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                Siguiente
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-slate-100 px-4 py-2 text-sm font-semibold text-brand-secondary/35">Siguiente</span>
                        @endif
                    </nav>
                </div>
            @endif
        @endif
    </div>
</section>
