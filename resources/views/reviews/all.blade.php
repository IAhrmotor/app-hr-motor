@extends('layouts.app')

@php
    $navTitle = 'Todas las reseñas';
@endphp

@section('title', 'Todas las reseñas')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Todas las reseñas</h1>
                <p class="mt-2 max-w-3xl text-sm text-gray-600">
                    Aquí tienes la tabla completa con filtros avanzados, rango de fechas y paginación sin recargar la página.
                </p>
            </div>

            <a href="{{ route('reviews.index') }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Volver a reseñas
            </a>
        </div>

        <div data-reviews-root>
            @include('reviews.partials.activity-results', [
                'reviews' => $reviews,
                'dealerships' => $dealerships,
                'filters' => $filters,
                'filterAction' => route('reviews.all'),
            ])
        </div>
    </div>

    <script>
        (function () {
            const root = document.querySelector('[data-reviews-root]');

            if (!root) {
                return;
            }

            let requestToken = 0;

            const getForm = () => root.querySelector('[data-reviews-filter-form]');
            const getDateInputs = () => ({
                from: root.querySelector('[data-date-input="from"]'),
                to: root.querySelector('[data-date-input="to"]'),
            });

            const getDateDisplays = () => ({
                from: root.querySelector('[data-display-date-from]'),
                to: root.querySelector('[data-display-date-to]'),
            });

            const normalizeUrl = (url) => {
                const cleanUrl = new URL(url.toString());
                cleanUrl.searchParams.delete('ajax');
                return cleanUrl;
            };

            const setLoadingState = (isLoading) => {
                root.setAttribute('aria-busy', isLoading ? 'true' : 'false');

                const loading = root.querySelector('[data-reviews-loading]');
                if (loading) {
                    loading.hidden = !isLoading;
                }
            };

            const setFormDisabled = (disabled) => {
                const form = getForm();
                if (!form) {
                    return;
                }

                form.querySelectorAll('input, select, button').forEach((control) => {
                    if (control.dataset.keepEnabled === '1') {
                        return;
                    }

                    control.disabled = disabled;
                });
            };

            const formatDateLabel = (value) => {
                if (!value) {
                    return '';
                }

                const [year, month, day] = value.split('-');
                if (!year || !month || !day) {
                    return value;
                }

                return `${day}/${month}/${year}`;
            };

            const refreshDateLabels = () => {
                const inputs = getDateInputs();
                const displays = getDateDisplays();

                if (inputs.from && displays.from) {
                    displays.from.textContent = inputs.from.value ? formatDateLabel(inputs.from.value) : 'Desde';
                }

                if (inputs.to && displays.to) {
                    displays.to.textContent = inputs.to.value ? formatDateLabel(inputs.to.value) : 'Hasta';
                }
            };

            const syncDateConstraints = () => {
                const inputs = getDateInputs();

                if (inputs.from && inputs.to) {
                    inputs.from.max = inputs.to.value || '';
                    inputs.to.min = inputs.from.value || '';

                    if (inputs.from.value && inputs.to.value && inputs.to.value < inputs.from.value) {
                        inputs.to.value = inputs.from.value;
                    }
                }
            };

            const buildUrlFromForm = (page = null) => {
                const form = getForm();

                if (!form) {
                    return new URL(window.location.href);
                }

                const url = new URL(form.action, window.location.origin);
                const params = new URLSearchParams(new FormData(form));

                params.delete('ajax');

                if (page) {
                    params.set('page', page);
                } else {
                    params.delete('page');
                }

                url.search = params.toString();

                return url;
            };

            const syncUrl = (url) => {
                const cleanUrl = normalizeUrl(url);
                window.history.replaceState({}, '', `${cleanUrl.pathname}${cleanUrl.search}`);
            };

            const renderReviews = async (url) => {
                const currentToken = ++requestToken;
                const fetchUrl = new URL(url.toString());
                fetchUrl.searchParams.set('ajax', '1');

                setLoadingState(true);
                setFormDisabled(true);

                try {
                    const response = await fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();

                    if (currentToken !== requestToken) {
                        return;
                    }

                    root.innerHTML = payload.html;
                    syncDateConstraints();
                    refreshDateLabels();
                    syncUrl(url);
                } catch (error) {
                    console.error('No se ha podido actualizar la tabla de reseñas.', error);
                } finally {
                    if (currentToken === requestToken) {
                        setLoadingState(false);
                        setFormDisabled(false);
                    }
                }
            };

            root.addEventListener('submit', (event) => {
                const form = event.target.closest('[data-reviews-filter-form]');

                if (!form) {
                    return;
                }

                event.preventDefault();
                renderReviews(buildUrlFromForm());
            });

            root.addEventListener('change', (event) => {
                if (!event.target.closest('[data-reviews-filter-form] select, [data-reviews-filter-form] input[type="date"]')) {
                    return;
                }

                syncDateConstraints();
                refreshDateLabels();
            });

            root.addEventListener('click', (event) => {
                const resetButton = event.target.closest('[data-reviews-reset]');

                if (resetButton) {
                    event.preventDefault();

                    const form = getForm();
                    if (!form) {
                        return;
                    }

                    form.querySelectorAll('input[type="text"], input[type="date"]').forEach((input) => {
                        input.value = '';
                    });

                    form.querySelectorAll('select').forEach((select) => {
                        select.selectedIndex = 0;
                    });

                    syncDateConstraints();
                    refreshDateLabels();
                    renderReviews(buildUrlFromForm());
                    return;
                }

                const paginationLink = event.target.closest('[data-reviews-pagination] a');
                if (!paginationLink) {
                    return;
                }

                event.preventDefault();
                renderReviews(new URL(paginationLink.href));
            });

            root.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-date-trigger]');

                if (!trigger) {
                    return;
                }

                const inputs = getDateInputs();
                const key = trigger.getAttribute('data-date-trigger');
                const input = key === 'from' ? inputs.from : inputs.to;

                if (!input) {
                    return;
                }

                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                    return;
                }

                input.click();
            });

            window.addEventListener('popstate', () => {
                renderReviews(new URL(window.location.href));
            });

            syncDateConstraints();
            refreshDateLabels();
        })();
    </script>
@endsection
