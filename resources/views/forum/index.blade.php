@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
            <div class="border-b border-brand-secondary/10 bg-brand-secondary px-6 py-8 text-white sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-white/90">
                            Comunidad interna
                        </span>
                        <h1 class="mt-4 max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">Foro de dudas del equipo comercial</h1>
                        <p class="mt-4 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                            Un espacio para abrir consultas, compartir contexto y resolver bloqueos entre compañeros. Los hilos abiertos aparecen primero y los resueltos quedan ordenados después, siempre del más reciente al más antiguo.
                        </p>
                    </div>

                    @if ($canCreateThreads)
                        <a href="{{ route('forum.create') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-slate-100">
                            Crear nuevo hilo
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>
                    @endif
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <article class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/65">Abiertas</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $threadStats['open'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/65">Resueltas</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $threadStats['resolved'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/65">Respuestas</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $threadStats['totalReplies'] }}</p>
                    </article>
                </div>
            </div>

            <div class="px-6 py-6 sm:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                @endif

                <form method="GET" action="{{ route('forum.index') }}" class="mb-6" data-forum-search-form>
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="relative w-full lg:max-w-xl">
                            <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Buscar por título, contenido, autor, delegación o tag"
                                class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <div class="relative w-full sm:w-52">
                                <select name="status"
                                    class="w-full cursor-pointer appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-11 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                    <option value="">Todos los estados</option>
                                    <option value="open" @selected($status === 'open')>Abiertos</option>
                                    <option value="resolved" @selected($status === 'resolved')>Resueltos</option>
                                </select>

                                <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/60">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Filtrar</button>
                                @if ($search !== '' || $status)
                                    <a href="{{ route('forum.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                <div id="forum-loading" class="hidden space-y-4" aria-hidden="true">
                    <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="h-5 w-20 animate-pulse rounded-full bg-slate-200"></div>
                                    <div class="h-5 w-24 animate-pulse rounded-full bg-slate-200"></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div class="h-5 w-14 animate-pulse rounded-full bg-slate-200"></div>
                                    <div class="h-5 w-20 animate-pulse rounded-full bg-slate-200"></div>
                                </div>
                                <div class="space-y-2 pt-2">
                                    <div class="h-5 w-2/3 animate-pulse rounded bg-slate-200"></div>
                                    <div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>
                                    <div class="h-4 w-5/6 animate-pulse rounded bg-slate-200"></div>
                                </div>
                            </div>

                            <div class="h-10 w-28 shrink-0 animate-pulse rounded-2xl border border-slate-200 bg-white"></div>
                        </div>

                        <div class="mt-5 grid gap-3 rounded-[1.5rem] border border-brand-secondary/10 bg-white/90 px-4 py-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center">
                            <div class="h-12 w-12 animate-pulse rounded-2xl bg-slate-200"></div>
                            <div class="min-w-0 space-y-2">
                                <div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div>
                                <div class="h-3 w-48 animate-pulse rounded bg-slate-200"></div>
                            </div>
                            <div class="space-y-2 sm:text-right">
                                <div class="h-3 w-28 animate-pulse rounded bg-slate-200"></div>
                                <div class="h-4 w-20 animate-pulse rounded bg-slate-200 sm:ml-auto"></div>
                                <div class="h-3 w-32 animate-pulse rounded bg-slate-200 sm:ml-auto"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="h-5 w-20 animate-pulse rounded-full bg-slate-200"></div>
                                    <div class="h-5 w-24 animate-pulse rounded-full bg-slate-200"></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div class="h-5 w-14 animate-pulse rounded-full bg-slate-200"></div>
                                    <div class="h-5 w-20 animate-pulse rounded-full bg-slate-200"></div>
                                </div>
                                <div class="space-y-2 pt-2">
                                    <div class="h-5 w-1/2 animate-pulse rounded bg-slate-200"></div>
                                    <div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>
                                    <div class="h-4 w-4/5 animate-pulse rounded bg-slate-200"></div>
                                </div>
                            </div>

                            <div class="h-10 w-28 shrink-0 animate-pulse rounded-2xl border border-slate-200 bg-white"></div>
                        </div>

                        <div class="mt-5 grid gap-3 rounded-[1.5rem] border border-brand-secondary/10 bg-white/90 px-4 py-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center">
                            <div class="h-12 w-12 animate-pulse rounded-2xl bg-slate-200"></div>
                            <div class="min-w-0 space-y-2">
                                <div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div>
                                <div class="h-3 w-48 animate-pulse rounded bg-slate-200"></div>
                            </div>
                            <div class="space-y-2 sm:text-right">
                                <div class="h-3 w-28 animate-pulse rounded bg-slate-200"></div>
                                <div class="h-4 w-20 animate-pulse rounded bg-slate-200 sm:ml-auto"></div>
                                <div class="h-3 w-32 animate-pulse rounded bg-slate-200 sm:ml-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div data-forum-results>
                    @include('forum.partials.thread-results', ['threads' => $threads])
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-forum-search-form]');
            const resultsWrapper = document.querySelector('[data-forum-results]');
            const loadingWrapper = document.getElementById('forum-loading');

            if (!form || !resultsWrapper || !loadingWrapper) {
                return;
            }

            const searchInput = form.querySelector('input[name="search"]');
            const statusSelect = form.querySelector('select[name="status"]');
            const baseUrl = new URL(form.action, window.location.origin);
            let searchTimeout = null;
            let abortController = null;
            let lastRequestKey = '';

            const buildUrl = (page = 1) => {
                const url = new URL(baseUrl.toString());
                const search = searchInput?.value.trim() ?? '';
                const status = statusSelect?.value ?? '';

                if (search !== '') {
                    url.searchParams.set('search', search);
                }

                if (status !== '') {
                    url.searchParams.set('status', status);
                }

                if (Number(page) > 1) {
                    url.searchParams.set('page', page);
                }

                url.searchParams.set('ajax', '1');

                return url;
            };

            const updateHistory = (url) => {
                const historyUrl = new URL(url.toString());
                historyUrl.searchParams.delete('ajax');
                window.history.replaceState({}, '', historyUrl.toString());
            };

            const setLoading = (isLoading) => {
                loadingWrapper.classList.toggle('hidden', !isLoading);
                resultsWrapper.classList.toggle('hidden', isLoading);
            };

            const loadResults = async ({ page = 1, updateBrowserHistory = true } = {}) => {
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
                        throw new Error('No se pudo cargar el foro');
                    }

                    const payload = await response.json();

                    if (abortController !== controller) {
                        return;
                    }

                    resultsWrapper.innerHTML = payload.html;
                    resultsWrapper.classList.remove('forum-results-pop');
                    void resultsWrapper.offsetWidth;
                    resultsWrapper.classList.add('forum-results-pop');

                    if (updateBrowserHistory) {
                        updateHistory(requestUrl);
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                } finally {
                    if (abortController === controller) {
                        setLoading(false);
                        abortController = null;
                    }
                }
            };

            const queueSearch = () => {
                window.clearTimeout(searchTimeout);
                searchTimeout = window.setTimeout(() => {
                    loadResults({ page: 1 });
                }, 250);
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                loadResults({ page: 1 });
            });

            searchInput?.addEventListener('input', queueSearch);

            statusSelect?.addEventListener('change', () => {
                loadResults({ page: 1 });
            });

            resultsWrapper.addEventListener('click', (event) => {
                const link = event.target.closest('[data-forum-pagination] a[href]');

                if (!link) {
                    return;
                }

                const url = new URL(link.href);

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
