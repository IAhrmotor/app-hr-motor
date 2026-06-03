@extends('layouts.app')

@section('content')
    @php
        $authUser = auth()->user();
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm" data-users-root>
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Gestion de usuarios</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Listado de usuarios registrados en la plataforma.</p>
                </div>

                <a href="{{ route('users.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear usuario" aria-label="Crear usuario">
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

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" action="{{ route('users.index') }}" class="mb-6" data-users-search-form>
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ $search }}" data-users-search-input
                            placeholder="Buscar por nombre, correo, telefono o Enreach"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="relative w-full md:w-56">
                        <select name="status" data-users-status-select
                            class="w-full cursor-pointer appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                            <option value="">Todos los estados</option>
                            <option value="active" @selected($status === 'active')>Activos</option>
                            <option value="pending" @selected($status === 'pending')>Pendientes</option>
                            <option value="disabled" @selected($status === 'disabled')>Desactivados</option>
                        </select>

                        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>

                        @if ($search || $status || $sort !== 'name' || $direction !== 'asc')
                            <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div data-users-loading class="hidden space-y-4" aria-hidden="true">
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
                                                <div class="h-11 w-11 animate-pulse rounded-full bg-slate-200"></div>
                                                <div class="flex-1 space-y-2">
                                                    <div class="h-4 w-48 animate-pulse rounded bg-slate-200"></div>
                                                    <div class="h-3 w-28 animate-pulse rounded bg-slate-200"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><div class="h-4 w-44 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                                        <td class="px-6 py-4"><div class="ml-auto h-10 w-28 animate-pulse rounded-xl bg-slate-200"></div></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div data-users-results>
                @include('users.partials.index-results', [
                    'users' => $users,
                    'authUser' => $authUser,
                    'search' => $search,
                    'status' => $status,
                    'sort' => $sort,
                    'direction' => $direction,
                ])
            </div>
        </section>
    </main>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4 py-6" data-user-disable-modal aria-hidden="true">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start gap-4">
                <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-brand-secondary">Desactivar usuario</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Vas a desactivar este usuario. No podrá iniciar sesión ni acceder a la aplicación, pero se mantendrá su histórico, mensajes y auditorías.
                    </p>

                    <div class="mt-4">
                        <label for="user-disable-reason-input" class="mb-2 block text-sm font-medium text-brand-secondary">Motivo opcional</label>
                        <input id="user-disable-reason-input" type="text" placeholder="Por ejemplo: Baja de empleado"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                            data-user-disable-reason-select>
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-3">
                        <button type="button" class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50" data-user-disable-modal-cancel>
                            Cancelar
                        </button>
                        <button type="button" class="inline-flex items-center rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700" data-user-disable-modal-confirm>
                            Desactivar usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-users-root]');
            const form = document.querySelector('[data-users-search-form]');
            const results = document.querySelector('[data-users-results]');
            const loading = document.querySelector('[data-users-loading]');
            const disableModal = document.querySelector('[data-user-disable-modal]');
            const disableReasonSelect = document.querySelector('[data-user-disable-reason-select]');
            const disableModalCancel = document.querySelector('[data-user-disable-modal-cancel]');
            const disableModalConfirm = document.querySelector('[data-user-disable-modal-confirm]');

            if (!root || !form || !results || !loading) {
                return;
            }

            const searchInput = form.querySelector('[data-users-search-input]');
            const statusSelect = form.querySelector('[data-users-status-select]');
            const baseUrl = new URL(form.action, window.location.origin);
            let timeout = null;
            let abortController = null;
            let lastRequestKey = '';
            let pendingDisableForm = null;

            const setLoading = (isLoading) => {
                loading.classList.toggle('hidden', !isLoading);
                results.classList.toggle('hidden', isLoading);
            };

            const closeDisableModal = () => {
                if (disableModal) {
                    disableModal.classList.add('hidden');
                    disableModal.setAttribute('aria-hidden', 'true');
                }

                pendingDisableForm = null;
            };

            const openDisableModal = (targetForm) => {
                pendingDisableForm = targetForm;

                if (disableReasonSelect) {
                    disableReasonSelect.value = '';
                }

                if (disableModal) {
                    disableModal.classList.remove('hidden');
                    disableModal.setAttribute('aria-hidden', 'false');
                }
            };

            const buildUrl = (page = 1) => {
                const url = new URL(baseUrl.toString());
                const currentUrl = new URL(window.location.href);
                const search = searchInput?.value.trim() ?? '';
                const status = statusSelect?.value ?? '';

                currentUrl.searchParams.forEach((value, key) => {
                    if (['search', 'status', 'page', 'ajax'].includes(key)) {
                        return;
                    }

                    url.searchParams.set(key, value);
                });

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
                        throw new Error('No se pudo cargar usuarios');
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
            statusSelect?.addEventListener('change', () => loadResults({ page: 1 }));

            document.addEventListener('click', (event) => {
                const sortLink = event.target.closest('[data-users-sort-link]');
                const paginationLink = event.target.closest('[data-users-pagination] a[href]');
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

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form');

                if (!form) {
                    return;
                }

                if (form.dataset.userActionConfirmed === '1') {
                    delete form.dataset.userActionConfirmed;
                    return;
                }

                const disableAction = form.dataset.userDisableForm === '1';
                const reactivateAction = form.dataset.userReactivateForm === '1';
                const deleteAction = form.dataset.userDeleteForm === '1';

                if (!disableAction && !reactivateAction && !deleteAction) {
                    return;
                }

                event.preventDefault();

                if (disableAction) {
                    openDisableModal(form);
                    return;
                }

                if (reactivateAction) {
                    const confirmed = window.confirm('Vas a reactivar este usuario. Podra volver a acceder a la aplicacion segun sus permisos actuales. ¿Quieres continuar?');

                    if (!confirmed) {
                        return;
                    }

                    form.dataset.userActionConfirmed = '1';
                    form.submit();
                    return;
                }

                if (deleteAction) {
                    const confirmation = window.prompt('Escribe ELIMINAR para confirmar el borrado definitivo de este usuario. Esta accion puede romper el historico y las referencias asociadas.');

                    if (confirmation !== 'ELIMINAR') {
                        return;
                    }

                    form.dataset.userActionConfirmed = '1';
                    form.submit();
                }
            });

            disableModalCancel?.addEventListener('click', closeDisableModal);

            disableModalConfirm?.addEventListener('click', () => {
                if (!pendingDisableForm) {
                    closeDisableModal();
                    return;
                }

                const reasonValue = disableReasonSelect?.value?.trim() || '';

                let reasonInput = pendingDisableForm.querySelector('[data-user-disable-reason-input]');

                if (!reasonInput) {
                    reasonInput = document.createElement('input');
                    reasonInput.type = 'hidden';
                    reasonInput.name = 'disabled_reason';
                    reasonInput.setAttribute('data-user-disable-reason-input', '1');
                    pendingDisableForm.appendChild(reasonInput);
                }

                reasonInput.value = reasonValue;
                pendingDisableForm.dataset.userActionConfirmed = '1';
                pendingDisableForm.submit();
                closeDisableModal();
            });

            disableModal?.addEventListener('click', (event) => {
                if (event.target === disableModal) {
                    closeDisableModal();
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && pendingDisableForm) {
                    closeDisableModal();
                }
            });
        });
    </script>
@endsection
