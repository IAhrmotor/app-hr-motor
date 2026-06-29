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
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm" data-zones-root>
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Gestión de zonas</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Organiza las zonas y reparte las delegaciones entre ellas.</p>
                </div>

                <a href="{{ route('admin.zones.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear zona" aria-label="Crear zona">
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

            <form method="GET" action="{{ route('admin.zones.index') }}" class="mb-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre o delegación"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>
                        @if ($search || $sort !== 'name' || $direction !== 'asc')
                            <a href="{{ route('admin.zones.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div data-zones-results>
                @include('admin.zones.partials.index-results', [
                    'zones' => $zones,
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction,
                ])
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-zones-root]');
            const form = root?.querySelector('form[action="{{ route('admin.zones.index') }}"]');
            const results = document.querySelector('[data-zones-results]');

            if (!root || !form || !results) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const baseUrl = new URL(form.action, window.location.origin);
            let timeout = null;
            let abortController = null;

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

            const loadResults = async (requestUrl) => {

                if (abortController) {
                    abortController.abort();
                }

                const controller = new AbortController();
                abortController = controller;

                try {
                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar zonas');
                    }

                    const payload = await response.json();

                    if (abortController !== controller) {
                        return;
                    }

                    results.innerHTML = payload.html;
                    const historyUrl = new URL(requestUrl.toString());
                    historyUrl.searchParams.delete('ajax');
                    window.history.replaceState({}, '', historyUrl.toString());
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                } finally {
                    if (abortController === controller) {
                        abortController = null;
                    }
                }
            };

            const queueSearch = () => {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(() => loadResults(buildUrl(1)), 250);
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                loadResults(buildUrl(1));
            });

            searchInput?.addEventListener('input', queueSearch);

            document.addEventListener('click', (event) => {
                const sortLink = event.target.closest('[data-zone-sort-link]');
                const paginationLink = event.target.closest('[data-zone-pagination] a[href]');
                const link = sortLink || paginationLink;

                if (!link) {
                    return;
                }

                event.preventDefault();

                const url = new URL(link.href, window.location.origin);
                url.searchParams.set('ajax', '1');
                loadResults(url);
            });
        });
    </script>
@endsection
