<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $statePath = $getStatePath();
        $mentionableUsers = $getViewData()['mentionableUsers'] ?? [];
        $defaultAvatarUrl = asset('images/users/hrmotor-default-user-avatar.png');
        $composerId = 'bulletin-mention-' . md5($statePath);
        $hiddenInputId = $composerId . '-state';
    @endphp

    <input
        type="hidden"
        id="{{ $hiddenInputId }}"
        {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
        value="{{ e($getState() ?? '') }}"
    >

    <div
        class="relative w-full min-w-0"
        style="position:relative;width:100%;min-width:0;min-height:18rem;"
        id="{{ $composerId }}"
        wire:ignore
        data-bulletin-mention-composer
        data-state-path="{{ $statePath }}"
        data-hidden-input-id="{{ $hiddenInputId }}"
        data-users='@json($mentionableUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'
    >
        <div class="relative w-full min-w-0 overflow-hidden rounded-2xl border border-gray-300 bg-white"
            style="position:relative;width:100%;min-width:0;overflow:hidden;min-height:18rem;">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div
                    class="h-full w-full whitespace-pre-wrap break-words px-4 py-3 text-sm leading-7 text-brand-secondary/80 will-change-transform"
                    style="position:absolute;inset:0;overflow:hidden;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;"
                    data-bulletin-body-mirror
                ></div>
            </div>
            <textarea
                id="{{ $getId() }}"
                data-bulletin-mention-input
                rows="12"
                maxlength="{{ $getMaxLength() ?? 10000 }}"
                required
                class="relative z-10 block w-full min-w-0 resize-y border-0 bg-transparent px-4 py-3 text-sm leading-7 text-transparent caret-brand-primary outline-none placeholder:text-brand-secondary/35 focus:ring-0"
                style="display:block;width:100%;min-width:0;min-height:18rem;box-sizing:border-box;color:transparent;-webkit-text-fill-color:transparent;"
                placeholder="Escribe aquí el anuncio..."
            >{{ $getState() }}</textarea>
        </div>

        <div
            class="absolute bottom-full left-0 right-0 z-20 mb-2 hidden w-full overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-2xl"
            data-bulletin-mention-panel
            aria-hidden="true"
        >
            <div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                Sugerencias
            </div>
            <div class="max-h-72 overflow-y-auto p-2" data-bulletin-mention-list></div>
        </div>

        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">
            Puedes usar <span class="font-semibold text-sky-600">@Nombre Apellido</span> para mencionar usuarios. Pulsa <span class="font-semibold text-brand-secondary">Tab</span> o <span class="font-semibold text-brand-secondary">Enter</span> para autocompletar.
        </p>
    </div>

    <script>
        (() => {
            const composerId = @js($composerId);
            const composer = document.getElementById(composerId);

            if (!composer || !composer.matches?.('[data-bulletin-mention-composer]')) {
                return;
            }

            if (composer.dataset.bulletinMentionReady === '1') {
                return;
            }

            composer.dataset.bulletinMentionReady = '1';

            const textarea = composer.querySelector('[data-bulletin-mention-input]');
            const mirror = composer.querySelector('[data-bulletin-body-mirror]');
            const panel = composer.querySelector('[data-bulletin-mention-panel]');
            const list = composer.querySelector('[data-bulletin-mention-list]');
            const hiddenInput = document.getElementById(composer.dataset.hiddenInputId || '');
            const users = JSON.parse(composer.dataset.users || '[]');
            const defaultAvatarUrl = @js($defaultAvatarUrl);

            if (!textarea || !mirror || !panel || !list || !hiddenInput) {
                return;
            }

            const state = {
                isOpen: false,
                query: '',
                start: 0,
                end: 0,
                selectedIndex: 0,
                suggestions: [],
            };

            const normalizeText = (value) => String(value || '')
                .toLocaleLowerCase('es-ES')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const escapeHtml = (value) => String(value || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const syncMirrorScroll = () => {
                mirror.style.transform = `translate(${-textarea.scrollLeft}px, ${-textarea.scrollTop}px)`;
            };

            const renderMirrorHtml = (value) => {
                const body = String(value || '');

                if (body === '') {
                    return '<span class="text-brand-secondary/35">Escribe aquí el anuncio...</span>';
                }

                const sortedUsers = [...users]
                    .filter((user) => user && typeof user.name === 'string' && user.name.trim() !== '')
                    .sort((left, right) => right.name.length - left.name.length);

                let escapedBody = escapeHtml(body);

                sortedUsers.forEach((user) => {
                    const name = user.name.trim();
                    const escapedMention = escapeHtml(`@${name}`);
                    const mentionMarkup = `<span class="font-semibold text-sky-600">@${escapeHtml(name)}</span>`;
                    escapedBody = escapedBody.replaceAll(escapedMention, mentionMarkup);
                });

                return escapedBody.replace(/\r?\n/g, '<br>');
            };

            const syncMirror = () => {
                mirror.innerHTML = renderMirrorHtml(textarea.value);
                syncMirrorScroll();
            };

            const syncState = () => {
                hiddenInput.value = textarea.value;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            };

            const closePanel = () => {
                state.isOpen = false;
                state.query = '';
                state.start = 0;
                state.end = 0;
                state.selectedIndex = 0;
                state.suggestions = [];
                panel.classList.add('hidden');
                panel.setAttribute('aria-hidden', 'true');
                list.innerHTML = '';
            };

            const getCaretPosition = () => textarea.selectionStart ?? textarea.value.length;

            const getActiveMention = () => {
                const caret = getCaretPosition();
                const before = textarea.value.slice(0, caret);
                const match = before.match(/(^|[\s(\[{>])@([^\s@]*)$/u);

                if (!match) {
                    return null;
                }

                const start = before.lastIndexOf('@');

                if (start < 0) {
                    return null;
                }

                return {
                    query: match[2] || '',
                    start,
                    end: caret,
                };
            };

            const filterSuggestions = (query) => {
                const normalizedQuery = normalizeText(query);

                return users
                    .filter((user) => {
                        if (!normalizedQuery) {
                            return true;
                        }

                        const normalizedName = normalizeText(user.name);
                        return normalizedName.includes(normalizedQuery)
                            || normalizedName.split(/\s+/).some((part) => part.startsWith(normalizedQuery));
                    })
                    .slice(0, 8);
            };

            const renderSuggestions = () => {
                list.innerHTML = '';

                if (!state.suggestions.length) {
                    closePanel();
                    return;
                }

                const fragment = document.createDocumentFragment();

                state.suggestions.forEach((user, index) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.dataset.bulletinMentionOption = '1';
                    option.dataset.index = String(index);
                    option.className = [
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition',
                        index === state.selectedIndex ? 'bg-brand-primary/10' : 'hover:bg-slate-50',
                    ].join(' ');

                    const avatar = document.createElement('img');
                    avatar.src = user.avatar_url || defaultAvatarUrl;
                    avatar.alt = `Avatar de ${user.name}`;
                    avatar.className = 'h-10 w-10 rounded-full object-cover ring-1 ring-slate-200';

                    const textWrap = document.createElement('span');
                    textWrap.className = 'min-w-0 flex-1';

                    const name = document.createElement('span');
                    name.className = 'block truncate text-sm font-semibold text-brand-secondary';
                    name.textContent = user.name;

                    const hint = document.createElement('span');
                    hint.className = 'block text-xs text-brand-secondary/50';
                    hint.textContent = 'Pulsa Tab o Enter para insertar';

                    textWrap.appendChild(name);
                    textWrap.appendChild(hint);
                    option.appendChild(avatar);
                    option.appendChild(textWrap);
                    fragment.appendChild(option);
                });

                list.appendChild(fragment);
                panel.classList.remove('hidden');
                panel.setAttribute('aria-hidden', 'false');
                state.isOpen = true;
            };

            const insertMention = (user) => {
                const activeMention = getActiveMention();

                if (!activeMention) {
                    closePanel();
                    return;
                }

                const before = textarea.value.slice(0, activeMention.start);
                const after = textarea.value.slice(activeMention.end);
                const mention = `@${user.name} `;

                textarea.value = `${before}${mention}${after}`;
                const nextCaret = before.length + mention.length;
                textarea.focus();
                textarea.setSelectionRange(nextCaret, nextCaret);
                closePanel();
                syncMirror();
                syncState();
            };

            const updateSuggestions = () => {
                const activeMention = getActiveMention();

                if (!activeMention) {
                    closePanel();
                    return;
                }

                state.query = activeMention.query;
                state.start = activeMention.start;
                state.end = activeMention.end;
                state.suggestions = filterSuggestions(activeMention.query);
                state.selectedIndex = Math.min(state.selectedIndex, Math.max(state.suggestions.length - 1, 0));

                renderSuggestions();
            };

            textarea.addEventListener('input', () => {
                syncMirror();
                syncState();
                updateSuggestions();
            });

            textarea.addEventListener('scroll', syncMirrorScroll);
            textarea.addEventListener('click', updateSuggestions);
            textarea.addEventListener('keyup', updateSuggestions);

            textarea.addEventListener('keydown', (event) => {
                if (!state.isOpen || !state.suggestions.length) {
                    return;
                }

                if (event.key === 'Tab' || event.key === 'Enter') {
                    event.preventDefault();
                    insertMention(state.suggestions[state.selectedIndex] || state.suggestions[0]);
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    state.selectedIndex = (state.selectedIndex + 1) % state.suggestions.length;
                    renderSuggestions();
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    state.selectedIndex = (state.selectedIndex - 1 + state.suggestions.length) % state.suggestions.length;
                    renderSuggestions();
                    return;
                }

                if (event.key === 'Escape') {
                    closePanel();
                }
            });

            list.addEventListener('mousedown', (event) => {
                const option = event.target.closest('[data-bulletin-mention-option]');

                if (!option) {
                    return;
                }

                event.preventDefault();
                const index = Number(option.dataset.index || 0);
                insertMention(state.suggestions[index] || state.suggestions[0]);
            });

            document.addEventListener('click', (event) => {
                if (composer.contains(event.target)) {
                    return;
                }

                closePanel();
            });

            syncMirror();
        })();
    </script>
</x-dynamic-component>
