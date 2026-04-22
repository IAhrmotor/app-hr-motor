<script>
    window.initAdminLogsFilters = function () {
        const root = document.getElementById('admin-logs-view');

        if (!root) {
            return;
        }

        const form = root.querySelector('[data-logs-filter-form]');

        if (!form || form.dataset.initialized === 'true') {
            return;
        }

        form.dataset.initialized = 'true';

        const actorSelect = form.querySelector('select[name="actor"]');
        const dateFromInput = form.querySelector('[data-date-input="from"]');
        const dateToInput = form.querySelector('[data-date-input="to"]');
        const actorDisplay = form.querySelector('[data-display-actor]');
        const rangeLabelDisplay = form.querySelector('[data-display-range-label]');
        const dateFromDisplay = form.querySelector('[data-display-date-from]');
        const dateToDisplay = form.querySelector('[data-display-date-to]');
        const submitButton = form.querySelector('[data-filter-submit]');
        const resetLink = form.querySelector('[data-logs-reset]');
        const exportLink = document.querySelector('[data-logs-export-link]');
        const dateButtons = {
            from: form.querySelector('[data-date-trigger="from"]'),
            to: form.querySelector('[data-date-trigger="to"]'),
        };

        const formatDate = (value) => {
            if (!value) {
                return null;
            }

            const [year, month, day] = value.split('-');
            return `${day}/${month}/${year}`;
        };

        const updateSelectDisplay = (select, display, fallback) => {
            if (!select || !display) {
                return;
            }

            display.textContent = select.options[select.selectedIndex]?.text || fallback;
        };

        const updateRangeState = () => {
            const fromValue = dateFromInput?.value || '';
            const toValue = dateToInput?.value || '';
            const hasRange = Boolean(fromValue || toValue);
            const fromText = formatDate(fromValue) ?? 'Desde';
            const toText = formatDate(toValue) ?? 'Hasta';

            if (dateFromDisplay) {
                dateFromDisplay.textContent = fromText;
            }

            if (dateToDisplay) {
                dateToDisplay.textContent = toText;
            }

            if (rangeLabelDisplay) {
                rangeLabelDisplay.textContent = fromValue && toValue
                    ? `Del ${fromText} al ${toText}`
                    : fromValue
                        ? `Desde ${fromText}`
                        : toValue
                            ? `Hasta ${toText}`
                            : 'Rango de fechas';

                rangeLabelDisplay.classList.toggle('text-brand-primary', hasRange);
                rangeLabelDisplay.classList.toggle('text-brand-secondary/45', !hasRange);
            }

            Object.entries(dateButtons).forEach(([key, button]) => {
                if (!button) {
                    return;
                }

                const isActive = key === 'from' ? Boolean(fromValue) : Boolean(toValue);
                button.classList.toggle('border-brand-primary', isActive);
                button.classList.toggle('bg-white', isActive);
                button.classList.toggle('text-brand-primary', isActive);
                button.classList.toggle('shadow-sm', isActive);
                button.classList.toggle('border-transparent', !isActive);
                button.classList.toggle('bg-transparent', !isActive);
                button.classList.toggle('text-brand-secondary', !isActive);
            });
        };

        const syncDateBounds = (changedField = null) => {
            if (!dateFromInput || !dateToInput) {
                return;
            }

            dateToInput.min = dateFromInput.value || '';
            dateFromInput.max = dateToInput.value || '';

            if (dateFromInput.value && dateToInput.value && dateToInput.value < dateFromInput.value) {
                if (changedField === 'from') {
                    dateToInput.value = dateFromInput.value;
                } else if (changedField === 'to') {
                    dateFromInput.value = dateToInput.value;
                }
            }
        };

        const updateExportLink = () => {
            if (!exportLink) {
                return;
            }

            const exportUrl = new URL(exportLink.dataset.baseHref || exportLink.href, window.location.origin);
            exportUrl.search = new URLSearchParams(new FormData(form)).toString();
            exportLink.href = exportUrl.toString();
        };

        const openSelect = (select) => {
            if (!select) {
                return;
            }

            if (typeof select.showPicker === 'function') {
                select.showPicker();
                return;
            }

            select.focus();
            select.click();
        };

        const replaceView = (html, nextUrl) => {
            const container = document.getElementById('admin-logs-container');

            if (!container) {
                return;
            }

            container.innerHTML = html;

            if (nextUrl) {
                window.history.replaceState({}, '', nextUrl.toString());
            }

            window.initAdminLogsFilters();
        };

        const fetchAndReplace = async (targetUrl) => {
            const response = await fetch(targetUrl.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('No se pudo actualizar el historial.');
            }

            const payload = await response.json();
            replaceView(payload.html, targetUrl);
        };

        if (exportLink && !exportLink.dataset.baseHref) {
            exportLink.dataset.baseHref = exportLink.href;
        }

        syncDateBounds();
        updateSelectDisplay(actorSelect, actorDisplay, 'Todos los gestores/admin');
        updateRangeState();
        updateExportLink();

        actorSelect?.addEventListener('change', () => {
            updateSelectDisplay(actorSelect, actorDisplay, 'Todos los gestores/admin');
            updateExportLink();
        });

        dateFromInput?.addEventListener('change', () => {
            syncDateBounds('from');
            updateRangeState();
            updateExportLink();
        });

        dateToInput?.addEventListener('change', () => {
            syncDateBounds('to');
            updateRangeState();
            updateExportLink();
        });

        [dateFromInput, dateToInput].forEach((input) => {
            input?.addEventListener('input', () => {
                syncDateBounds(input === dateFromInput ? 'from' : 'to');
                updateRangeState();
                updateExportLink();
            });
        });

        [actorSelect].forEach((select) => {
            select?.parentElement?.addEventListener('click', (event) => {
                if (event.target !== select) {
                    openSelect(select);
                }
            });
        });

        Object.entries(dateButtons).forEach(([type, trigger]) => {
            trigger?.addEventListener('click', () => {
                const input = form.querySelector(`[data-date-input="${type}"]`);

                if (!input) {
                    return;
                }

                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                    return;
                }

                input.focus();
                input.click();
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!submitButton) {
                return;
            }

            const label = submitButton.querySelector('span:last-child');
            const previousLabel = label?.textContent;
            submitButton.classList.add('opacity-80', 'pointer-events-none');

            if (label) {
                label.textContent = 'Filtrando...';
            }

            const formAction = form.getAttribute('action') || window.location.href;
            const url = new URL(formAction, window.location.origin);
            url.search = new URLSearchParams(new FormData(form)).toString();

            try {
                await fetchAndReplace(url);
            } catch (error) {
                console.error(error);
            } finally {
                submitButton.classList.remove('opacity-80', 'pointer-events-none');

                if (label && previousLabel) {
                    label.textContent = previousLabel;
                }
            }
        });

        resetLink?.addEventListener('click', async (event) => {
            event.preventDefault();

            try {
                await fetchAndReplace(new URL(resetLink.href, window.location.origin));
            } catch (error) {
                console.error(error);
            }
        });

        root.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', async (event) => {
                event.preventDefault();

                try {
                    await fetchAndReplace(new URL(link.href, window.location.origin));
                } catch (error) {
                    console.error(error);
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.initAdminLogsFilters();
    });
</script>
