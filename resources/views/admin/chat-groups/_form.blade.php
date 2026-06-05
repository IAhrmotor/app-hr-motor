@php
    $group = $group ?? null;
    $availableParticipants = collect($availableParticipants ?? []);
    $selectedParticipantIds = collect(old('participants', $group?->participants?->pluck('id')->all() ?? []))
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();
    $selectedParticipants = $availableParticipants->whereIn('id', array_map('intval', $selectedParticipantIds))->values();
    $selectedParticipantNames = $selectedParticipants->pluck('name')->implode(', ');
@endphp

<div class="space-y-6">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.12fr)_minmax(18rem,0.88fr)] xl:items-stretch">
        <div class="flex h-full flex-col rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Datos del grupo</p>
            <h2 class="mt-2 text-lg font-semibold text-brand-secondary">Configuración básica</h2>

            <div class="mt-5 flex flex-1 flex-col rounded-[1.5rem] border border-brand-secondary/10 bg-white p-4">
                <div class="flex flex-1 flex-col">
                    <label for="name" class="text-sm font-semibold text-brand-secondary">Nombre</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $group->name ?? '') }}"
                        required
                        class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                    >
                </div>

                <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Reglas rápidas</p>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-brand-secondary/70">
                        <li class="rounded-xl border border-brand-secondary/10 bg-white px-3 py-2">Solo se pueden añadir usuarios activos de la aplicación.</li>
                        <li class="rounded-xl border border-brand-secondary/10 bg-white px-3 py-2">El grupo necesita al menos dos participantes para guardarse.</li>
                        <li class="rounded-xl border border-brand-secondary/10 bg-white px-3 py-2">Cada cambio queda registrado en el log de grupos.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex h-full flex-col gap-6">
            <div class="flex-1 rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Vista previa</p>
                <h3 class="mt-1 text-lg font-semibold text-brand-secondary">Cómo quedará el grupo</h3>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70">
                    El grupo mostrará la lista de participantes seleccionados. Puedes quitar cualquiera desde aquí o desde el buscador.
                </p>

                <div class="mt-5 rounded-[1.5rem] border border-brand-secondary/10 bg-white p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 21C5 17.134 8.13401 14 12 14C15.866 14 19 17.134 19 21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-brand-secondary">Miembros seleccionados</p>
                            <p class="text-xs text-brand-secondary/60" data-group-preview-count>{{ count($selectedParticipantIds) }} personas</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm leading-6 text-brand-secondary/70" data-group-preview-text>
                        {{ count($selectedParticipantIds) > 0 ? 'En el grupo estarán ' . $selectedParticipantNames . '.' : 'Selecciona participantes para construir el grupo.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-brand-secondary/10 bg-white shadow-sm">
        <div class="bg-brand-secondary px-5 py-4 text-white">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/65">Participantes</p>
            <h2 class="mt-1 text-lg font-semibold">Busca usuarios y agrégalos al grupo</h2>
            <p class="mt-2 text-sm leading-6 text-white/75">
                Usa el buscador global para encontrar usuarios de la aplicación y añadirlos con un clic.
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
                    placeholder="Buscar por nombre, correo, rol o delegación"
                    autocomplete="off"
                    data-group-user-search
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-28 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                >

                <button
                    type="button"
                    data-group-user-search-clear
                    class="absolute inset-y-0 right-3 my-auto hidden h-9 rounded-xl border border-brand-secondary/10 bg-white px-3 text-xs font-semibold text-brand-secondary transition hover:cursor-pointer hover:bg-brand-secondary/5"
                >
                    Limpiar
                </button>
            </div>

            <div class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Resumen</p>
                        <p class="mt-1 text-sm font-semibold text-brand-secondary">Personas dentro del grupo</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary shadow-sm" data-group-selected-count>
                        {{ count($selectedParticipantIds) }} seleccionados
                    </span>
                </div>

                <p class="mt-4 text-sm leading-6 text-brand-secondary/70" data-group-selected-summary>
                    {{ count($selectedParticipantIds) > 0 ? 'En el grupo estarán ' . $selectedParticipantNames . '.' : 'Selecciona usuarios para construir el grupo.' }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2" data-group-selected-list>
                    @forelse ($selectedParticipants as $participant)
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-brand-primary/15 bg-white px-3 py-1.5 text-sm font-medium text-brand-secondary shadow-sm"
                            data-group-selected-pill="{{ $participant->id }}"
                        >
                            <span class="max-w-[12rem] truncate">{{ $participant->name }}</span>
                            <button
                                type="button"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-secondary/5 text-brand-secondary transition hover:cursor-pointer hover:bg-brand-primary hover:text-white"
                                data-group-remove-participant
                                data-participant-id="{{ $participant->id }}"
                                aria-label="Quitar {{ $participant->name }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 overflow-visible" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M20.7457 3.32851C20.3552 2.93798 19.722 2.93798 19.3315 3.32851L12.0371 10.6229L4.74275 3.32851C4.35223 2.93798 3.71906 2.93798 3.32854 3.32851C2.93801 3.71903 2.93801 4.3522 3.32854 4.74272L10.6229 12.0371L3.32856 19.3314C2.93803 19.722 2.93801 20.3551 3.32856 20.7457C3.71908 21.1362 4.35225 21.1362 4.74277 20.7457L12.0371 13.4513L19.3315 20.7457C19.722 21.1362 20.3552 21.1362 20.7457 20.7457C21.1362 20.3551 21.1362 19.722 20.7457 19.3315L13.4513 12.0371L20.7457 4.74272C21.1362 4.3522 21.1362 3.71903 20.7457 3.32851Z" fill="currentColor"/>
                                </svg>
                            </button>
                        </span>
                    @empty
                        <span class="text-sm text-brand-secondary/60">Todavía no hay participantes añadidos.</span>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Buscador global</p>
                        <h3 class="mt-1 text-sm font-semibold text-brand-secondary">Usuarios disponibles</h3>
                    </div>
                    <p class="text-xs text-brand-secondary/60" data-group-user-results-count>
                        {{ $availableParticipants->count() }} usuarios
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5" data-group-user-results>
                    @foreach ($availableParticipants as $participant)
                        @php
                            $searchIndex = strtolower(trim(implode(' ', array_filter([
                                $participant->name,
                                $participant->email,
                                $participant->chat_role_label ?? null,
                                $participant->resolved_dealership_name ?? null,
                                $participant->phone ?? null,
                                $participant->enreach_extension ?? null,
                            ]))));
                        @endphp

                        <article
                            class="group flex h-full flex-col rounded-2xl border border-brand-secondary/10 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            data-group-user-card
                            data-search-index="{{ $searchIndex }}"
                            data-participant-id="{{ $participant->id }}"
                            data-participant-name="{{ $participant->name }}"
                        >
                            <div class="flex items-start gap-3">
                                <img src="{{ $participant->avatar_url }}" alt="Avatar de {{ $participant->name }}" class="h-11 w-11 rounded-2xl object-cover ring-1 ring-brand-secondary/10">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-brand-secondary">{{ $participant->name }}</p>
                                    <p class="truncate text-xs text-brand-secondary/60">{{ $participant->email }}</p>
                                    <p class="mt-1 truncate text-xs text-brand-secondary/60">
                                        {{ $participant->chat_role_label ?? 'Sin rol' }}{{ $participant->resolved_dealership_name ? ' - ' . $participant->resolved_dealership_name : '' }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:cursor-pointer hover:opacity-90 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                                data-group-add-participant
                                data-participant-id="{{ $participant->id }}"
                            >
                                Añadir al grupo
                            </button>
                        </article>
                    @endforeach
                </div>

                <p class="mt-3 hidden text-sm text-brand-secondary/60" data-group-user-empty-state>
                    No hay usuarios que coincidan con esa búsqueda.
                </p>
            </div>
        </div>
    </div>

    <div data-group-hidden-participants>
        @foreach ($selectedParticipantIds as $participantId)
            <input type="hidden" name="participants[]" value="{{ $participantId }}" data-group-hidden-participant="{{ $participantId }}">
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-chat-groups-root]') || document;
        const searchInput = root.querySelector('[data-group-user-search]');
        const clearButton = root.querySelector('[data-group-user-search-clear]');
        const cards = Array.from(root.querySelectorAll('[data-group-user-card]'));
        const selectedList = root.querySelector('[data-group-selected-list]');
        const selectedSummary = root.querySelector('[data-group-selected-summary]');
        const selectedCount = root.querySelector('[data-group-selected-count]');
        const previewCount = root.querySelector('[data-group-preview-count]');
        const previewText = root.querySelector('[data-group-preview-text]');
        const hiddenWrapper = root.querySelector('[data-group-hidden-participants]');
        const resultCount = root.querySelector('[data-group-user-results-count]');
        const emptyState = root.querySelector('[data-group-user-empty-state]');

        if (!searchInput || !selectedList || !selectedSummary || !selectedCount || !previewCount || !previewText || !hiddenWrapper) {
            return;
        }

        const findCardById = (id) => cards.find((card) => String(card.dataset.participantId) === String(id));
        const getCardData = (card) => ({
            id: String(card.dataset.participantId),
            name: card.dataset.participantName || card.querySelector('p.text-sm.font-semibold')?.textContent?.trim() || '',
        });

        const selected = new Map();
        const initialHiddenInputs = Array.from(hiddenWrapper.querySelectorAll('input[data-group-hidden-participant]'));

        initialHiddenInputs.forEach((input) => {
            const card = findCardById(String(input.value));
            selected.set(String(input.value), {
                id: String(input.value),
                name: card ? getCardData(card).name : '',
            });
        });

        const updateCounters = () => {
            const count = selected.size;
            selectedCount.textContent = `${count} seleccionados`;
            previewCount.textContent = `${count} persona${count === 1 ? '' : 's'}`;

            const names = Array.from(selected.values()).map((item) => item.name).filter(Boolean);
            selectedSummary.textContent = count > 0
                ? `En el grupo estarán ${names.join(', ')}.`
                : 'Selecciona usuarios para construir el grupo.';
            previewText.textContent = selectedSummary.textContent;
        };

        const syncHiddenInputs = () => {
            hiddenWrapper.innerHTML = '';

            Array.from(selected.keys()).forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'participants[]';
                input.value = id;
                input.dataset.groupHiddenParticipant = id;
                hiddenWrapper.appendChild(input);
            });
        };

        const syncCardState = () => {
            cards.forEach((card) => {
                const id = String(card.dataset.participantId);
                const button = card.querySelector('[data-group-add-participant]');
                const isSelected = selected.has(id);

                if (button) {
                    button.disabled = isSelected;
                    button.textContent = isSelected ? 'Añadido' : 'Añadir al grupo';
                }

                card.classList.toggle('ring-2', isSelected);
                card.classList.toggle('ring-brand-primary/20', isSelected);
            });
        };

        const renderSelected = () => {
            selectedList.innerHTML = '';

            if (selected.size === 0) {
                const empty = document.createElement('span');
                empty.className = 'text-sm text-brand-secondary/60';
                empty.textContent = 'Todavía no hay participantes añadidos.';
                selectedList.appendChild(empty);
            } else {
                selected.forEach((item) => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-flex items-center gap-2 rounded-full border border-brand-primary/15 bg-white px-3 py-1.5 text-sm font-medium text-brand-secondary shadow-sm';
                    pill.dataset.groupSelectedPill = item.id;
                    pill.innerHTML = `
                        <span class="max-w-[12rem] truncate">${item.name}</span>
                        <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-secondary/5 text-brand-secondary transition hover:cursor-pointer hover:bg-brand-primary hover:text-white" data-group-remove-participant data-participant-id="${item.id}" aria-label="Quitar ${item.name}">
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

        const addParticipant = (id) => {
            if (selected.has(id)) {
                return;
            }

            const card = findCardById(id);
            if (!card) {
                return;
            }

            selected.set(id, getCardData(card));
            renderSelected();
        };

        const removeParticipant = (id) => {
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
                resultCount.textContent = `${visible} usuario${visible === 1 ? '' : 's'}`;
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
            const button = card.querySelector('[data-group-add-participant]');
            button?.addEventListener('click', () => addParticipant(String(card.dataset.participantId)));
        });

        root.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-group-remove-participant]');

            if (!removeButton) {
                return;
            }

            removeParticipant(String(removeButton.dataset.participantId));
        });

        renderSelected();
        applySearch();
    });
</script>
