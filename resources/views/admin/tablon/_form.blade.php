@php
    $titleValue = old('title', $post->title);
    $bodyValue = old('body', $post->body);
    $isPublishedValue = old('is_published', $post->exists ? $post->is_published : false);
@endphp

<div class="grid gap-6">
    <div>
        <label for="title" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
            T&iacute;tulo
        </label>
        <input
            id="title"
            name="title"
            type="text"
            value="{{ $titleValue }}"
            maxlength="140"
            required
            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
            placeholder="Aviso importante"
        >
    </div>

    <div class="relative" data-bulletin-mention-composer>
        <label for="body" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
            Contenido
        </label>

        <div class="relative overflow-hidden rounded-2xl border border-gray-300 bg-white">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div
                    class="whitespace-pre-wrap break-words px-4 py-3 text-sm leading-7 text-brand-secondary/80 will-change-transform"
                    data-bulletin-body-mirror
                >{{ $bodyValue }}</div>
            </div>
            <textarea
                id="body"
                name="body"
                data-bulletin-mention-input
                rows="12"
                maxlength="10000"
                required
                class="relative z-10 w-full resize-y border-0 bg-transparent px-4 py-3 text-sm leading-7 text-transparent caret-brand-primary outline-none placeholder:text-brand-secondary/35 focus:ring-0"
                placeholder="Escribe aqu&iacute; el anuncio..."
            >{{ $bodyValue }}</textarea>
        </div>

        <div
            class="absolute bottom-full left-0 right-0 z-20 mb-2 hidden overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-2xl"
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

    <div>
        <label for="images" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
            Im&aacute;genes
        </label>
        <input
            id="images"
            name="images[]"
            type="file"
            accept="image/*"
            multiple
            class="block w-full cursor-pointer rounded-2xl border border-dashed border-brand-secondary/20 bg-slate-50 px-4 py-3 text-sm text-brand-secondary file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-brand-secondary/30"
        >
        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">
            Puedes subir varias im&aacute;genes JPG, PNG, WEBP o GIF. Se mostrar&aacute;n en la publicaci&oacute;n.
        </p>
        @error('images')
            <p class="mt-2 pl-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('images.*')
            <p class="mt-2 pl-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($post->exists && $post->attachments->isNotEmpty())
        <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-brand-secondary">Fotos actuales</p>
                    <p class="mt-1 text-xs text-brand-secondary/60">Desmarca las que quieras eliminar al guardar.</p>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">
                    {{ $post->attachments->count() }} fotos
                </span>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($post->attachments as $attachment)
                    <div class="overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <img
                            src="{{ $attachment->image_url }}"
                            alt="{{ $post->title }}"
                            class="h-44 w-full object-cover"
                        >
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <label class="flex items-center gap-2 text-sm font-medium text-brand-secondary">
                                <input type="checkbox" name="keep_attachment_ids[]" value="{{ $attachment->id }}" @checked(true) class="h-4 w-4 rounded border-gray-300 text-brand-primary focus:ring-brand-primary/20">
                                Mantener foto
                            </label>
                            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">
                                Desmarcar para borrar
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-4">
        <p class="text-sm font-semibold text-brand-secondary">Estado de la publicaci&oacute;n</p>
        <p class="mt-1 text-xs text-brand-secondary/60">Elige si se guarda como borrador o si se publica al guardar.</p>

        <input type="hidden" name="is_published" value="0">
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label data-bulletin-status-card="draft" class="flex cursor-pointer items-center gap-3 rounded-2xl border px-4 py-3 transition {{ ! (bool) $isPublishedValue ? 'border-brand-primary bg-brand-primary/5 ring-1 ring-brand-primary/10' : 'border-brand-secondary/10 bg-white hover:border-brand-secondary/20' }}">
                <input
                    type="radio"
                    name="is_published"
                    value="0"
                    data-bulletin-status-input="draft"
                    @checked(! (bool) $isPublishedValue)
                    class="h-4 w-4 border-gray-300 text-brand-primary focus:ring-brand-primary/20"
                >
                <span class="block">
                    <span class="block text-sm font-semibold text-brand-secondary">Guardar como borrador</span>
                    <span class="mt-1 block text-xs text-brand-secondary/60">Solo se ver&aacute; en admin hasta que lo publiques.</span>
                </span>
            </label>

            <label data-bulletin-status-card="published" class="flex cursor-pointer items-center gap-3 rounded-2xl border px-4 py-3 transition {{ (bool) $isPublishedValue ? 'border-brand-primary bg-brand-primary/5 ring-1 ring-brand-primary/10' : 'border-brand-secondary/10 bg-white hover:border-brand-secondary/20' }}">
                <input
                    type="radio"
                    name="is_published"
                    value="1"
                    data-bulletin-status-input="published"
                    @checked((bool) $isPublishedValue)
                    class="h-4 w-4 border-gray-300 text-brand-primary focus:ring-brand-primary/20"
                >
                <span class="block">
                    <span class="block text-sm font-semibold text-brand-secondary">Publicar ahora</span>
                    <span class="mt-1 block text-xs text-brand-secondary/60">Aparecer&aacute; al instante en el tabl&oacute;n p&uacute;blico.</span>
                </span>
            </label>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-bulletin-mention-composer]').forEach((composer) => {
        const textarea = composer.querySelector('[data-bulletin-mention-input]');
        const mirror = composer.querySelector('[data-bulletin-body-mirror]');
        const panel = composer.querySelector('[data-bulletin-mention-panel]');
        const list = composer.querySelector('[data-bulletin-mention-list]');
        const users = @js($mentionableUsers ?? []);
        const statusInputs = Array.from(document.querySelectorAll('[data-bulletin-status-input]'));
        const statusCards = new Map(
            Array.from(document.querySelectorAll('[data-bulletin-status-card]')).map((card) => [card.dataset.bulletinStatusCard, card]),
        );

        if (!textarea || !mirror || !panel || !list) {
            return;
        }

        const defaultAvatarUrl = @js(asset('images/users/hrmotor-default-user-avatar.png'));
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
                return '<span class="text-brand-secondary/35">Escribe aqu&iacute; el anuncio...</span>';
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

        const syncPublicationStatus = () => {
            const selected = statusInputs.find((input) => input.checked)?.value === '1' ? 'published' : 'draft';

            statusCards.forEach((card, key) => {
                const isSelected = key === selected;

                card.classList.toggle('border-brand-primary', isSelected);
                card.classList.toggle('bg-brand-primary/5', isSelected);
                card.classList.toggle('ring-1', isSelected);
                card.classList.toggle('ring-brand-primary/10', isSelected);
                card.classList.toggle('border-brand-secondary/10', !isSelected);
                card.classList.toggle('bg-white', !isSelected);
                card.classList.toggle('hover:border-brand-secondary/20', !isSelected);
            });
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
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
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
            updateSuggestions();
        });

        textarea.addEventListener('scroll', syncMirrorScroll);
        textarea.addEventListener('click', updateSuggestions);
        textarea.addEventListener('keyup', updateSuggestions);

        statusInputs.forEach((input) => {
            input.addEventListener('change', syncPublicationStatus);
        });

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
        syncPublicationStatus();
    });
</script>
