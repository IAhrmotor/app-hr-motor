import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.bodyScrollLock = {
    locks: new Set(),

    update() {
        if (typeof document === 'undefined' || !document.body) {
            return;
        }

        document.body.classList.toggle('overflow-hidden', this.locks.size > 0);
    },

    set(key, locked) {
        if (locked) {
            this.locks.add(key);
        } else {
            this.locks.delete(key);
        }

        this.update();
    },

    release(key) {
        this.locks.delete(key);
        this.update();
    },
};
window.imageLightbox = () => ({
    isImageOpen: false,
    imageUrl: '',
    imageAlt: '',
    imageTitle: '',
    imageScale: 1,
    translateX: 0,
    translateY: 0,
    minScale: 1,
    maxScale: 4,
    scaleStep: 0.25,
    isDragging: false,
    dragPointerId: null,
    dragStartX: 0,
    dragStartY: 0,
    dragOriginX: 0,
    dragOriginY: 0,
    dragSensitivity: 2.0,
    activePointers: {},
    clampScale(value) {
        return Math.min(this.maxScale, Math.max(this.minScale, Number(value.toFixed(2))));
    },
    resetTransform() {
        this.imageScale = 1;
        this.translateX = 0;
        this.translateY = 0;
        this.isDragging = false;
        this.dragPointerId = null;
        this.activePointers = {};
        this.pinchStartDistance = 0;
        this.pinchStartScale = 1;
    },
    getViewportRect() {
        return this.$refs.imageViewport?.getBoundingClientRect?.() ?? null;
    },
    getViewportCenter() {
        const rect = this.getViewportRect();

        if (!rect) {
            return { x: 0, y: 0 };
        }

        return {
            x: rect.left + rect.width / 2,
            y: rect.top + rect.height / 2,
        };
    },
    getPointerList() {
        return Object.values(this.activePointers);
    },
    getDistance(a, b) {
        return Math.hypot(a.x - b.x, a.y - b.y);
    },
    setZoom(nextScale, anchorX = null, anchorY = null) {
        const clampedScale = this.clampScale(nextScale);
        const rect = this.getViewportRect();

        if (!rect || this.imageScale === clampedScale) {
            this.imageScale = clampedScale;
            if (clampedScale === 1) {
                this.translateX = 0;
                this.translateY = 0;
            }
            return;
        }

        if (clampedScale === 1) {
            this.imageScale = 1;
            this.translateX = 0;
            this.translateY = 0;
            return;
        }

        const center = this.getViewportCenter();
        const pointX = anchorX ?? center.x;
        const pointY = anchorY ?? center.y;
        const factor = clampedScale / this.imageScale;
        const offsetX = pointX - center.x;
        const offsetY = pointY - center.y;

        this.translateX = (this.translateX * factor) + (offsetX * (1 - factor));
        this.translateY = (this.translateY * factor) + (offsetY * (1 - factor));
        this.imageScale = clampedScale;
    },
    openImage(payload = {}) {
        this.imageUrl = payload.src ?? '';
        this.imageAlt = payload.alt ?? '';
        this.imageTitle = payload.title ?? '';
        this.resetTransform();
        this.isImageOpen = true;
    },
    closeImage() {
        this.isImageOpen = false;
        this.resetTransform();
    },
    async downloadImage() {
        if (!this.imageUrl) {
            return;
        }

        const safeName = String(this.imageTitle || this.imageAlt || 'imagen')
            .trim()
            .replace(/[<>:"/\\|?*\u0000-\u001F]+/g, '-')
            .replace(/\s+/g, ' ')
            .replace(/\.+$/g, '');

        try {
            const response = await fetch(this.imageUrl, {
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Unable to download image');
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = objectUrl;
            link.download = safeName || 'imagen';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();

            window.setTimeout(() => {
                URL.revokeObjectURL(objectUrl);
            }, 1000);
        } catch (error) {
            const link = document.createElement('a');

            link.href = this.imageUrl;
            link.download = safeName || 'imagen';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }
    },
    zoomIn(clientX = null, clientY = null) {
        const rect = this.getViewportRect();
        const anchorX = clientX ?? (rect ? rect.left + rect.width / 2 : null);
        const anchorY = clientY ?? (rect ? rect.top + rect.height / 2 : null);

        this.setZoom(this.imageScale + this.scaleStep, anchorX, anchorY);
    },
    zoomOut(clientX = null, clientY = null) {
        const rect = this.getViewportRect();
        const anchorX = clientX ?? (rect ? rect.left + rect.width / 2 : null);
        const anchorY = clientY ?? (rect ? rect.top + rect.height / 2 : null);

        this.setZoom(this.imageScale - this.scaleStep, anchorX, anchorY);
    },
    resetZoom() {
        this.resetTransform();
    },
    toggleZoom(clientX = null, clientY = null) {
        if (this.imageScale === 1) {
            this.setZoom(2, clientX, clientY);
            return;
        }

        this.resetTransform();
    },
    handleWheel(event) {
        if (event.deltaY < 0) {
            this.zoomIn(event.clientX, event.clientY);
            return;
        }

        this.zoomOut(event.clientX, event.clientY);
    },
    handleKeydown(event) {
        if (!this.isImageOpen) {
            return;
        }

        switch (event.key) {
            case '+':
            case '=':
                event.preventDefault();
                this.zoomIn();
                break;
            case '-':
            case '_':
                event.preventDefault();
                this.zoomOut();
                break;
            case '0':
                event.preventDefault();
                this.resetZoom();
                break;
            case 'ArrowLeft':
                event.preventDefault();
                this.panBy(48, 0);
                break;
            case 'ArrowRight':
                event.preventDefault();
                this.panBy(-48, 0);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.panBy(0, 48);
                break;
            case 'ArrowDown':
                event.preventDefault();
                this.panBy(0, -48);
                break;
            default:
                break;
        }
    },
    panBy(deltaX, deltaY) {
        if (this.imageScale <= 1) {
            return;
        }

        this.translateX += deltaX;
        this.translateY += deltaY;
    },
    handlePointerDown(event) {
        if (!this.isImageOpen || !this.imageUrl) {
            return;
        }

        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        this.activePointers[event.pointerId] = { id: event.pointerId, x: event.clientX, y: event.clientY };
        event.currentTarget?.setPointerCapture?.(event.pointerId);

        const pointers = this.getPointerList();

        if (pointers.length === 1) {
            if (this.imageScale <= 1) {
                return;
            }

            this.isDragging = true;
            this.dragPointerId = event.pointerId;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.dragOriginX = this.translateX;
            this.dragOriginY = this.translateY;
            return;
        }

        if (pointers.length >= 2) {
            this.isDragging = false;
            this.dragPointerId = null;
            this.pinchStartDistance = this.getDistance(pointers[0], pointers[1]);
            this.pinchStartScale = this.imageScale;
        }
    },
    handlePointerMove(event) {
        if (!this.isImageOpen || !this.activePointers[event.pointerId]) {
            return;
        }

        this.activePointers[event.pointerId] = { id: event.pointerId, x: event.clientX, y: event.clientY };

        const pointers = this.getPointerList();

        if (pointers.length === 1 && this.isDragging && event.pointerId === this.dragPointerId) {
            this.translateX = this.dragOriginX + ((event.clientX - this.dragStartX) * this.dragSensitivity);
            this.translateY = this.dragOriginY + ((event.clientY - this.dragStartY) * this.dragSensitivity);
            return;
        }

        if (pointers.length >= 2) {
            const [first, second] = pointers;
            const distance = Math.max(1, this.getDistance(first, second));
            const midpoint = {
                x: (first.x + second.x) / 2,
                y: (first.y + second.y) / 2,
            };
            const scaleMultiplier = distance / (this.pinchStartDistance || distance);
            const nextScale = this.clampScale((this.pinchStartScale || this.imageScale) * scaleMultiplier);

            this.setZoom(nextScale, midpoint.x, midpoint.y);
        }
    },
    handlePointerUp(event) {
        if (!this.activePointers[event.pointerId]) {
            return;
        }

        delete this.activePointers[event.pointerId];

        if (this.dragPointerId === event.pointerId) {
            this.isDragging = false;
            this.dragPointerId = null;
        }

        const pointers = this.getPointerList();

        if (pointers.length >= 2) {
            this.pinchStartDistance = this.getDistance(pointers[0], pointers[1]);
            this.pinchStartScale = this.imageScale;
            return;
        }

        if (pointers.length === 1 && this.imageScale > 1) {
            const pointer = pointers[0];
            this.isDragging = true;
            this.dragPointerId = pointer.id;
            this.dragStartX = pointer.x;
            this.dragStartY = pointer.y;
            this.dragOriginX = this.translateX;
            this.dragOriginY = this.translateY;
            return;
        }

        this.isDragging = false;
        this.dragPointerId = null;
        this.pinchStartDistance = 0;
        this.pinchStartScale = 1;
    },
    pinchStartDistance: 0,
    pinchStartScale: 1,
    handlePointerCancel(event) {
        this.handlePointerUp(event);
    },
});

window.agendaSearch = (agendaUrl, initialSearch = '') => ({
    search: initialSearch,
    isLoading: false,
    searchTimeout: null,
    abortController: null,
    lastRequestKey: '',

    init() {
        this.$watch('search', () => {
            this.queueSearch();
        });

        this.$refs.resultsWrapper?.addEventListener('click', (event) => {
            this.handleResultsClick(event);
        });
    },

    queueSearch() {
        clearTimeout(this.searchTimeout);

        this.searchTimeout = setTimeout(() => {
            this.loadResults({ page: 1 });
        }, 250);
    },

    async loadResults({ page = 1, updateHistory = true } = {}) {
        const search = this.search.trim();
        const requestUrl = new URL(agendaUrl, window.location.origin);

        if (search !== '') {
            requestUrl.searchParams.set('search', search);
        }

        if (Number(page) > 1) {
            requestUrl.searchParams.set('page', page);
        }

        requestUrl.searchParams.set('ajax', '1');

        const requestKey = requestUrl.searchParams.toString();

        if (requestKey === this.lastRequestKey) {
            return;
        }

        this.lastRequestKey = requestKey;

        if (this.abortController) {
            this.abortController.abort();
        }

        const controller = new AbortController();
        this.abortController = controller;
        this.isLoading = true;

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar la agenda');
            }

            const payload = await response.json();

            if (this.abortController !== controller) {
                return;
            }

            this.$refs.resultsWrapper.innerHTML = payload.html;

            if (updateHistory) {
                const historyUrl = new URL(agendaUrl, window.location.origin);

                if (search !== '') {
                    historyUrl.searchParams.set('search', search);
                }

                if (Number(page) > 1) {
                    historyUrl.searchParams.set('page', page);
                }

                window.history.replaceState({}, '', historyUrl.toString());
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        } finally {
            if (this.abortController === controller) {
                this.isLoading = false;
            }
        }
    },

    handleResultsClick(event) {
        const link = event.target.closest('a[href]');

        if (!link || !this.$refs.resultsWrapper.contains(link)) {
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
        this.loadResults({ page });
    },

    clearSearch() {
        if (this.search === '') {
            return;
        }

        this.search = '';
        clearTimeout(this.searchTimeout);
        this.loadResults({ page: 1 });
    },
});

Alpine.start();
