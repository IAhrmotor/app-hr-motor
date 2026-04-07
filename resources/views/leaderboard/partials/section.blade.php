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
    $entityLabelPlural = $entityLabelPlural ?? 'comerciales';
    $searchPlaceholder = $searchPlaceholder ?? 'Buscar comercial, email o delegacion';
    $aggregateByDealership = $aggregateByDealership ?? false;
    $dealershipImageClasses = 'h-16 w-16 rounded-2xl object-cover ring-2';
    $dealershipFallbackClasses = 'flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-secondary text-lg font-semibold text-white ring-2';
    $dealershipRowImageClasses = 'h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10';
    $dealershipRowFallbackClasses = 'flex h-11 w-11 items-center justify-center rounded-xl bg-brand-secondary text-sm font-semibold text-white ring-1 ring-brand-secondary/10';
    $movementPillBaseClasses = 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1';
    $movementPillCompactClasses = 'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ring-1';
    $movementIconClasses = 'h-3.5 w-3.5 shrink-0';
    $storeManagerBadgeClasses = 'inline-flex items-center rounded-full border border-amber-300/70 bg-amber-100/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-800';
@endphp

<section class="rounded-[1.85rem] border border-brand-secondary/10 bg-white/90 p-5 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-6">
    @if ($hasLeaderboardData)
        <form method="GET" action="{{ route($routeName) }}"
            class="rounded-[1.75rem] border border-brand-secondary/10 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
            @foreach ($persistedQuery as $queryKey => $queryValue)
                @if (is_array($queryValue))
                    @foreach ($queryValue as $nestedValue)
                        <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                @endif
            @endforeach

            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-secondary/35"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="{{ $searchParam }}" value="{{ $search }}"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full rounded-2xl border border-brand-secondary/10 bg-slate-50 py-3 pl-12 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white">
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit"
                        class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-brand-secondary px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110 sm:w-auto">
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
            @if ($search !== '')
                <p class="mt-3 text-sm text-brand-secondary/65">
                    Mostrando resultados para <span class="font-semibold text-brand-secondary">"{{ $search }}"</span> con su posicion real en el ranking.
                </p>
            @endif
        </form>
    @endif

    @if ($entryItems->isEmpty())
        <div class="rounded-[2rem] border border-dashed border-brand-secondary/15 bg-slate-50 px-6 py-12 text-center text-brand-secondary/75">
            @if ($hasLeaderboardData && $search !== '')
                <p class="text-lg font-semibold text-brand-secondary">No hay resultados para tu busqueda</p>
                <p class="mt-2 text-sm">
                    {{ $aggregateByDealership ? 'Prueba con otra delegación.' : 'Prueba con otro nombre, email o delegación.' }}
                </p>
            @else
                <p class="text-lg font-semibold text-brand-secondary">{{ $emptyTitle }}</p>
                <p class="mt-2 text-sm">{{ $emptyDescription }}</p>
            @endif
        </div>
    @else
        @if ($topEntries->isNotEmpty())
            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                @foreach ($topEntries as $entry)
                    @php
                        $movement = $topEntryMovements[$entry->id] ?? ['direction' => 'same', 'amount' => 0, 'label' => 'Se mantiene igual que ayer'];
                        $topEntryHref = null;
                        if ($aggregateByDealership && $entry->dealership_id) {
                            $topEntryHref = route('dealerships.show', $entry->dealership_id);
                        } elseif ($entry->user && auth()->check() && in_array(auth()->user()->role, ['admin', 'gestor'])) {
                            $topEntryHref = route('users.show', $entry->user);
                        }
                        $medalStyles = match ($entry->ranking_position) {
                            1 => [
                                'card' => 'border-yellow-300/80 bg-[linear-gradient(180deg,rgba(255,248,214,0.98),rgba(255,255,255,1))] shadow-[0_7px_0_0_rgba(217,167,34,0.24)]',
                                'badge' => 'bg-gradient-to-r from-yellow-500 via-amber-400 to-yellow-300 text-amber-950',
                                'ring' => 'ring-yellow-300/70',
                                'accent' => 'text-amber-600',
                            ],
                            2 => [
                                'card' => 'border-slate-300/80 bg-[linear-gradient(180deg,rgba(241,245,249,0.98),rgba(255,255,255,1))] shadow-[0_7px_0_0_rgba(100,116,139,0.22)]',
                                'badge' => 'border border-slate-300/80 bg-[linear-gradient(135deg,#64748b_0%,#e2e8f0_50%,#94a3b8_100%)] text-slate-900',
                                'ring' => 'ring-slate-300/80',
                                'accent' => 'text-slate-500',
                            ],
                            default => [
                                'card' => 'border-orange-300/80 bg-[linear-gradient(180deg,rgba(255,237,213,0.98),rgba(255,255,255,1))] shadow-[0_7px_0_0_rgba(180,83,9,0.22)]',
                                'badge' => 'bg-gradient-to-r from-orange-700 via-amber-700 to-orange-300 text-white',
                                'ring' => 'ring-orange-300/80',
                                'accent' => 'text-orange-600',
                            ],
                        };
                    @endphp

                    @if ($topEntryHref)
                        <a href="{{ $topEntryHref }}"
                            class="group block h-full rounded-[1.75rem] transition duration-200 hover:-translate-y-1">
                    @endif
                    <article class="grid min-h-[11rem] grid-cols-[minmax(0,1fr)_auto] grid-rows-[auto_1fr] gap-x-3 gap-y-3 overflow-hidden rounded-[1.75rem] border p-4 transition duration-200 sm:relative sm:flex sm:h-full sm:min-h-0 sm:flex-col sm:p-6 {{ $medalStyles['card'] }}">
                        <div class="flex items-center gap-2 self-start sm:absolute sm:right-4 sm:top-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold sm:px-3 {{ $medalStyles['badge'] }}">
                                #{{ $entry->ranking_position }}
                            </span>
                            @if ($movement['direction'] === 'up')
                                <span class="{{ $movementPillCompactClasses }} bg-emerald-100 text-emerald-800 ring-emerald-200" title="{{ $movement['label'] }}">
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                    </svg>
                                    <span>{{ $movement['amount'] }}</span>
                                    <span class="sr-only">{{ $movement['label'] }}</span>
                                </span>
                            @elseif ($movement['direction'] === 'down')
                                <span class="{{ $movementPillCompactClasses }} bg-red-100 text-red-700 ring-red-200" title="{{ $movement['label'] }}">
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                    </svg>
                                    <span>{{ $movement['amount'] }}</span>
                                    <span class="sr-only">{{ $movement['label'] }}</span>
                                </span>
                            @else
                                <span class="{{ $movementPillCompactClasses }} justify-center bg-slate-200 text-slate-600 ring-slate-300" title="{{ $movement['label'] }}">
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                    </svg>
                                    <span class="sr-only">{{ $movement['label'] }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="justify-self-end self-start sm:hidden">
                            @if ($aggregateByDealership)
                                @if ($entry->dealership_image_url)
                                    <img src="{{ $entry->dealership_image_url }}"
                                        alt="Imagen de {{ $entry->dealership_name }}"
                                        class="h-14 w-14 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-secondary text-sm font-semibold text-white ring-2 {{ $medalStyles['ring'] }}">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($entry->dealership_name, 0, 2)) }}
                                    </div>
                                @endif
                            @else
                                <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                    alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                    class="h-14 w-14 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                            @endif
                        </div>

                        <div class="min-w-0 self-end sm:hidden">
                            @if ($aggregateByDealership)
                                <p class="line-clamp-2 text-lg font-semibold leading-tight text-brand-secondary {{ $topEntryHref ? 'transition group-hover:text-brand-primary' : '' }}">{{ $entry->dealership_name }}</p>
                                <p class="truncate text-sm text-brand-secondary/60">
                                    {{ $entry->commercial_count }} {{ (int) $entry->commercial_count === 1 ? 'comercial' : 'comerciales' }}
                                </p>
                            @else
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="line-clamp-2 text-lg font-semibold leading-tight {{ $entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER ? 'text-amber-700' : 'text-brand-secondary' }} {{ $topEntryHref ? 'transition group-hover:text-brand-primary' : '' }}">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                    @if ($entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER)
                                        <span class="{{ $storeManagerBadgeClasses }}">Jefe de tienda</span>
                                    @endif
                                </div>
                                <p class="truncate text-sm text-brand-secondary/60">
                                    {{ $entry->user?->dealership ?: 'Sin delegación asignada' }}
                                </p>
                            @endif
                        </div>

                        <div class="text-right self-end sm:hidden">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-brand-secondary/50">{{ $metricLabel }}</p>
                            <p class="mt-1 text-2xl font-semibold {{ $medalStyles['accent'] }}">
                                {{ number_format((float) $entry->{$metricField}, 0, ',', '.') }}
                            </p>
                        </div>

                        <div class="hidden grid flex-1 grid-cols-[auto_minmax(0,1fr)] items-start gap-4 pr-24 sm:grid">
                            @if ($aggregateByDealership)
                                @if ($entry->dealership_image_url)
                                    <img src="{{ $entry->dealership_image_url }}"
                                        alt="Imagen de {{ $entry->dealership_name }}"
                                        class="{{ $dealershipImageClasses }} {{ $medalStyles['ring'] }}">
                                @else
                                    <div class="{{ $dealershipFallbackClasses }} {{ $medalStyles['ring'] }}">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($entry->dealership_name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0 max-w-full">
                                    <p class="text-xl font-semibold leading-tight break-words text-brand-secondary {{ $topEntryHref ? 'transition group-hover:text-brand-primary' : '' }}">{{ $entry->dealership_name }}</p>
                                    <p class="text-sm text-brand-secondary/60">
                                        {{ $entry->commercial_count }} {{ (int) $entry->commercial_count === 1 ? 'comercial' : 'comerciales' }}
                                    </p>
                                </div>
                            @elseif ($entry->user && auth()->check() && in_array(auth()->user()->role, ['admin', 'gestor']))
                                    <img src="{{ $entry->user->avatar_url }}"
                                        alt="Avatar de {{ $entry->user->name }}"
                                        class="h-16 w-16 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                                    <div class="min-w-0 max-w-full">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-xl font-semibold leading-tight break-words {{ $entry->user->role === \App\Models\User::ROLE_STORE_MANAGER ? 'text-amber-700' : 'text-brand-secondary' }} {{ $topEntryHref ? 'transition group-hover:text-brand-primary' : '' }}">{{ $entry->user->name }}</p>
                                            @if ($entry->user->role === \App\Models\User::ROLE_STORE_MANAGER)
                                                <span class="{{ $storeManagerBadgeClasses }}">Jefe de tienda</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-brand-secondary/60">{{ $entry->user->dealership ?: 'Sin delegación asignada' }}</p>
                                    </div>
                            @else
                                <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                    alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                    class="h-16 w-16 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                                <div class="min-w-0 max-w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-xl font-semibold leading-tight break-words {{ $entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER ? 'text-amber-700' : 'text-brand-secondary' }}">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                        @if ($entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER)
                                            <span class="{{ $storeManagerBadgeClasses }}">Jefe de tienda</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-brand-secondary/60">
                                        {{ $entry->user?->dealership ?: 'Sin delegación asignada' }}
                                    </p>
                                </div>
                            @endif
                        </div>
                        <p class="hidden mt-6 text-sm uppercase tracking-[0.3em] text-brand-secondary/50 sm:block">{{ $metricLabel }}</p>
                        <p class="hidden mt-2 text-3xl font-semibold sm:block {{ $medalStyles['accent'] }}">
                            {{ number_format((float) $entry->{$metricField}, 0, ',', '.') }}
                        </p>
                    </article>
                    @if ($topEntryHref)
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-brand-secondary/10 bg-white">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                <div class="hidden grid-cols-[220px_minmax(0,1.2fr)_minmax(0,1fr)_100px] gap-6 md:grid">
                    <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Puesto</div>
                    <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">{{ $aggregateByDealership ? 'Delegación' : 'Comercial' }}</div>
                    <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">{{ $aggregateByDealership ? 'Equipo' : 'Delegación' }}</div>
                    <div class="text-right text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">{{ $metricLabel }}</div>
                </div>
                <div class="md:hidden text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">{{ $title }}</div>
            </div>

            <div>
                @foreach ($entryItems as $entry)
                    @php
                        $rowBadge = match ($entry->ranking_position) {
                            1 => ['pill' => 'border-yellow-300/80 bg-[linear-gradient(135deg,#f59e0b_0%,#fde68a_55%,#fff7cc_100%)] text-amber-950', 'marker' => 'bg-yellow-400'],
                            2 => ['pill' => 'border-slate-300/80 bg-[linear-gradient(135deg,#64748b_0%,#e2e8f0_55%,#f8fafc_100%)] text-slate-900', 'marker' => 'bg-slate-400'],
                            3 => ['pill' => 'border-orange-300/80 bg-[linear-gradient(135deg,#c2410c_0%,#fdba74_55%,#ffedd5_100%)] text-orange-950', 'marker' => 'bg-orange-400'],
                            default => null,
                        };
                        $movement = $entryMovements[$entry->id] ?? ['direction' => 'same', 'amount' => 0, 'label' => 'Se mantiene igual que ayer'];
                    @endphp

                    <div class="border-b border-slate-100 px-6 py-4 transition hover:bg-slate-50/80 last:border-b-0">
                        <div class="hidden grid-cols-[220px_minmax(0,1.2fr)_minmax(0,1fr)_100px] items-center gap-6 md:grid">
                            <div class="text-sm font-semibold text-brand-secondary">
                                <div class="flex items-center gap-3">
                                    @if ($rowBadge)
                                        <span class="h-8 w-1.5 rounded-full {{ $rowBadge['marker'] }}"></span>
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $rowBadge['pill'] }}">
                                            #{{ $entry->ranking_position }}
                                        </span>
                                    @else
                                        <span>#{{ $entry->ranking_position }}</span>
                                    @endif
                                    @if ($movement['direction'] === 'up')
                                        <span class="{{ $movementPillBaseClasses }} bg-emerald-100 text-emerald-800 ring-emerald-200" title="{{ $movement['label'] }}">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                            </svg>
                                            <span>{{ $movement['amount'] }}</span>
                                            <span class="sr-only">{{ $movement['label'] }}</span>
                                        </span>
                                    @elseif ($movement['direction'] === 'down')
                                        <span class="{{ $movementPillBaseClasses }} bg-red-100 text-red-700 ring-red-200" title="{{ $movement['label'] }}">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                            </svg>
                                            <span>{{ $movement['amount'] }}</span>
                                            <span class="sr-only">{{ $movement['label'] }}</span>
                                        </span>
                                    @else
                                        <span class="{{ $movementPillBaseClasses }} justify-center bg-slate-200 text-slate-600 ring-slate-300" title="{{ $movement['label'] }}">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="{{ $movementIconClasses }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                            </svg>
                                            <span class="sr-only">{{ $movement['label'] }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                @if ($aggregateByDealership)
                                    @php
                                        $dealershipHref = $entry->dealership_id ? route('dealerships.show', $entry->dealership_id) : null;
                                    @endphp
                                    @if ($dealershipHref)
                                        <a href="{{ $dealershipHref }}" class="flex items-center gap-3 rounded-2xl transition hover:opacity-90">
                                            @if ($entry->dealership_image_url)
                                                <img src="{{ $entry->dealership_image_url }}"
                                                    alt="Imagen de {{ $entry->dealership_name }}"
                                                    class="{{ $dealershipRowImageClasses }}">
                                            @else
                                                <div class="{{ $dealershipRowFallbackClasses }}">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($entry->dealership_name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-semibold text-brand-secondary hover:text-brand-primary">{{ $entry->dealership_name }}</p>
                                                <p class="text-xs text-brand-secondary/55">Ranking por delegación</p>
                                            </div>
                                        </a>
                                    @else
                                        <div class="flex items-center gap-3">
                                            @if ($entry->dealership_image_url)
                                                <img src="{{ $entry->dealership_image_url }}"
                                                    alt="Imagen de {{ $entry->dealership_name }}"
                                                    class="{{ $dealershipRowImageClasses }}">
                                            @else
                                                <div class="{{ $dealershipRowFallbackClasses }}">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($entry->dealership_name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-semibold text-brand-secondary">{{ $entry->dealership_name }}</p>
                                                <p class="text-xs text-brand-secondary/55">Ranking por delegación</p>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                            alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                            class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                        @if ($entry->user && auth()->check() && in_array(auth()->user()->role, ['admin', 'gestor']))
                                            <a href="{{ route('users.show', $entry->user) }}" class="transition hover:text-brand-primary">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold {{ $entry->user->role === \App\Models\User::ROLE_STORE_MANAGER ? 'text-amber-700' : 'text-brand-secondary' }}">{{ $entry->user->name }}</p>
                                                    @if ($entry->user->role === \App\Models\User::ROLE_STORE_MANAGER)
                                                        <span class="{{ $storeManagerBadgeClasses }}">Jefe de tienda</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-brand-secondary/55">{{ $entry->user->email }}</p>
                                            </a>
                                        @else
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold {{ $entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER ? 'text-amber-700' : 'text-brand-secondary' }}">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                                    @if ($entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER)
                                                        <span class="{{ $storeManagerBadgeClasses }}">Jefe de tienda</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-brand-secondary/55">{{ $entry->user?->email ?? 'Sin usuario interno enlazado' }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="truncate text-sm text-brand-secondary/70">
                                @if ($aggregateByDealership)
                                    {{ $entry->commercial_count }} {{ (int) $entry->commercial_count === 1 ? 'comercial' : 'comerciales' }}
                                @else
                                    {{ $entry->user?->dealership ?: 'Sin delegación asignada' }}
                                @endif
                            </div>

                            <div class="text-right text-sm font-semibold text-brand-primary">
                                {{ number_format((float) $entry->{$metricField}, 0, ',', '.') }}
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
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-secondary/45">{{ $metricLabel }}</p>
                                    <p class="mt-1 text-lg font-semibold text-brand-primary">{{ number_format((float) $entry->{$metricField}, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                @if ($aggregateByDealership)
                                    @php
                                        $dealershipHref = $entry->dealership_id ? route('dealerships.show', $entry->dealership_id) : null;
                                    @endphp
                                    @if ($dealershipHref)
                                        <a href="{{ $dealershipHref }}" class="flex min-w-0 items-center gap-3 rounded-2xl transition hover:opacity-90">
                                            @if ($entry->dealership_image_url)
                                                <img src="{{ $entry->dealership_image_url }}"
                                                    alt="Imagen de {{ $entry->dealership_name }}"
                                                    class="{{ $dealershipRowImageClasses }}">
                                            @else
                                                <div class="{{ $dealershipRowFallbackClasses }}">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($entry->dealership_name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-brand-secondary hover:text-brand-primary">{{ $entry->dealership_name }}</p>
                                                <p class="truncate text-xs text-brand-secondary/55">{{ $entry->commercial_count }} {{ (int) $entry->commercial_count === 1 ? 'comercial' : 'comerciales' }}</p>
                                                <p class="truncate text-xs text-brand-secondary/55">Ranking por delegación</p>
                                            </div>
                                        </a>
                                    @else
                                        @if ($entry->dealership_image_url)
                                            <img src="{{ $entry->dealership_image_url }}"
                                                alt="Imagen de {{ $entry->dealership_name }}"
                                                class="{{ $dealershipRowImageClasses }}">
                                        @else
                                            <div class="{{ $dealershipRowFallbackClasses }}">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($entry->dealership_name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-brand-secondary">{{ $entry->dealership_name }}</p>
                                            <p class="truncate text-xs text-brand-secondary/55">{{ $entry->commercial_count }} {{ (int) $entry->commercial_count === 1 ? 'comercial' : 'comerciales' }}</p>
                                            <p class="truncate text-xs text-brand-secondary/55">Ranking por delegación</p>
                                        </div>
                                    @endif
                                @else
                                    <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                        alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                        class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold {{ $entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER ? 'text-amber-700' : 'text-brand-secondary' }}">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                            @if ($entry->user?->role === \App\Models\User::ROLE_STORE_MANAGER)
                                                <span class="{{ $storeManagerBadgeClasses }}">Jefe de tienda</span>
                                            @endif
                                        </div>
                                        <p class="truncate text-xs text-brand-secondary/55">{{ $entry->user?->email ?? 'Sin usuario interno enlazado' }}</p>
                                        <p class="truncate text-xs text-brand-secondary/55">{{ $entry->user?->dealership ?: 'Sin delegación asignada' }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($entries->hasPages())
            <div class="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-brand-secondary/65">
                    Mostrando del {{ $entries->firstItem() }} al {{ $entries->lastItem() }} de {{ $entries->total() }} {{ $entityLabelPlural }}.
                </p>

                <nav class="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-start" aria-label="Paginacion del ranking">
                    @if ($entries->onFirstPage())
                        <span class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-slate-100 px-4 py-2 text-sm font-semibold text-brand-secondary/35">Anterior</span>
                    @else
                        <a href="{{ $entries->previousPageUrl() }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Anterior
                        </a>
                    @endif

                    <span class="inline-flex items-center justify-center rounded-2xl bg-brand-secondary px-4 py-2 text-sm font-semibold text-white">
                        <span class="sm:hidden">{{ $entries->currentPage() }} de {{ $entries->lastPage() }}</span>
                        <span class="hidden sm:inline">Página {{ $entries->currentPage() }} de {{ $entries->lastPage() }}</span>
                        <span class="sr-only">Pagina {{ $entries->currentPage() }} de {{ $entries->lastPage() }}</span>
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
</section>
