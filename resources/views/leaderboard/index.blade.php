@extends('layouts.app')

@section('content')
    <section class="bg-[radial-gradient(circle_at_top,_rgba(229,26,46,0.14),_transparent_45%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex flex-col gap-6 rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur sm:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-brand-primary">Salesforce</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-secondary sm:text-4xl">
                            Ranking comercial
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-brand-secondary/70 sm:text-base">
                            Ranking de comerciales por número de ventas del mes sincronizado desde Salesforce.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary/80">
                        <p class="font-semibold">Estado</p>
                        <p class="mt-1">
                            @if ($connection)
                                Conectado con Salesforce
                            @elseif ($salesforceConfigReady)
                                Pendiente de autorizar la conexion en Salesforce
                            @else
                                Configuracion de Salesforce pendiente
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-brand-secondary/60">
                            @if ($connection?->last_synced_at)
                                Ultima sincronizacion: {{ $connection->last_synced_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            @elseif ($salesforceConfigReady)
                                La app esta preparada, pero aun no se ha completado el OAuth.
                            @else
                                Faltan credenciales o la URL de callback en este entorno.
                            @endif
                        </p>
                    </div>
                </div>

                @if (session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if (! $leaderboardTablesReady)
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm leading-6 text-sky-900">
                        El ranking esta en modo preparacion. La pagina ya no falla aunque falten tablas, pero todavia necesitas ejecutar las migraciones para guardar la conexion y las ventas.
                    </div>
                @endif

                @auth
                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                        @if (! $connection || ! $leaderboardTablesReady)
                            <div class="flex flex-col gap-3 sm:flex-row">
                                @if ($salesforceConfigReady && $leaderboardTablesReady)
                                    <a href="{{ route('salesforce.connect') }}"
                                        class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:brightness-95">
                                        Conectar Salesforce
                                    </a>
                                @else
                                    <span
                                        class="inline-flex items-center justify-center rounded-2xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-500">
                                        Configuracion pendiente
                                    </span>
                                @endif

                                <form method="POST" action="{{ route('leaderboard.sync') }}">
                                    @csrf
                                    <button type="submit"
                                        @disabled(! $connection || ! $leaderboardTablesReady)
                                        class="inline-flex w-full items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5 sm:w-auto">
                                        Sincronizar ahora
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if (! $leaderboardTablesReady)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                Antes de conectar Salesforce, ejecuta las migraciones de Laravel para crear las tablas nuevas del ranking.
                            </div>
                        @elseif (! $salesforceConfigReady)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                La integracion esta en modo espera. La app no falla, pero no intentara conectar ni sincronizar hasta que completes
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_ID</code>,
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_SECRET</code>
                                y
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_REDIRECT_URI</code>.
                            </div>
                        @elseif (! $connection)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                En Salesforce Connected App debes autorizar exactamente la misma callback URL que uses aqui en
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">{{ config('services.salesforce.redirect_uri') }}</code>.
                                Hasta que eso ocurra, la web seguira funcionando y el ranking quedara simplemente pendiente de conexion.
                            </div>
                        @endif
                    @endif
                @endauth

                @if ($hasLeaderboardData)
                    <form method="GET" action="{{ route('leaderboard.index') }}"
                        class="rounded-[1.75rem] border border-brand-secondary/10 bg-white p-4 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center">
                            <div class="relative flex-1">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-brand-secondary/35"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" name="search" value="{{ $search }}"
                                    placeholder="Buscar comercial, email o ID de Salesforce"
                                    class="w-full rounded-2xl border border-brand-secondary/10 bg-slate-50 py-3 pl-12 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:bg-white">
                            </div>
                            <div class="flex gap-3">
                                <button type="submit"
                                    class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-secondary px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">
                                    Buscar
                                </button>
                                @if ($search !== '')
                                    <a href="{{ route('leaderboard.index') }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
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

                @if ($entries->isEmpty())
                    <div class="rounded-[2rem] border border-dashed border-brand-secondary/15 bg-slate-50 px-6 py-12 text-center text-brand-secondary/75">
                        @if ($hasLeaderboardData && $search !== '')
                            <p class="text-lg font-semibold text-brand-secondary">No hay resultados para tu busqueda</p>
                            <p class="mt-2 text-sm">
                                Prueba con otro nombre, email o ID de Salesforce.
                            </p>
                        @else
                            <p class="text-lg font-semibold text-brand-secondary">Aun no hay datos de ventas</p>
                            <p class="mt-2 text-sm">
                                @if (! $leaderboardTablesReady)
                                    Ejecuta primero las migraciones para activar el almacenamiento del ranking.
                                @elseif ($connection)
                                    Ejecuta una sincronizacion para llenar el ranking.
                                @elseif ($salesforceConfigReady)
                                    Completa la autorizacion OAuth en Salesforce y despues ejecuta la primera sincronizacion.
                                @else
                                    Completa la configuracion de Salesforce y despues autoriza la conexion.
                                @endif
                            </p>
                        @endif
                    </div>
                @else
                    @if ($topEntries->isNotEmpty())
                        <div class="grid gap-4 lg:grid-cols-3">
                            @foreach ($topEntries as $entry)
                                @php
                                    $movement = $topEntryMovements[$entry->id] ?? ['direction' => 'same', 'amount' => 0, 'label' => 'Se mantiene igual que ayer'];
                                    $medalStyles = match ($entry->ranking_position) {
                                        1 => [
                                            'card' => 'border-yellow-300/80 bg-[linear-gradient(180deg,rgba(255,248,214,0.98),rgba(255,255,255,1))] shadow-[0_20px_40px_rgba(217,167,34,0.18)]',
                                            'badge' => 'bg-gradient-to-r from-yellow-500 via-amber-400 to-yellow-300 text-amber-950',
                                            'ring' => 'ring-yellow-300/70',
                                            'accent' => 'text-amber-600',
                                        ],
                                        2 => [
                                            'card' => 'border-slate-300/80 bg-[linear-gradient(180deg,rgba(241,245,249,0.98),rgba(255,255,255,1))] shadow-[0_20px_40px_rgba(100,116,139,0.15)]',
                                            'badge' => 'border border-slate-300/80 bg-[linear-gradient(135deg,#64748b_0%,#e2e8f0_50%,#94a3b8_100%)] text-slate-900',
                                            'ring' => 'ring-slate-300/80',
                                            'accent' => 'text-slate-500',
                                        ],
                                        default => [
                                            'card' => 'border-orange-300/80 bg-[linear-gradient(180deg,rgba(255,237,213,0.98),rgba(255,255,255,1))] shadow-[0_20px_40px_rgba(180,83,9,0.14)]',
                                            'badge' => 'bg-gradient-to-r from-orange-700 via-amber-700 to-orange-300 text-white',
                                            'ring' => 'ring-orange-300/80',
                                            'accent' => 'text-orange-600',
                                        ],
                                    };
                                @endphp

                                <article class="relative overflow-hidden rounded-[1.75rem] border p-6 {{ $medalStyles['card'] }}">
                                    <div class="absolute right-4 top-4 flex items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $medalStyles['badge'] }}">
                                            #{{ $entry->ranking_position }}
                                        </span>
                                        @if ($movement['direction'] === 'up')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200" title="{{ $movement['label'] }}">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                                </svg>
                                                <span>{{ $movement['amount'] }}</span>
                                                <span class="sr-only">{{ $movement['label'] }}</span>
                                            </span>
                                        @elseif ($movement['direction'] === 'down')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200" title="{{ $movement['label'] }}">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                                </svg>
                                                <span>{{ $movement['amount'] }}</span>
                                                <span class="sr-only">{{ $movement['label'] }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-300" title="{{ $movement['label'] }}">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                                </svg>
                                                <span class="sr-only">{{ $movement['label'] }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4">
                                        @if ($entry->user && auth()->check() && in_array(auth()->user()->role, ['admin', 'gestor']))
                                            <a href="{{ route('users.show', $entry->user) }}"
                                                class="flex items-center gap-4 rounded-2xl transition hover:opacity-90">
                                                <img src="{{ $entry->user->avatar_url }}"
                                                    alt="Avatar de {{ $entry->user->name }}"
                                                    class="h-16 w-16 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                                                <div>
                                                    <p class="text-xl font-semibold text-brand-secondary hover:text-brand-primary">{{ $entry->user->name }}</p>
                                                    <p class="text-sm text-brand-secondary/60">
                                                        {{ $entry->user->email }}
                                                    </p>
                                                </div>
                                            </a>
                                        @else
                                            <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                                alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                                class="h-16 w-16 rounded-2xl object-cover ring-2 {{ $medalStyles['ring'] }}">
                                            <div>
                                                <p class="text-xl font-semibold text-brand-secondary">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                                <p class="text-sm text-brand-secondary/60">
                                                    {{ $entry->user?->email ?? ($entry->salesforce_user_id ?: 'Sin vincular con usuario interno') }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                    <p class="mt-6 text-sm uppercase tracking-[0.3em] text-brand-secondary/50">Ventas</p>
                                    <p class="mt-2 text-3xl font-semibold {{ $medalStyles['accent'] }}">
                                        {{ number_format((float) $entry->total_sales, 0, ',', '.') }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-[1.75rem] border border-brand-secondary/10 bg-white">
                        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                            <div class="hidden grid-cols-[220px_minmax(0,1.2fr)_minmax(0,1fr)_100px] gap-6 md:grid">
                                <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Puesto</div>
                                <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Comercial</div>
                                <div class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">ID Salesforce</div>
                                <div class="text-right text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Ventas</div>
                            </div>
                            <div class="md:hidden text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">
                                Ranking de comerciales
                            </div>
                        </div>

                        <div class="overflow-y-auto overflow-x-hidden" style="max-height: 32rem;">
                            @foreach ($entries as $entry)
                                @php
                                    $rowBadge = match ($entry->ranking_position) {
                                        1 => [
                                            'pill' => 'border-yellow-300/80 bg-[linear-gradient(135deg,#f59e0b_0%,#fde68a_55%,#fff7cc_100%)] text-amber-950',
                                            'marker' => 'bg-yellow-400',
                                        ],
                                        2 => [
                                            'pill' => 'border-slate-300/80 bg-[linear-gradient(135deg,#64748b_0%,#e2e8f0_55%,#f8fafc_100%)] text-slate-900',
                                            'marker' => 'bg-slate-400',
                                        ],
                                        3 => [
                                            'pill' => 'border-orange-300/80 bg-[linear-gradient(135deg,#c2410c_0%,#fdba74_55%,#ffedd5_100%)] text-orange-950',
                                            'marker' => 'bg-orange-400',
                                        ],
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
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200" title="{{ $movement['label'] }}">
                                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                                        </svg>
                                                        <span>{{ $movement['amount'] }}</span>
                                                        <span class="sr-only">{{ $movement['label'] }}</span>
                                                    </span>
                                                @elseif ($movement['direction'] === 'down')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200" title="{{ $movement['label'] }}">
                                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                                        </svg>
                                                        <span>{{ $movement['amount'] }}</span>
                                                        <span class="sr-only">{{ $movement['label'] }}</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-300" title="{{ $movement['label'] }}">
                                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                                        </svg>
                                                        <span class="sr-only">{{ $movement['label'] }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div>
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                                    alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                                    class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                                @if ($entry->user && auth()->check() && in_array(auth()->user()->role, ['admin', 'gestor']))
                                                    <a href="{{ route('users.show', $entry->user) }}" class="transition hover:text-brand-primary">
                                                        <p class="text-sm font-semibold text-brand-secondary">{{ $entry->user->name }}</p>
                                                        <p class="text-xs text-brand-secondary/55">{{ $entry->user->email }}</p>
                                                    </a>
                                                @else
                                                    <div>
                                                        <p class="text-sm font-semibold text-brand-secondary">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                                        <p class="text-xs text-brand-secondary/55">{{ $entry->user?->email ?? 'Sin usuario interno enlazado' }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="truncate text-sm text-brand-secondary/70">{{ $entry->salesforce_user_id ?: 'No informado' }}</div>

                                        <div class="text-right text-sm font-semibold text-brand-primary">
                                            {{ number_format((float) $entry->total_sales, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div class="space-y-3 md:hidden">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 text-sm font-semibold text-brand-secondary">
                                                <span>#{{ $entry->ranking_position }}</span>
                                                @if ($movement['direction'] === 'up')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">
                                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18V6m0 0-5 5m5-5 5 5" />
                                                        </svg>
                                                        <span>{{ $movement['amount'] }}</span>
                                                    </span>
                                                @elseif ($movement['direction'] === 'down')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">
                                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m0 0-5-5m5 5 5-5" />
                                                        </svg>
                                                        <span>{{ $movement['amount'] }}</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-300">
                                                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
                                                        </svg>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-secondary/45">Ventas</p>
                                                <p class="mt-1 text-lg font-semibold text-brand-primary">{{ number_format((float) $entry->total_sales, 0, ',', '.') }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                                alt="Avatar de {{ $entry->user?->name ?? $entry->seller_name }}"
                                                class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-brand-secondary">{{ $entry->user?->name ?? $entry->seller_name }}</p>
                                                <p class="truncate text-xs text-brand-secondary/55">{{ $entry->user?->email ?? 'Sin usuario interno enlazado' }}</p>
                                                <p class="truncate text-xs text-brand-secondary/55">{{ $entry->salesforce_user_id ?: 'No informado' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <script>
        window.setTimeout(() => window.location.reload(), 600000);
    </script>
@endsection
