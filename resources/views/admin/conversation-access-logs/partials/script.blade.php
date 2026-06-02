<script>
    (() => {
        const root = document.getElementById('admin-logs-view');

        if (!root) {
            return;
        }

        const filterForm = root.querySelector('[data-logs-filter-form]');
        const exportLink = root.querySelector('[data-logs-export-link]');
        const resetLink = root.querySelector('[data-logs-reset]');
        const container = document.getElementById('admin-logs-container');

        if (!filterForm || !container) {
            return;
        }

        const updateExportLink = () => {
            if (!exportLink) {
                return;
            }

            const formData = new FormData(filterForm);
            const params = new URLSearchParams();

            ['date_from', 'date_to', 'user'].forEach((key) => {
                const value = formData.get(key);

                if (value) {
                    params.set(key, String(value));
                }
            });

            const url = new URL(exportLink.getAttribute('href'), window.location.origin);
            url.search = params.toString();
            exportLink.setAttribute('href', url.toString());
        };

        filterForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const url = new URL(filterForm.action, window.location.origin);
            const formData = new FormData(filterForm);

            formData.forEach((value, key) => {
                if (value) {
                    url.searchParams.set(key, String(value));
                }
            });

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const payload = await response.json();
            container.innerHTML = payload.html;
            updateExportLink();
        });

        filterForm.addEventListener('change', updateExportLink);

        if (resetLink) {
            resetLink.addEventListener('click', () => {
                window.requestAnimationFrame(updateExportLink);
            });
        }

        updateExportLink();
    })();
</script>
