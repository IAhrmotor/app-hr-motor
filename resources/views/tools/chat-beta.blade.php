@extends('layouts.chat-shell')

@section('content')
    @php
        $authUser = auth()->user();
        $selectedParticipant = $selectedConversation?->otherParticipant($authUser);
        $selectedConversationMessages = $selectedConversation?->messages ?? collect();
    @endphp

    <section class="flex min-h-0 flex-1 w-full overflow-hidden bg-slate-100" data-chat-summary-url="{{ route('chat.beta.summary') }}" data-selected-conversation-id="{{ $selectedConversation?->id ?? '' }}">
        <aside class="flex h-full w-[21rem] min-w-[21rem] max-w-[21rem] flex-col border-r border-slate-200 bg-white shadow-[12px_0_40px_rgba(15,23,42,0.04)]">
            <div class="flex min-h-[4.75rem] items-center border-b border-slate-200 px-4 py-2">
                <div class="flex w-full items-center gap-2">
                    <button type="button"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-brand-primary/20 hover:text-brand-primary"
                        aria-label="Nuevo chat">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                    </button>

                    <form method="GET" action="{{ route('chat.beta') }}" class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20 20-3.5-3.5"></path>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10">
                    </form>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto">
                @if ($search !== '')
                    <div class="border-b border-slate-200 px-4 py-3">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Resultados</p>
                            <a href="{{ route('chat.beta') }}" class="cursor-pointer text-xs font-semibold text-brand-primary hover:underline">Limpiar</a>
                        </div>

                        <div class="space-y-2">
                            @forelse ($people as $person)
                                <a href="{{ route('chat.beta', ['recipient' => $person->id]) }}"
                                    class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-brand-primary/20 hover:shadow-sm">
                                    <img src="{{ $person->avatar_url }}" alt="Avatar de {{ $person->name }}" class="h-10 w-10 rounded-2xl object-cover">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-brand-secondary">{{ $person->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $person->chat_role_label }}</p>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-sm text-slate-500">
                                    Sin resultados.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

                <div class="px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Recientes</p>
                        <span class="text-xs text-slate-400" data-chat-unread-total>{{ $conversations->sum('unread_messages_count') }}</span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 border-y border-slate-100" data-chat-conversations-list>
                        @forelse ($conversations as $conversation)
                            @php
                                $partner = $conversation->otherParticipant($authUser);
                                $isSelected = $selectedConversation?->id === $conversation->id;
                            @endphp
                            <a href="{{ route('chat.beta', ['conversation' => $conversation->id]) }}"
                                data-chat-conversation-id="{{ $conversation->id }}"
                                class="group flex w-full cursor-pointer items-center gap-3 px-4 py-3 transition {{ $isSelected ? 'bg-brand-primary/10' : 'hover:bg-slate-50' }}">
                                <div class="relative shrink-0">
                                    <img src="{{ $partner?->avatar_url ?? asset('images/users/hrmotor-default-user-avatar.png') }}"
                                        alt="Avatar de {{ $partner?->name ?? 'Usuario' }}"
                                        class="h-11 w-11 rounded-2xl object-cover">
                                    @if (($conversation->unread_messages_count ?? 0) > 0)
                                        <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-primary px-1 text-[11px] font-semibold text-white" data-chat-unread-badge>
                                            {{ $conversation->unread_messages_count }}
                                        </span>
                                    @else
                                        <span class="absolute -right-1 -top-1 hidden h-5 min-w-5 items-center justify-center rounded-full bg-brand-primary px-1 text-[11px] font-semibold text-white" data-chat-unread-badge></span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-brand-secondary" data-chat-partner-name>{{ $partner?->name ?? 'Conversación' }}</p>
                                            <p class="truncate text-xs text-slate-500" data-chat-partner-role>{{ $partner?->chat_role_label ?? '' }}</p>
                                            <p class="truncate text-xs text-slate-500" data-chat-last-message>
                                                {{ $conversation->last_message_excerpt ?: 'Empieza la conversación' }}
                                            </p>
                                        </div>

                                        @if ($conversation->last_message_at)
                                            <span class="shrink-0 text-[11px] text-slate-400" data-chat-last-message-at>
                                                {{ $conversation->last_message_at->translatedFormat('d/m') }}
                                            </span>
                                        @else
                                            <span class="shrink-0 text-[11px] text-slate-400" data-chat-last-message-at></span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-brand-secondary">Sin conversaciones aún</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    Busca a un compañero y abre el primer chat.
                                </p>
                            </div>
                        @endforelse
                </div>
            </div>
        </aside>

        <section class="flex min-w-0 flex-1 flex-col bg-slate-100">
            @if ($selectedConversation && $selectedParticipant)
                <header class="flex min-h-[4.75rem] items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ $selectedParticipant->avatar_url }}" alt="Avatar de {{ $selectedParticipant->name }}" class="h-11 w-11 rounded-2xl object-cover">
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-semibold text-brand-secondary">{{ $selectedParticipant->name }}</h1>
                            <p class="truncate text-xs text-slate-500">{{ $selectedParticipant->chat_role_label }} · {{ $selectedParticipant->resolved_dealership_name ?: 'Sin delegación' }}</p>
                        </div>
                    </div>

                </header>

                <div
                    class="flex-1 min-h-0 overflow-y-auto px-4 py-5 sm:px-6"
                    data-chat-messages-wrapper
                    data-poll-url="{{ route('chat.beta.messages.index', $selectedConversation) }}"
                    data-conversation-id="{{ $selectedConversation->id }}"
                >
                    <div class="mx-auto flex min-h-full max-w-5xl flex-col justify-end">
                        <div class="space-y-3" data-chat-messages>
                            @forelse ($selectedConversationMessages as $message)
                                @php
                                    $isMine = $message->sender_id === $authUser->id;
                                @endphp
                                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
                                    <div class="flex max-w-[78%] flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                                        <div class="rounded-[1.1rem] px-3 py-2 shadow-sm {{ $isMine ? 'bg-[#d9fdd3] text-slate-800' : 'border border-slate-200 bg-white text-brand-secondary' }}">
                                            <p class="whitespace-pre-line text-[15px] leading-[1.45]">{{ $message->body }}</p>
                                        </div>
                                        <div class="mt-1 flex items-center gap-1 text-[11px] {{ $isMine ? 'justify-end text-slate-500' : 'justify-start text-slate-400' }}">
                                            <span>{{ $message->created_at->translatedFormat('H:i') }}</span>
                                            @if ($isMine)
                                                <span class="inline-flex items-center {{ $message->read_at ? 'text-sky-500' : 'text-slate-400' }}" data-message-checks>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                                                        <line x1="13.22" y1="16.5" x2="21" y2="7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                        <polyline points="3 11.88 7 16.5 14.78 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex min-h-full items-center justify-center">
                                    <div class="max-w-md rounded-[2rem] border border-dashed border-slate-300 bg-white px-8 py-10 text-center shadow-sm">
                                        <p class="text-lg font-bold text-brand-secondary">Chat listo para empezar</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-500">
                                            Aquí verás la conversación cuando elijas un compañero.
                                        </p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <footer class="border-t border-slate-200 bg-white px-4 py-4">
                    <form
                        method="POST"
                        action="{{ route('chat.beta.messages.store', $selectedConversation) }}"
                        class="mx-auto max-w-5xl"
                        data-chat-form
                    >
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $selectedConversation->id }}">
                        <div class="flex items-end gap-3 rounded-[1.75rem] border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <button type="button"
                                class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-slate-400 transition hover:bg-slate-100 hover:text-brand-primary"
                                aria-label="Adjuntar archivo">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.5 12.5 21a6.364 6.364 0 1 1-9-9L12 3.5a4.243 4.243 0 1 1 6 6L8.5 19a2.121 2.121 0 1 1-3-3L14 7.5" />
                                </svg>
                            </button>

                            <textarea
                                name="body"
                                rows="1"
                                placeholder="Escribir mensaje..."
                                class="max-h-40 flex-1 resize-none border-0 bg-transparent px-0 py-2 text-[15px] text-brand-secondary outline-none placeholder:text-slate-400 focus:ring-0"
                                data-chat-input
                            >{{ old('body') }}</textarea>

                            <button type="submit"
                                class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white transition hover:opacity-90">
                                Enviar
                            </button>
                        </div>

                        @error('body')
                            <p class="mt-2 px-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                </footer>
            @else
                <div class="flex flex-1 items-center justify-center px-6">
                    <div class="max-w-xl rounded-[2rem] border border-dashed border-slate-300 bg-white px-8 py-10 text-center shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-primary">Chat</p>
                        <h2 class="mt-4 text-2xl font-bold tracking-tight text-brand-secondary">Busca a un compañero para empezar</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Selecciona una conversación reciente o usa la lupa para abrir un chat nuevo.
                        </p>
                    </div>
                </div>
            @endif
        </section>
    </section>

    @if ($selectedConversation && $selectedParticipant)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const wrapper = document.querySelector('[data-chat-messages-wrapper]');
                const messagesContainer = document.querySelector('[data-chat-messages]');
                const form = document.querySelector('[data-chat-form]');
                const input = document.querySelector('[data-chat-input]');
                const pollUrl = wrapper?.dataset.pollUrl;
                const summaryRoot = document.querySelector('[data-chat-summary-url]');
                const sidebarList = document.querySelector('[data-chat-conversations-list]');
                const sidebarUnreadTotal = document.querySelector('[data-chat-unread-total]');
                const summaryUrl = summaryRoot?.dataset.chatSummaryUrl;

                if (!wrapper || !messagesContainer || !form || !input || !pollUrl) {
                    return;
                }

                const csrfToken = form.querySelector('input[name="_token"]')?.value ?? '';
                let isSubmitting = false;
                let pollingLocked = false;
                let latestMessageId = Number(messagesContainer.querySelector('[data-message-id]')?.dataset.messageId ?? 0);
                let sidebarSelectedConversationId = Number(summaryRoot?.dataset.selectedConversationId ?? 0);

                const escapeHtml = (value) => {
                    const span = document.createElement('span');
                    span.textContent = value ?? '';
                    return span.innerHTML;
                };

                const autoScroll = () => {
                    wrapper.scrollTop = wrapper.scrollHeight;
                };

                const renderMessage = (message) => {
                    const mine = Boolean(message.is_mine);
                    const readClass = message.read_at ? 'text-sky-500' : 'text-slate-400';
                    const doubleCheckSvg = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <line x1="13.22" y1="16.5" x2="21" y2="7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                            <polyline points="3 11.88 7 16.5 14.78 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" />
                        </svg>`;

                    return `
                        <div class="flex ${mine ? 'justify-end' : 'justify-start'}" data-message-id="${message.id}">
                            <div class="flex max-w-[78%] flex-col ${mine ? 'items-end' : 'items-start'}">
                                <div class="rounded-[1.1rem] px-3 py-2 shadow-sm ${mine ? 'bg-[#d9fdd3] text-slate-800' : 'border border-slate-200 bg-white text-brand-secondary'}">
                                    <p class="whitespace-pre-line text-[15px] leading-[1.45]">${escapeHtml(message.body)}</p>
                                </div>
                                <div class="mt-1 flex items-center gap-1 text-[11px] ${mine ? 'justify-end text-slate-500' : 'justify-start text-slate-400'}">
                                    <span>${escapeHtml(message.created_at_label ?? '')}</span>
                                    ${mine ? `<span class="inline-flex items-center ${readClass}" data-message-checks>${doubleCheckSvg}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                };

                const renderConversation = (conversation) => {
                    const isSelected = Number(conversation.id) === Number(sidebarSelectedConversationId);
                    const itemClass = isSelected
                        ? 'bg-brand-primary/10'
                        : 'hover:bg-slate-50';
                    const unreadBadge = Number(conversation.unread_messages_count || 0);
                    const unreadHtml = unreadBadge > 0
                        ? `<span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-primary px-1 text-[11px] font-semibold text-white" data-chat-unread-badge>${unreadBadge}</span>`
                        : `<span class="absolute -right-1 -top-1 hidden h-5 min-w-5 items-center justify-center rounded-full bg-brand-primary px-1 text-[11px] font-semibold text-white" data-chat-unread-badge></span>`;

                    return `
                        <a href="{{ route('chat.beta') }}?conversation=${encodeURIComponent(conversation.id)}"
                            data-chat-conversation-id="${conversation.id}"
                            class="group flex w-full cursor-pointer items-center gap-3 px-4 py-3 transition ${itemClass}">
                            <div class="relative shrink-0">
                                <img src="${escapeHtml(conversation.partner_avatar_url || '{{ asset('images/users/hrmotor-default-user-avatar.png') }}')}"
                                    alt="Avatar de ${escapeHtml(conversation.partner_name || 'Usuario')}"
                                    class="h-11 w-11 rounded-2xl object-cover">
                                ${unreadHtml}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-brand-secondary" data-chat-partner-name>${escapeHtml(conversation.partner_name || 'Conversación')}</p>
                                        <p class="truncate text-xs text-slate-500" data-chat-partner-role>${escapeHtml(conversation.partner_chat_role_label || '')}</p>
                                        <p class="truncate text-xs text-slate-500" data-chat-last-message>${escapeHtml(conversation.last_message_excerpt || 'Empieza la conversación')}</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] text-slate-400" data-chat-last-message-at>${escapeHtml(conversation.last_message_at_label || '')}</span>
                                </div>
                            </div>
                        </a>
                    `;
                };

                const refreshSidebar = async () => {
                    if (!summaryUrl || !sidebarList) {
                        return;
                    }

                    try {
                        const response = await fetch(summaryUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        const conversations = Array.isArray(payload.conversations) ? payload.conversations : [];

                        if (sidebarUnreadTotal) {
                            const unreadTotal = Number(payload.unread_messages_total || 0);
                            sidebarUnreadTotal.textContent = String(unreadTotal);
                        }

                        sidebarList.innerHTML = conversations.length === 0
                            ? `
                                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                                    <p class="text-sm font-semibold text-brand-secondary">Sin conversaciones aún</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Busca a un compañero y abre el primer chat.</p>
                                </div>
                            `
                            : conversations.map(renderConversation).join('');
                    } catch (error) {
                        console.error(error);
                    }
                };

                const renderMessages = (messages) => {
                    if (!Array.isArray(messages) || messages.length === 0) {
                        messagesContainer.innerHTML = `
                            <div class="flex min-h-full items-center justify-center">
                                <div class="max-w-md rounded-[2rem] border border-dashed border-slate-300 bg-white px-8 py-10 text-center shadow-sm">
                                    <p class="text-lg font-bold text-brand-secondary">Chat listo para empezar</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Aquí verás la conversación cuando elijas un compañero.</p>
                                </div>
                            </div>
                        `;
                        latestMessageId = 0;
                        return;
                    }

                    messagesContainer.innerHTML = messages.map(renderMessage).join('');
                    latestMessageId = Math.max(...messages.map((message) => Number(message.id) || 0));
                    autoScroll();
                };

                const syncMessages = async () => {
                    if (pollingLocked) {
                        return;
                    }

                    try {
                        const response = await fetch(pollUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        const messages = Array.isArray(payload.messages) ? payload.messages : [];
                        const newLatestId = Math.max(0, ...messages.map((message) => Number(message.id) || 0));

                        if (newLatestId !== latestMessageId) {
                            renderMessages(messages);
                            refreshSidebar();
                            return;
                        }

                        const currentMineMessages = messages.filter((message) => message.is_mine);
                        const currentDomMessages = Array.from(messagesContainer.querySelectorAll('[data-message-id]'));

                        if (currentDomMessages.length !== messages.length) {
                            renderMessages(messages);
                            return;
                        }

                        currentMineMessages.forEach((message) => {
                            const node = messagesContainer.querySelector(`[data-message-id="${message.id}"] [data-message-checks]`);

                            if (node) {
                                node.classList.toggle('text-sky-500', Boolean(message.read_at));
                                node.classList.toggle('text-slate-400', !message.read_at);
                            }
                        });

                        refreshSidebar();
                    } catch (error) {
                        console.error(error);
                    }
                };

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const body = input.value.trimEnd();

                    if (body.trim() === '' || isSubmitting) {
                        return;
                    }

                    isSubmitting = true;
                    pollingLocked = true;

                    try {
                        const formData = new FormData(form);

                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                        });

                        if (!response.ok) {
                            throw new Error('No se pudo enviar el mensaje.');
                        }

                        const payload = await response.json();
                        const message = payload.message;

                        if (message) {
                            const currentHtml = messagesContainer.innerHTML;
                            if (currentHtml.includes('Chat listo para empezar')) {
                                messagesContainer.innerHTML = '';
                            }
                            messagesContainer.insertAdjacentHTML('beforeend', renderMessage(message));
                            latestMessageId = Number(message.id) || latestMessageId;
                            autoScroll();
                            refreshSidebar();
                        }

                        input.value = '';
                        input.style.height = 'auto';
                    } catch (error) {
                        console.error(error);
                    } finally {
                        isSubmitting = false;
                        pollingLocked = false;
                    }
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
                        return;
                    }

                    event.preventDefault();
                    form.requestSubmit();
                });

                input.addEventListener('input', () => {
                    input.style.height = 'auto';
                    input.style.height = `${Math.min(input.scrollHeight, 160)}px`;
                });

                autoScroll();
                setInterval(syncMessages, 3000);
                setInterval(refreshSidebar, 5000);
                refreshSidebar();
                syncMessages();
            });
        </script>
    @endif
@endsection
