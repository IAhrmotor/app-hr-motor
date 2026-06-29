@php
    $zone = $zone ?? null;
    $availableDealerships = collect($availableDealerships ?? []);
    $selectedDealershipIds = collect(old('dealership_ids', $zone?->dealerships?->pluck('id')->all() ?? []))
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();
    $selectedDealerships = $availableDealerships->whereIn('id', array_map('intval', $selectedDealershipIds))->values();
    $selectedDealershipNames = $selectedDealerships->pluck('name')->implode(', ');
@endphp

<div class="space-y-6">
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(18rem,0.95fr)] xl:items-stretch">
        <div class="flex h-full flex-col rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Datos de la zona</p>
            <h2 class="mt-2 text-lg font-semibold text-brand-secondary">Configuración básica</h2>

            <div class="mt-5 flex flex-1 flex-col rounded-[1.5rem] border border-brand-secondary/10 bg-white p-4">
                <div class="flex flex-1 flex-col">
                    <label for="name" class="text-sm font-semibold text-brand-secondary">Nombre</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $zone->name ?? '') }}"
                        required
                        class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                    >
                </div>

                <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Reglas rápidas</p>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-brand-secondary/70">
                        <li class="rounded-xl border border-brand-secondary/10 bg-white px-3 py-2">Cada delegación solo puede estar en una zona.</li>
                        <li class="rounded-xl border border-brand-secondary/10 bg-white px-3 py-2">Puedes elegir una o varias delegaciones para la misma zona.</li>
                        <li class="rounded-xl border border-brand-secondary/10 bg-white px-3 py-2">Cualquier cambio quedará reflejado en el log de zonas.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex h-full flex-col gap-6">
            <div class="flex-1 rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Vista previa</p>
                <h3 class="mt-1 text-lg font-semibold text-brand-secondary">Cómo quedará la zona</h3>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70">
                    {{ count($selectedDealershipIds) > 0 ? 'La zona incluirá ' . $selectedDealershipNames . '.' : 'Selecciona delegaciones para construir la zona.' }}
                </p>

                <div class="mt-5 rounded-[1.5rem] border border-brand-secondary/10 bg-white p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 20h16M6 20V8.5a2.5 2.5 0 0 1 2.5-2.5H10V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1h1.5A2.5 2.5 0 0 1 18 8.5V20M9 20v-4h6v4M9 9h.01M13 9h.01M9 12h.01M13 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-brand-secondary">Delegaciones seleccionadas</p>
                            <p class="text-xs text-brand-secondary/60" data-zone-preview-count>{{ count($selectedDealershipIds) }} {{ count($selectedDealershipIds) === 1 ? 'delegación' : 'delegaciones' }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-brand-secondary/70" data-zone-preview-text>
                        {{ count($selectedDealershipIds) > 0 ? 'En la zona estarán ' . $selectedDealershipNames . '.' : 'Selecciona delegaciones para construir la zona.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-brand-secondary/10 bg-white shadow-sm">
        <div class="bg-brand-secondary px-5 py-4 text-white">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/65">Delegaciones</p>
            <h2 class="mt-1 text-lg font-semibold">Busca delegaciones y agrégalas a la zona</h2>
            <p class="mt-2 text-sm leading-6 text-white/75">
                Las delegaciones que ya pertenezcan a otra zona aparecerán bloqueadas.
            </p>
        </div>

        <div class="space-y-5 p-5">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <input
                    type="search"
                    placeholder="Buscar por nombre, zona o estado"
                    autocomplete="off"
                    data-zone-dealership-search
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-28 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                >

                <button
                    type="button"
                    data-zone-dealership-search-clear
                    class="absolute inset-y-0 right-3 my-auto hidden h-9 rounded-xl border border-brand-secondary/10 bg-white px-3 text-xs font-semibold text-brand-secondary transition hover:cursor-pointer hover:bg-brand-secondary/5"
                >
                    Limpiar
                </button>
            </div>

            <div class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Resumen</p>
                        <p class="mt-1 text-sm font-semibold text-brand-secondary">Delegaciones en la zona</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary shadow-sm" data-zone-selected-count>
                        {{ count($selectedDealershipIds) }} seleccionadas
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-brand-secondary/70" data-zone-selected-summary>
                    {{ count($selectedDealershipIds) > 0 ? 'En la zona estarán ' . $selectedDealershipNames . '.' : 'Selecciona delegaciones para construir la zona.' }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2" data-zone-selected-list>
                    @forelse ($selectedDealerships as $dealership)
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-brand-primary/15 bg-white px-3 py-1.5 text-sm font-medium text-brand-secondary shadow-sm"
                            data-zone-selected-pill="{{ $dealership->id }}"
                        >
                            <span class="max-w-[12rem] truncate">{{ $dealership->name }}</span>
                            <button
                                type="button"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-secondary/5 text-brand-secondary transition hover:cursor-pointer hover:bg-brand-primary hover:text-white"
                                data-zone-remove-dealership
                                data-dealership-id="{{ $dealership->id }}"
                                aria-label="Quitar {{ $dealership->name }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M20.7457 3.32851C20.3552 2.93798 19.722 2.93798 19.3315 3.32851L12.0371 10.6229L4.74275 3.32851C4.35223 2.93798 3.71906 2.93798 3.32854 3.32851C2.93801 3.71903 2.93801 4.3522 3.32854 4.74272L10.6229 12.0371L3.32856 19.3314C2.93803 19.722 2.93801 20.3551 3.32856 20.7457C3.71908 21.1362 4.35225 21.1362 4.74277 20.7457L12.0371 13.4513L19.3315 20.7457C19.722 21.1362 20.3552 21.1362 20.7457 20.7457C21.1362 20.3551 21.1362 19.722 20.7457 19.3315L13.4513 12.0371L20.7457 4.74272C21.1362 4.3522 21.1362 3.71903 20.7457 3.32851Z" fill="currentColor"/>
                                </svg>
                            </button>
                        </span>
                    @empty
                        <span class="text-sm text-brand-secondary/60">Todavía no hay delegaciones añadidas.</span>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Buscador global</p>
                        <h3 class="mt-1 text-sm font-semibold text-brand-secondary">Delegaciones disponibles</h3>
                    </div>
                    <p class="text-xs text-brand-secondary/60" data-zone-dealership-results-count>
                        {{ $availableDealerships->count() }} delegaciones
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5" data-zone-dealership-results>
                    @foreach ($availableDealerships as $dealership)
                        @php
                            $assignedZoneName = $dealership->zone?->name;
                            $isSelected = in_array((string) $dealership->id, $selectedDealershipIds, true);
                            $isBlocked = filled($assignedZoneName) && ! $isSelected;
                            $searchIndex = strtolower(trim(implode(' ', array_filter([
                                $dealership->name,
                                $dealership->salesforce_id,
                                $assignedZoneName,
                                $dealership->phone,
                            ]))));
                        @endphp

                        <article
                            class="group flex h-full flex-col rounded-2xl border border-brand-secondary/10 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            data-zone-dealership-card
                            data-search-index="{{ $searchIndex }}"
                            data-dealership-id="{{ $dealership->id }}"
                            data-dealership-name="{{ $dealership->name }}"
                            data-dealership-zone="{{ $assignedZoneName ?? '' }}"
                            data-dealership-blocked="{{ $isBlocked ? '1' : '0' }}"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex h-14 w-14 shrink-0 overflow-hidden rounded-2xl border border-brand-secondary/10 bg-slate-100">
                                    @if ($dealership->image_url)
                                        <img src="{{ $dealership->image_url }}" alt="Foto de {{ $dealership->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-brand-primary/10 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                            {{ mb_substr($dealership->name, 0, 2) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-brand-secondary">{{ $dealership->name }}</p>
                                    <p class="truncate text-xs text-brand-secondary/60">{{ $dealership->salesforce_id ?? 'Sin ID Salesforce' }}</p>
                                    <p class="mt-1 truncate text-xs text-brand-secondary/60">
                                        {{ $assignedZoneName ? 'Zona: ' . $assignedZoneName : 'Sin zona asignada' }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:cursor-pointer hover:opacity-90"
                                data-zone-add-dealership
                                data-dealership-id="{{ $dealership->id }}"
                                aria-disabled="{{ $isSelected || $isBlocked ? 'true' : 'false' }}"
                            >
                                {{ $isSelected ? 'Añadida' : ($isBlocked ? 'Bloqueada' : 'Añadir a la zona') }}
                            </button>
                        </article>
                    @endforeach
                </div>

                <p class="mt-3 hidden text-sm text-brand-secondary/60" data-zone-dealership-empty-state>
                    No hay delegaciones que coincidan con esa búsqueda.
                </p>
            </div>
        </div>
    </div>

    <div data-zone-hidden-dealerships>
        @foreach ($selectedDealershipIds as $dealershipId)
            <input type="hidden" name="dealership_ids[]" value="{{ $dealershipId }}" data-zone-hidden-dealership="{{ $dealershipId }}">
        @endforeach
    </div>
</div>

<script>
    const initZoneForm = () => {
        const root = document.querySelector('[data-zone-form-root]') || document;
        const searchInput = root.querySelector('[data-zone-dealership-search]');
        const clearButton = root.querySelector('[data-zone-dealership-search-clear]');
        const cards = Array.from(root.querySelectorAll('[data-zone-dealership-card]'));
        const selectedList = root.querySelector('[data-zone-selected-list]');
        const selectedSummary = root.querySelector('[data-zone-selected-summary]');
        const selectedCount = root.querySelector('[data-zone-selected-count]');
        const previewCount = root.querySelector('[data-zone-preview-count]');
        const previewText = root.querySelector('[data-zone-preview-text]');
        const hiddenWrapper = root.querySelector('[data-zone-hidden-dealerships]');
        const resultCount = root.querySelector('[data-zone-dealership-results-count]');
        const emptyState = root.querySelector('[data-zone-dealership-empty-state]');

        if (!searchInput || !selectedList || !selectedSummary || !selectedCount || !previewCount || !previewText || !hiddenWrapper) {
            return;
        }

        const findCardById = (id) => cards.find((card) => String(card.dataset.dealershipId) === String(id));
        const getCardData = (card) => ({
            id: String(card.dataset.dealershipId),
            name: card.dataset.dealershipName || '',
        });

        const selected = new Map();
        const initialHiddenInputs = Array.from(hiddenWrapper.querySelectorAll('input[data-zone-hidden-dealership]'));

        initialHiddenInputs.forEach((input) => {
            const card = findCardById(String(input.value));
            selected.set(String(input.value), {
                id: String(input.value),
                name: card ? getCardData(card).name : '',
            });
        });

        const updateCounters = () => {
            const count = selected.size;
            selectedCount.textContent = `${count} seleccionadas`;
            previewCount.textContent = `${count} ${count === 1 ? 'delegación' : 'delegaciones'}`;

            const names = Array.from(selected.values()).map((item) => item.name).filter(Boolean);
            selectedSummary.textContent = count > 0
                ? `En la zona estarán ${names.join(', ')}.`
                : 'Selecciona delegaciones para construir la zona.';
            previewText.textContent = selectedSummary.textContent;
        };

        const syncHiddenInputs = () => {
            hiddenWrapper.innerHTML = '';

            Array.from(selected.keys()).forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'dealership_ids[]';
                input.value = id;
                input.dataset.zoneHiddenDealership = id;
                hiddenWrapper.appendChild(input);
            });
        };

        const syncCardState = () => {
            cards.forEach((card) => {
                const id = String(card.dataset.dealershipId);
                const button = card.querySelector('[data-zone-add-dealership]');
                const isSelected = selected.has(id);
                const isBlocked = card.dataset.dealershipBlocked === '1';

                if (button) {
                    button.disabled = isSelected || isBlocked;
                    button.textContent = isSelected ? 'Añadida' : (isBlocked ? 'Bloqueada' : 'Añadir a la zona');
                }

                card.classList.toggle('ring-2', isSelected);
                card.classList.toggle('ring-brand-primary/20', isSelected);
                card.classList.toggle('opacity-70', isBlocked && ! isSelected);
            });
        };

        const renderSelected = () => {
            selectedList.innerHTML = '';

            if (selected.size === 0) {
                const empty = document.createElement('span');
                empty.className = 'text-sm text-brand-secondary/60';
                empty.textContent = 'Todavía no hay delegaciones añadidas.';
                selectedList.appendChild(empty);
            } else {
                selected.forEach((item) => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-flex items-center gap-2 rounded-full border border-brand-primary/15 bg-white px-3 py-1.5 text-sm font-medium text-brand-secondary shadow-sm';
                    pill.dataset.zoneSelectedPill = item.id;
                    pill.innerHTML = `
                        <span class="max-w-[12rem] truncate">${item.name}</span>
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-secondary/5 text-brand-secondary transition hover:cursor-pointer hover:bg-brand-primary hover:text-white" data-zone-remove-dealership data-dealership-id="${item.id}" aria-label="Quitar ${item.name}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20.7457 3.32851C20.3552 2.93798 19.722 2.93798 19.3315 3.32851L12.0371 10.6229L4.74275 3.32851C4.35223 2.93798 3.71906 2.93798 3.32854 3.32851C2.93801 3.71903 2.93801 4.3522 3.32854 4.74272L10.6229 12.0371L3.32856 19.3314C2.93803 19.722 2.93801 20.3551 3.32856 20.7457C3.71908 21.1362 4.35225 21.1362 4.74277 20.7457L12.0371 13.4513L19.3315 20.7457C19.722 21.1362 20.3552 21.1362 20.7457 20.7457C21.1362 20.3551 21.1362 19.722 20.7457 19.3315L13.4513 12.0371L20.7457 4.74272C21.1362 4.3522 21.1362 3.71903 20.7457 3.32851Z" fill="currentColor"/>
                            </svg>
                        </button>
                    `;
                    selectedList.appendChild(pill);
                });
            }

            updateCounters();
            syncHiddenInputs();
            syncCardState();
        };

        const addDealership = (id) => {
            if (selected.has(id)) {
                return;
            }

            const card = findCardById(id);
            if (!card || card.dataset.dealershipBlocked === '1') {
                return;
            }

            selected.set(id, getCardData(card));
            renderSelected();
        };

        const removeDealership = (id) => {
            if (!selected.has(id)) {
                return;
            }

            selected.delete(id);
            renderSelected();
        };

        const applySearch = () => {
            const term = searchInput.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach((card) => {
                const matches = term === '' || (card.dataset.searchIndex || '').includes(term);
                card.classList.toggle('hidden', !matches);

                if (matches) {
                    visible += 1;
                }
            });

            if (clearButton) {
                clearButton.classList.toggle('hidden', term === '');
            }

            if (resultCount) {
                resultCount.textContent = `${visible} ${visible === 1 ? 'delegación' : 'delegaciones'}`;
            }

            if (emptyState) {
                emptyState.classList.toggle('hidden', visible !== 0);
            }
        };

        searchInput.addEventListener('input', applySearch);

        clearButton?.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.focus();
            applySearch();
        });

        cards.forEach((card) => {
            const button = card.querySelector('[data-zone-add-dealership]');
            button?.addEventListener('click', () => addDealership(String(card.dataset.dealershipId)));
        });

        root.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-zone-remove-dealership]');

            if (!removeButton) {
                return;
            }

            removeDealership(String(removeButton.dataset.dealershipId));
        });

        renderSelected();
        applySearch();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initZoneForm);
    } else {
        initZoneForm();
    }
</script>
