@extends('layouts.app')

@section('content')
    <section class="py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            @php
                $visibleRole = app_visible_role(auth()->user());
            @endphp
            <div class="flex flex-col gap-6 rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur sm:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-brand-primary">Salesforce</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-secondary sm:text-4xl">
                            {{ $title }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-brand-secondary/70 sm:text-base">
                            {{ $description }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-end">
                        @if ($dealershipLeaderboard)
                            <a href="#ranking-delegaciones"
                                class="inline-flex w-full items-center gap-3 rounded-2xl border border-brand-secondary/10 bg-white px-4 py-3 text-left text-sm text-brand-secondary transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:bg-brand-primary/[0.03] sm:w-fit sm:max-w-[210px]">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-primary/10 text-brand-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15m-15 5.25h10.5m-10.5 5.25h6" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                                        Ir a
                                    </span>
                                    <span class="mt-0.5 block font-semibold text-brand-secondary">
                                        Ranking por delegaciones
                                    </span>
                                </span>
                            </a>
                        @endif

                        @if ($zoneLeaderboard)
                            <a href="#ranking-zonas"
                                class="inline-flex w-full items-center gap-3 rounded-2xl border border-brand-secondary/10 bg-white px-4 py-3 text-left text-sm text-brand-secondary transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:bg-brand-primary/[0.03] sm:w-fit sm:max-w-[210px]">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-primary/10 text-brand-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15m-15 5.25h10.5m-10.5 5.25h6" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                                        Ir a
                                    </span>
                                    <span class="mt-0.5 block font-semibold text-brand-secondary">
                                        Ranking por zonas
                                    </span>
                                </span>
                            </a>
                        @endif

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
                        El ranking esta en modo preparacion. La pagina ya no falla aunque falten tablas, pero todavia necesitas ejecutar las migraciones para guardar la conexion y los datos de este ranking.
                    </div>
                @endif

                @auth
                    @if (in_array($visibleRole, ['admin', 'gestor'], true))
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
                                Antes de conectar Salesforce, ejecuta las migraciones de Laravel para crear las tablas nuevas de este ranking.
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
                                Hasta que eso ocurra, la web seguira funcionando y este ranking quedara pendiente de conexion.
                            </div>
                        @endif
                    @endif
                @endauth

                @include('leaderboard.partials.section', [
                    'leaderboard' => $leaderboard,
                    'eyebrow' => $eyebrow,
                    'title' => $title,
                    'description' => $description,
                    'metricLabel' => $metricLabel,
                    'metricField' => $metricField,
                    'emptyTitle' => $emptyTitle,
                    'emptyDescription' => $emptyDescription,
                    'entityLabelPlural' => $entityLabelPlural,
                    'searchPlaceholder' => $searchPlaceholder,
                    'aggregateType' => 'user',
                ])

                @if ($dealershipLeaderboard)
                    <div id="ranking-delegaciones" class="scroll-mt-28">
                        @include('leaderboard.partials.section', [
                            'leaderboard' => $dealershipLeaderboard,
                            'eyebrow' => $eyebrow,
                            'title' => $dealershipTitle,
                            'description' => $dealershipDescription,
                            'metricLabel' => $metricLabel,
                            'metricField' => $metricField,
                            'emptyTitle' => $dealershipEmptyTitle,
                            'emptyDescription' => $emptyDescription,
                            'entityLabelPlural' => 'delegaciones',
                            'searchPlaceholder' => 'Buscar delegacion',
                            'aggregateType' => 'dealership',
                        ])
                    </div>
                @endif

                @if ($zoneLeaderboard)
                    <div id="ranking-zonas" class="scroll-mt-28">
                        @include('leaderboard.partials.section', [
                            'leaderboard' => $zoneLeaderboard,
                            'eyebrow' => $eyebrow,
                            'title' => $zoneTitle,
                            'description' => $zoneDescription,
                            'metricLabel' => $metricLabel,
                            'metricField' => $metricField,
                            'emptyTitle' => $zoneEmptyTitle,
                            'emptyDescription' => $emptyDescription,
                            'entityLabelPlural' => 'zonas',
                            'searchPlaceholder' => 'Buscar zona',
                            'aggregateType' => 'zone',
                        ])
                    </div>
                @endif
            </div>
        </div>

    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const states = new Map();

            const getRootState = (root) => {
                const stateKey = root.dataset.leaderboardSearchParam || 'leaderboard';

                if (!states.has(stateKey)) {
                    states.set(stateKey, {
                        timeout: null,
                        controller: null,
                        lastRequestKey: '',
                    });
                }

                return states.get(stateKey);
            };

            const getRoot = (element) => element.closest('[data-leaderboard-root]');

            const setLoadingState = (root, isLoading) => {
                const loading = root.querySelector('[data-leaderboard-loading]');
                const results = root.querySelector('[data-leaderboard-results]');

                if (loading) {
                    loading.classList.toggle('hidden', !isLoading);
                }

                if (results) {
                    results.classList.toggle('hidden', isLoading);
                }
            };

            const buildRequestUrl = (root, page = 1) => {
                const form = root.querySelector('[data-leaderboard-search-form]');

                if (!form) {
                    return null;
                }

                const requestUrl = new URL(form.action, window.location.origin);
                const currentUrl = new URL(window.location.href);
                const searchParam = root.dataset.leaderboardSearchParam;
                const pageParam = root.dataset.leaderboardPageParam;
                const searchInput = form.querySelector(`input[name="${searchParam}"]`);
                const search = searchInput?.value.trim() ?? '';

                currentUrl.searchParams.forEach((value, key) => {
                    if (key === searchParam || key === pageParam || key === 'ajax' || key === 'section') {
                        return;
                    }

                    requestUrl.searchParams.set(key, value);
                });

                if (search !== '') {
                    requestUrl.searchParams.set(searchParam, search);
                } else {
                    requestUrl.searchParams.delete(searchParam);
                }

                if (Number(page) > 1) {
                    requestUrl.searchParams.set(pageParam, page);
                } else {
                    requestUrl.searchParams.delete(pageParam);
                }

                requestUrl.searchParams.set('ajax', '1');
                requestUrl.searchParams.set('section', root.dataset.leaderboardSection || 'leaderboard');

                return requestUrl;
            };

            const updateHistory = (requestUrl) => {
                const historyUrl = new URL(requestUrl.toString());
                historyUrl.searchParams.delete('ajax');
                historyUrl.searchParams.delete('section');
                window.history.replaceState({}, '', historyUrl.toString());
            };

            const loadSection = async (root, { page = 1 } = {}) => {
                const state = getRootState(root);
                const requestUrl = buildRequestUrl(root, page);
                const scrollPosition = {
                    left: window.scrollX,
                    top: window.scrollY,
                };

                if (!requestUrl) {
                    return;
                }

                const requestKey = requestUrl.searchParams.toString();

                if (requestKey === state.lastRequestKey) {
                    return;
                }

                state.lastRequestKey = requestKey;

                if (state.controller) {
                    state.controller.abort();
                }

                const controller = new AbortController();
                state.controller = controller;

                const activeElement = document.activeElement;
                const shouldRestoreFocus = activeElement && root.contains(activeElement) && activeElement.matches('[data-leaderboard-search-form] input[type="text"]');
                const selectionStart = shouldRestoreFocus ? activeElement.selectionStart : null;

                setLoadingState(root, true);

                try {
                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar el ranking');
                    }

                    const payload = await response.json();

                    if (state.controller !== controller) {
                        return;
                    }

                    root.outerHTML = payload.html;

                    const updatedRoot = document.querySelector(
                        `[data-leaderboard-root][data-leaderboard-search-param="${root.dataset.leaderboardSearchParam}"]`
                    );

                    if (updatedRoot) {
                        updatedRoot.classList.remove('leaderboard-results-pop');
                        void updatedRoot.offsetWidth;
                        updatedRoot.classList.add('leaderboard-results-pop');

                        if (shouldRestoreFocus) {
                            const updatedInput = updatedRoot.querySelector('[data-leaderboard-search-form] input[type="text"]');

                            if (updatedInput) {
                                updatedInput.focus({ preventScroll: true });

                                if (selectionStart !== null) {
                                    const selectionEnd = updatedInput.value.length;
                                    updatedInput.setSelectionRange(selectionEnd, selectionEnd);
                                }
                            }
                        }
                    }

                    updateHistory(requestUrl);
                    window.scrollTo({
                        left: scrollPosition.left,
                        top: scrollPosition.top,
                        behavior: 'auto',
                    });
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                } finally {
                    if (state.controller === controller) {
                        state.controller = null;
                        setLoadingState(root, false);
                    }
                }
            };

            document.addEventListener('input', (event) => {
                const input = event.target.closest('[data-leaderboard-search-form] input[type="text"]');

                if (!input) {
                    return;
                }

                const root = getRoot(input);

                if (!root) {
                    return;
                }

                const state = getRootState(root);
                window.clearTimeout(state.timeout);
                state.timeout = window.setTimeout(() => {
                    loadSection(root, { page: 1 });
                }, 250);
            });

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('[data-leaderboard-search-form]');

                if (!form) {
                    return;
                }

                const root = getRoot(form);

                if (!root) {
                    return;
                }

                event.preventDefault();
                loadSection(root, { page: 1 });
            });

            document.addEventListener('click', (event) => {
                const link = event.target.closest('[data-leaderboard-pagination] a[href]');

                if (!link) {
                    return;
                }

                const root = getRoot(link);

                if (!root) {
                    return;
                }

                const url = new URL(link.href);

                if (url.pathname !== window.location.pathname) {
                    return;
                }

                const pageParam = root.dataset.leaderboardPageParam;
                const page = url.searchParams.get(pageParam);

                if (!page) {
                    return;
                }

                event.preventDefault();
                loadSection(root, { page });
            });
        });

        document.querySelectorAll('a[href="#ranking-delegaciones"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                const target = document.querySelector('#ranking-delegaciones');

                if (!target) {
                    return;
                }

                event.preventDefault();
                const navbar = document.querySelector('nav.sticky');
                const navbarHeight = navbar ? navbar.offsetHeight : 0;
                const extraOffset = 24;
                const targetTop = target.getBoundingClientRect().top + window.scrollY - navbarHeight - extraOffset;

                window.scrollTo({
                    top: Math.max(targetTop, 0),
                    behavior: 'smooth',
                });
            });
        });
    </script>

    <script>
        window.setTimeout(() => window.location.reload(), 600000);
    </script>
@endsection
