@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm" data-contacts-root>
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Contactos de agenda</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Gestion de contactos externos visibles en la agenda.</p>
                </div>

                <a href="{{ route('admin.contacts.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear contacto" aria-label="Crear contacto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <circle cx="9" cy="8" r="2.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 17c.9-2 2.5-3 4.5-3s3.6 1 4.5 3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8v6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 11h6" />
                    </svg>
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.contacts.index') }}" class="mb-6" data-contacts-search-form>
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ $search }}" data-contacts-search-input
                            placeholder="Buscar por nombre, correo o numero"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>
                        @if ($search)
                            <a href="{{ route('admin.contacts.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div data-contacts-loading class="hidden space-y-4" aria-hidden="true">
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
                                        <td class="px-6 py-4"><div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-44 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="ml-auto h-10 w-28 animate-pulse rounded-xl bg-slate-200"></div></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div data-contacts-results>
                @include('admin.contacts.partials.index-results', ['contacts' => $contacts, 'search' => $search])
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-contacts-root]');
            const form = document.querySelector('[data-contacts-search-form]');
            const results = document.querySelector('[data-contacts-results]');
            const loading = document.querySelector('[data-contacts-loading]');

            if (!root || !form || !results || !loading) {
                return;
            }

            const searchInput = form.querySelector('[data-contacts-search-input]');
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
                const search = searchInput?.value.trim() ?? '';

                if (search !== '') {
                    url.searchParams.set('search', search);
                }

                if (Number(page) > 1) {
                    url.searchParams.set('page', page);
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
                        throw new Error('No se pudo cargar contactos');
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
                const paginationLink = event.target.closest('[data-contacts-pagination] a[href]');

                if (!paginationLink) {
                    return;
                }

                const url = new URL(paginationLink.href);

                if (url.pathname !== window.location.pathname) {
                    return;
                }

                const page = url.searchParams.get('page');

                if (!page) {
                    return;
                }

                event.preventDefault();
                loadResults({ page });
            });
        });
    </script>
@endsection
