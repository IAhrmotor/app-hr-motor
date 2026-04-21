@extends('layouts.app')

@section('content')
    @php
        $sortDirection = function ($column, $sort, $direction) {
            if ($sort !== $column) {
                return 'asc';
            }

            return $direction === 'asc' ? 'desc' : 'asc';
        };
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm" data-dealership-root>
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Gestion de delegaciones</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Listado de delegaciones configuradas en la aplicacion.</p>
                </div>

                <a href="{{ route('dealerships.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear delegacion" aria-label="Crear delegacion">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('dealerships.index') }}" class="mb-6" data-dealership-search-form>
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ $search }}" data-dealership-search-input
                            placeholder="Buscar por nombre, ID Salesforce o URLs"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>
                        @if ($search || $sort !== 'name' || $direction !== 'asc')
                            <a href="{{ route('dealerships.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div data-dealership-loading class="hidden space-y-4" aria-hidden="true">
                <div class="rounded-2xl border border-brand-secondary/10 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 animate-pulse rounded-full bg-slate-200"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-2/5 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-1/4 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-brand-secondary/10">
                            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                                @for ($row = 0; $row < 6; $row++)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-11 w-11 animate-pulse rounded-xl bg-slate-200"></div>
                                                <div class="flex-1 space-y-2">
                                                    <div class="h-4 w-48 animate-pulse rounded bg-slate-200"></div>
                                                    <div class="h-3 w-28 animate-pulse rounded bg-slate-200"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><div class="h-4 w-44 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="ml-auto h-10 w-28 animate-pulse rounded-xl bg-slate-200"></div></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div data-dealership-results>
                @include('dealerships.partials.index-results', [
                    'dealerships' => $dealerships,
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction,
                ])
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-dealership-root]');
            const form = document.querySelector('[data-dealership-search-form]');
            const results = document.querySelector('[data-dealership-results]');
            const loading = document.querySelector('[data-dealership-loading]');

            if (!root || !form || !results || !loading) {
                return;
            }

            const searchInput = form.querySelector('[data-dealership-search-input]');
            const baseUrl = new URL(form.action, window.location.origin);
            let timeout = null;
            let abortController = null;
            let lastRequestKey = '';

            const setLoading = (isLoading) => {
                loading.classList.toggle('hidden', !isLoading);
                results.classList.toggle('hidden', isLoading);
            };

            const buildUrl = (page = 1) => {
                const url = new URL(baseUrl.toString());
                const currentUrl = new URL(window.location.href);
                const search = searchInput?.value.trim() ?? '';

                currentUrl.searchParams.forEach((value, key) => {
                    if (['search', 'sort', 'direction', 'page', 'ajax'].includes(key)) {
                        return;
                    }

                    url.searchParams.set(key, value);
                });

                if (search !== '') {
                    url.searchParams.set('search', search);
                }

                if (Number(page) > 1) {
                    url.searchParams.set('page', page);
                }

                const currentSort = currentUrl.searchParams.get('sort');
                const currentDirection = currentUrl.searchParams.get('direction');

                if (currentSort) {
                    url.searchParams.set('sort', currentSort);
                }

                if (currentDirection) {
                    url.searchParams.set('direction', currentDirection);
                }

                url.searchParams.set('ajax', '1');

                return url;
            };

            const updateHistory = (requestUrl) => {
                const historyUrl = new URL(requestUrl.toString());
                historyUrl.searchParams.delete('ajax');
                window.history.replaceState({}, '', historyUrl.toString());
            };

            const loadResults = async ({ page = 1 } = {}) => {
                const requestUrl = buildUrl(page);
                const requestKey = requestUrl.searchParams.toString();

                if (requestKey === lastRequestKey) {
                    return;
                }

                lastRequestKey = requestKey;

                if (abortController) {
                    abortController.abort();
                }

                const controller = new AbortController();
                abortController = controller;
                setLoading(true);

                try {
                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar delegaciones');
                    }

                    const payload = await response.json();

                    if (abortController !== controller) {
                        return;
                    }

                    results.innerHTML = payload.html;
                    results.classList.remove('live-results-pop');
                    void results.offsetWidth;
                    results.classList.add('live-results-pop');
                    updateHistory(requestUrl);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                } finally {
                    if (abortController === controller) {
                        abortController = null;
                        setLoading(false);
                    }
                }
            };

            const queueSearch = () => {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(() => {
                    loadResults({ page: 1 });
                }, 250);
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                loadResults({ page: 1 });
            });

            searchInput?.addEventListener('input', queueSearch);

            document.addEventListener('click', (event) => {
                const sortLink = event.target.closest('[data-dealership-sort-link]');
                const paginationLink = event.target.closest('[data-dealership-pagination] a[href]');
                const link = sortLink || paginationLink;

                if (!link) {
                    return;
                }

                const url = new URL(link.href);

                if (url.pathname !== window.location.pathname) {
                    return;
                }

                event.preventDefault();
                loadResults({ page: url.searchParams.get('page') || 1 });
            });
        });
    </script>
@endsection
