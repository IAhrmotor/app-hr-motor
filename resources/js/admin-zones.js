const setupZoneForm = (root) => {
    if (!root || root.dataset.zoneFormInitialized === '1') {
        return;
    }

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

    root.dataset.zoneFormInitialized = '1';

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
        previewCount.textContent = `${count} delegaciÃ³n${count === 1 ? '' : 'es'}`;

        const names = Array.from(selected.values()).map((item) => item.name).filter(Boolean);
        selectedSummary.textContent = count > 0
            ? `En la zona estarÃ¡n ${names.join(', ')}.`
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
                button.setAttribute('aria-disabled', String(isSelected || isBlocked));
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

                const removeButton = pill.querySelector('[data-zone-remove-dealership]');
                if (removeButton) {
                    removeButton.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        removeDealership(item.id);
                    });
                }

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
            resultCount.textContent = `${visible} delegaciÃ³n${visible === 1 ? '' : 'es'}`;
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
            button?.addEventListener('click', () => {
                addDealership(String(card.dataset.dealershipId));
            });
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

const initZoneForms = () => {
    document.querySelectorAll('[data-zone-form-root]').forEach((root) => {
        setupZoneForm(root);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initZoneForms);
} else {
    initZoneForms();
}
