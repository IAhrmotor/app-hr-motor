@extends('layouts.chat-shell')

@section('content')
    @php
        $authUser = auth()->user();
        $selectedParticipant = $selectedConversation?->otherParticipant($authUser);
        $selectedConversationMessages = $selectedConversation?->messages ?? collect();
    @endphp

    <section
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="flex min-h-0 flex-1 w-full overflow-hidden bg-slate-100"
        data-chat-root
        data-chat-summary-url="{{ route('chat.beta.summary') }}"
        data-selected-conversation-id="{{ $selectedConversation?->id ?? '' }}"
    >
        <aside class="flex h-full w-[21rem] min-w-[21rem] max-w-[21rem] flex-col border-r border-slate-200 bg-white shadow-[12px_0_40px_rgba(15,23,42,0.04)]" data-chat-sidebar>
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
                                    data-chat-recipient-link
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
                                data-chat-conversation-link
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
                                                {{ $conversation->last_message_at->translatedFormat('d/m H:i') }}
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
                        <button
                            type="button"
                            @click.stop="openImage({ src: @js($selectedParticipant->avatar_url), alt: @js('Avatar de '.$selectedParticipant->name), title: @js($selectedParticipant->name) })"
                            class="group relative cursor-pointer overflow-hidden rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                            aria-label="Ampliar imagen de {{ $selectedParticipant->name }}"
                        >
                            <img src="{{ $selectedParticipant->avatar_url }}" alt="Avatar de {{ $selectedParticipant->name }}" class="h-11 w-11 rounded-2xl object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75" data-chat-partner-avatar>
                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-2xl bg-brand-secondary/0 text-[10px] font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                                Ver
                            </span>
                        </button>
                        <a href="{{ route('users.show', $selectedParticipant) }}" class="min-w-0 transition hover:opacity-90" aria-label="Ver perfil de {{ $selectedParticipant->name }}">
                            <h1 class="truncate text-base font-semibold text-brand-secondary">{{ $selectedParticipant->name }}</h1>
                            <p class="truncate text-xs text-slate-500">{{ $selectedParticipant->chat_role_label }} · {{ $selectedParticipant->resolved_dealership_name ?: 'Sin delegación' }}</p>
                        </a>
                    </div>

                </header>

                <div
                    class="flex-1 min-h-0 overflow-y-auto px-4 py-5 sm:px-6"
                    data-chat-messages-wrapper
                    data-chat-messages-url-template="{{ route('chat.beta.messages.index', ['conversation' => '__CONVERSATION_ID__']) }}"
                    data-chat-store-url-template="{{ route('chat.beta.messages.store', ['conversation' => '__CONVERSATION_ID__']) }}"
                    data-poll-url="{{ route('chat.beta.messages.index', $selectedConversation) }}"
                    data-conversation-id="{{ $selectedConversation->id }}"
                >
                    <div class="mx-auto flex min-h-full max-w-5xl flex-col justify-end">
                        <div class="space-y-0" data-chat-messages>
                            @forelse ($selectedConversationMessages as $message)
                                @php
                                    $isMine = $message->sender_id === $authUser->id;
                                    $nextMessage = $selectedConversationMessages->get($loop->index + 1);
                                    $currentTimeLabel = $message->created_at->translatedFormat('H:i');
                                    $nextTimeLabel = $nextMessage?->created_at?->translatedFormat('H:i');
                                    $showTime = $loop->last || $nextTimeLabel !== $currentTimeLabel;
                                    $messageAttachments = collect($message->attachments ?? []);
                                @endphp
                                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} {{ $loop->first ? 'mt-0' : ($showTime ? 'mt-3' : 'mt-0.5') }}" data-message-id="{{ $message->id }}">
                                    <div class="flex max-w-[78%] flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                                        <div class="relative rounded-[1.1rem] px-3 py-2 shadow-sm {{ $isMine ? 'bg-[#d9fdd3] pb-5 text-slate-800' : 'border border-slate-200 bg-white text-brand-secondary' }}">
                                            @if (filled($message->body))
                                                <p class="whitespace-pre-line text-[15px] leading-[1.45]">{{ $message->body }}</p>
                                            @endif

                                            @if ($messageAttachments->isNotEmpty())
                                                <div class="{{ filled($message->body) ? 'mt-2' : '' }} space-y-2">
                                                    @foreach ($messageAttachments as $attachment)
                                                        @php
                                                            $isImageAttachment = (bool) ($attachment['is_image'] ?? str_starts_with((string) ($attachment['mime_type'] ?? ''), 'image/'));
                                                            $attachmentUrl = $attachment['url'] ?? '';
                                                            $attachmentName = $attachment['original_name'] ?? 'archivo';
                                                            $attachmentSize = $attachment['size_label'] ?? '';
                                                        @endphp

                                                        @if ($isImageAttachment)
                                                            <button
                                                                type="button"
                                                                data-chat-image-src="{{ $attachmentUrl }}"
                                                                data-chat-image-alt="{{ $attachmentName }}"
                                                                data-chat-image-title="{{ $attachmentName }}"
                                                                @click="openImage({ src: $el.dataset.chatImageSrc, alt: $el.dataset.chatImageAlt, title: $el.dataset.chatImageTitle })"
                                                                class="group relative block cursor-pointer overflow-hidden rounded-[1rem] border border-black/5 bg-white/50 text-left transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                                                aria-label="Ver {{ $attachmentName }}"
                                                            >
                                                                <img src="{{ $attachmentUrl }}" alt="{{ $attachmentName }}" class="max-h-72 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">
                                                                    Ver
                                                                </span>
                                                            </button>
                                                        @else
                                                            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-[1rem] border border-black/5 bg-white/60 px-3 py-2 transition hover:bg-white">
                                                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5" />
                                                                    </svg>
                                                                </span>
                                                                <span class="min-w-0 flex-1">
                                                                    <span class="block truncate text-sm font-semibold text-brand-secondary">{{ $attachmentName }}</span>
                                                                    @if ($attachmentSize !== '')
                                                                        <span class="block text-xs text-slate-500">{{ $attachmentSize }}</span>
                                                                    @endif
                                                                </span>
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($isMine)
                                                <span class="absolute bottom-1.5 right-2 inline-flex items-center {{ $message->read_at ? 'text-sky-500' : 'text-slate-400' }}" data-message-checks>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                        <line x1="13.22" y1="16.5" x2="21" y2="7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                        <polyline points="3 11.88 7 16.5 14.78 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="{{ $showTime ? 'mt-1' : 'mt-0.5' }} flex items-center gap-1 text-[11px] {{ $isMine ? 'justify-end text-slate-500' : 'justify-start text-slate-400' }}">
                                            <span data-message-time @if (! $showTime) class="hidden" @endif>{{ $currentTimeLabel }}</span>
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
                        class="relative mx-auto max-w-5xl"
                        data-chat-form
                    >
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $selectedConversation->id }}">
                        <input type="file" name="attachments[]" multiple class="hidden" data-chat-attachments-input accept="image/*,.svg,.pdf,.txt,.md,.csv,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar">

                        <div class="absolute bottom-full right-16 mb-3 hidden w-72 rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-xl" data-chat-emoji-picker>
                            <div class="grid grid-cols-8 gap-1">
                                @foreach (['😀','😄','😁','😉','😍','🥰','😎','🤩','🙂','🙌','👍','👏','🔥','✨','❤️','💡','📎','📷','🧠','🎯','🚀','💬','😅','🙏'] as $emoji)
                                    <button type="button"
                                        class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl text-lg transition hover:bg-slate-100"
                                        data-chat-emoji-option
                                        data-emoji="{{ $emoji }}"
                                        aria-label="Insertar {{ $emoji }}">
                                        {{ $emoji }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-end gap-3 rounded-[1.75rem] border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <button type="button"
                                class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-slate-400 transition hover:bg-slate-100 hover:text-brand-primary"
                                aria-label="Adjuntar archivo"
                                data-chat-attachments-button>
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

                            <button type="button"
                                class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-slate-400 transition hover:bg-slate-100 hover:text-brand-primary"
                                aria-label="Emoticonos"
                                data-chat-emoji-button>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="12" r="9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h.01M15 10h.01" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15c.8 1 1.8 1.5 3 1.5S14.2 16 15 15" />
                                </svg>
                            </button>

                            <button type="submit"
                                class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white transition hover:opacity-90">
                                Enviar
                            </button>
                        </div>

                        <div class="mt-2 hidden px-1 text-xs text-slate-500" data-chat-attachments-preview></div>
                        <div class="mt-2 hidden px-1" data-chat-attachments-chips></div>
                        <p class="mt-2 hidden px-1 text-sm font-medium text-red-600" data-chat-error></p>

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

        <div
            x-cloak
            x-show="isImageOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-6 py-8 backdrop-blur-sm"
            @click.self="closeImage()"
        >
            <div class="inline-flex max-w-[calc(100vw-3rem)] flex-col items-center">
                <div
                    x-ref="imageViewport"
                    class="relative touch-none overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl"
                    :class="imageScale > 1 ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                    @wheel.prevent="handleWheel($event)"
                    @pointerdown="handlePointerDown($event)"
                    @pointermove="handlePointerMove($event)"
                    @pointerup="handlePointerUp($event)"
                    @pointercancel="handlePointerCancel($event)"
                >
                    <button
                        type="button"
                        @pointerdown.stop
                        @click.stop="closeImage()"
                        class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Cerrar imagen ampliada"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <img
                        :src="imageUrl"
                        :alt="imageAlt"
                        @dblclick="toggleZoom($event.clientX, $event.clientY)"
                        draggable="false"
                        @dragstart.prevent
                        class="block max-h-[80vh] w-auto max-w-[calc(100vw-3rem)] select-none object-contain bg-slate-900 will-change-transform"
                        :class="isDragging ? 'transition-none' : 'transition-transform duration-200'"
                        :style="`transform: translate3d(${translateX}px, ${translateY}px, 0) scale(${imageScale}); transform-origin: center center;`"
                    >
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="zoomOut()"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Reducir zoom"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="resetZoom()"
                        class="inline-flex h-10 min-w-20 items-center justify-center rounded-full bg-white/90 px-3 text-sm font-semibold text-brand-secondary shadow-lg"
                        aria-label="Restablecer zoom"
                    >
                        <span x-text="`${imageScale.toFixed(2).replace(/\.00$/, '')}x`"></span>
                    </button>
                    <button
                        type="button"
                        @click="zoomIn()"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Aumentar zoom"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <p class="mt-4 text-center text-sm font-medium text-white/80" x-text="imageTitle"></p>
            </div>
        </div>

    </section>

    @if ($selectedConversation && $selectedParticipant)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const root = document.querySelector('[data-chat-root]');
                const sidebar = document.querySelector('[data-chat-sidebar]');
                const wrapper = document.querySelector('[data-chat-messages-wrapper]');
                const messagesContainer = document.querySelector('[data-chat-messages]');
                const form = document.querySelector('[data-chat-form]');
                const input = document.querySelector('[data-chat-input]');
                const attachmentsInput = document.querySelector('[data-chat-attachments-input]');
                const attachmentsButton = document.querySelector('[data-chat-attachments-button]');
                const attachmentsPreview = document.querySelector('[data-chat-attachments-preview]');
                const attachmentsChips = document.querySelector('[data-chat-attachments-chips]');
                const chatError = document.querySelector('[data-chat-error]');
                const emojiButton = document.querySelector('[data-chat-emoji-button]');
                const emojiPicker = document.querySelector('[data-chat-emoji-picker]');
                let pollUrl = wrapper?.dataset.pollUrl;
                const summaryRoot = document.querySelector('[data-chat-summary-url]');
                const sidebarList = document.querySelector('[data-chat-conversations-list]');
                const sidebarUnreadTotal = document.querySelector('[data-chat-unread-total]');
                const summaryUrl = summaryRoot?.dataset.chatSummaryUrl;
                const messagesUrlTemplate = wrapper?.dataset.chatMessagesUrlTemplate;
                const storeUrlTemplate = wrapper?.dataset.chatStoreUrlTemplate;
                const headerName = document.querySelector('[data-chat-partner-name]');
                const headerRole = document.querySelector('[data-chat-partner-role]');
                const headerAvatar = document.querySelector('[data-chat-partner-avatar]');

                if (!root || !sidebar || !wrapper || !messagesContainer || !form || !input || !pollUrl || !messagesUrlTemplate || !storeUrlTemplate || !attachmentsInput || !attachmentsButton || !attachmentsPreview || !attachmentsChips || !chatError || !emojiButton || !emojiPicker) {
                    return;
                }

                const csrfToken = form.querySelector('input[name="_token"]')?.value ?? '';
                let isSubmitting = false;
                let pollingLocked = false;
                let attachmentSnapshot = [];
                let latestMessageId = Number(messagesContainer.querySelector('[data-message-id]')?.dataset.messageId ?? 0);
                let sidebarSelectedConversationId = Number(summaryRoot?.dataset.selectedConversationId ?? 0);
                const allowedAttachmentExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'txt', 'md', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'];

                const escapeHtml = (value) => {
                    const span = document.createElement('span');
                    span.textContent = value ?? '';
                    return span.innerHTML;
                };

                const autoScroll = () => {
                    wrapper.scrollTop = wrapper.scrollHeight;
                };

                const clearChatError = () => {
                    chatError.textContent = '';
                    chatError.classList.add('hidden');
                };

                const setChatError = (message) => {
                    if (!message) {
                        clearChatError();
                        return;
                    }

                    chatError.textContent = message;
                    chatError.classList.remove('hidden');
                };

                const getAttachmentExtension = (fileName) => {
                    const parts = String(fileName || '').toLowerCase().split('.');
                    return parts.length > 1 ? parts.pop() : '';
                };

                const getAttachmentKey = (file) => [
                    file?.name || '',
                    file?.size || 0,
                    file?.type || '',
                    file?.lastModified || 0,
                ].join('|');

                const buildConversationMessagesUrl = (conversationId) => messagesUrlTemplate.replace('__CONVERSATION_ID__', encodeURIComponent(conversationId));

                const buildConversationStoreUrl = (conversationId) => storeUrlTemplate.replace('__CONVERSATION_ID__', encodeURIComponent(conversationId));

                const setSelectedConversation = (conversationId) => {
                    sidebarSelectedConversationId = Number(conversationId);
                    wrapper.dataset.conversationId = String(conversationId);
                    pollUrl = buildConversationMessagesUrl(conversationId);
                    wrapper.dataset.pollUrl = pollUrl;
                    form.action = buildConversationStoreUrl(conversationId);
                    form.querySelector('input[name="conversation_id"]').value = String(conversationId);
                };

                const updateHeader = (payload) => {
                    if (payload?.partner_name && headerName) {
                        headerName.textContent = payload.partner_name;
                    }

                    if (payload?.partner_chat_role_label && headerRole) {
                        const dealership = headerRole.textContent?.split('·')?.[1]?.trim();
                        headerRole.textContent = dealership
                            ? `${payload.partner_chat_role_label} · ${dealership}`
                            : payload.partner_chat_role_label;
                    }

                    if (payload?.partner_avatar_url && headerAvatar) {
                        headerAvatar.src = payload.partner_avatar_url;
                    }
                };

                const updatePreviousTimeVisibility = (message) => {
                    const currentTime = message?.created_at_label;
                    if (!currentTime) {
                        return;
                    }

                    const currentNode = messagesContainer.querySelector(`[data-message-id="${message.id}"]`);
                    const previousNode = currentNode?.previousElementSibling;
                    const previousTimeNode = previousNode?.querySelector('[data-message-time]');

                    if (!previousTimeNode) {
                        return;
                    }

                    const previousTime = previousTimeNode.textContent?.trim();

                    if (previousTime === currentTime) {
                        previousTimeNode.classList.add('hidden');
                    }
                };

                const renderMessage = (message, compactTop = false) => {
                    const mine = Boolean(message.is_mine);
                    const readClass = message.read_at ? 'text-sky-500' : 'text-slate-400';
                const doubleCheckSvg = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <line x1="13.22" y1="16.5" x2="21" y2="7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                            <polyline points="3 11.88 7 16.5 14.78 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" />
                        </svg>`;

                const renderAttachment = (attachment) => {
                    const url = escapeHtml(attachment?.url || '');
                    const name = escapeHtml(attachment?.original_name || 'archivo');
                    const sizeLabel = attachment?.size_label ? `<span class="block text-xs text-slate-500">${escapeHtml(attachment.size_label)}</span>` : '';

                    if (attachment?.is_image) {
                        return `
                            <button
                                type="button"
                                data-chat-image-src="${url}"
                                data-chat-image-alt="${name}"
                                data-chat-image-title="${name}"
                                @click="openImage({ src: $el.dataset.chatImageSrc, alt: $el.dataset.chatImageAlt, title: $el.dataset.chatImageTitle })"
                                class="group relative block cursor-pointer overflow-hidden rounded-[1rem] border border-black/5 bg-white/50 text-left transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                aria-label="Ver ${name}"
                            >
                                <img src="${url}" alt="${name}" class="max-h-72 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">
                                    Ver
                                </span>
                            </button>
                        `;
                    }

                    return `
                        <a href="${url}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-[1rem] border border-black/5 bg-white/60 px-3 py-2 transition hover:bg-white">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-brand-secondary">${name}</span>
                                ${sizeLabel}
                            </span>
                        </a>
                    `;
                };

                return `
                        <div class="flex ${mine ? 'justify-end' : 'justify-start'} ${compactTop ? 'mt-0.5' : 'mt-3'}" data-message-id="${message.id}">
                            <div class="flex max-w-[78%] flex-col ${mine ? 'items-end' : 'items-start'}">
                                <div class="relative rounded-[1.1rem] px-3 py-2 shadow-sm ${mine ? 'bg-[#d9fdd3] pb-5 text-slate-800' : 'border border-slate-200 bg-white text-brand-secondary'}">
                                    ${message.body ? `<p class="whitespace-pre-line text-[15px] leading-[1.45]">${escapeHtml(message.body)}</p>` : ''}
                                    ${(Array.isArray(message.attachments) && message.attachments.length > 0) ? `
                                        <div class="${message.body ? 'mt-2' : ''} space-y-2">
                                            ${message.attachments.map((attachment) => renderAttachment(attachment)).join('')}
                                        </div>
                                    ` : ''}
                                    ${mine ? `<span class="absolute bottom-1.5 right-2 inline-flex items-center ${readClass}" data-message-checks>${doubleCheckSvg}</span>` : ''}
                                </div>
                                <div class="${message.show_time === false ? 'mt-0.5' : 'mt-1'} flex items-center gap-1 text-[11px] ${mine ? 'justify-end text-slate-500' : 'justify-start text-slate-400'}">
                                    <span data-message-time${message.show_time === false ? ' class="hidden"' : ''}>${escapeHtml(message.created_at_label ?? '')}</span>
                                </div>
                            </div>
                        </div>
                    `;
                };

                const setAttachmentsFiles = (files) => {
                    const dataTransfer = new DataTransfer();

                    files.forEach((file) => dataTransfer.items.add(file));
                    attachmentsInput.files = dataTransfer.files;
                    renderAttachmentsPreview();
                };

                const filterUnsupportedAttachments = () => {
                    const files = Array.from(attachmentsInput.files || []);
                    const validFiles = [];
                    const invalidFiles = [];

                    files.forEach((file) => {
                        const extension = getAttachmentExtension(file.name);

                        if (allowedAttachmentExtensions.includes(extension)) {
                            validFiles.push(file);
                            return;
                        }

                        invalidFiles.push(file.name);
                    });

                    if (invalidFiles.length > 0) {
                        const allowedList = allowedAttachmentExtensions.map((extension) => `.${extension}`).join(', ');
                        setChatError(`El archivo ${invalidFiles.length === 1 ? invalidFiles[0] : invalidFiles.join(', ')} no se puede enviar. Formatos permitidos: ${allowedList}.`);
                    } else {
                        clearChatError();
                    }

                    setAttachmentsFiles(validFiles);
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
                            data-chat-conversation-link
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

                const loadConversation = async (conversationId, { pushState = true } = {}) => {
                    if (!conversationId) {
                        return;
                    }

                    const url = buildConversationMessagesUrl(conversationId);

                    try {
                        const response = await fetch(url, {
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
                        const activeConversationId = Number(payload.conversation_id || conversationId);

                        setSelectedConversation(activeConversationId);
                        updateHeader(payload);
                        renderMessages(messages);
                        autoScroll();
                        refreshSidebar();

                        if (pushState) {
                            const nextUrl = new URL(window.location.href);
                            nextUrl.searchParams.set('conversation', String(activeConversationId));
                            nextUrl.searchParams.delete('recipient');
                            window.history.pushState({ conversationId: activeConversationId }, '', nextUrl.toString());
                        }
                    } catch (error) {
                        console.error(error);
                    }
                };

                const openConversationFromLink = async (url) => {
                    try {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });

                        const finalUrl = new URL(response.url);
                        const conversationId = finalUrl.searchParams.get('conversation');

                        if (conversationId) {
                            await loadConversation(conversationId);
                            autoScroll();
                        }
                    } catch (error) {
                        console.error(error);
                    }
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

                    messagesContainer.innerHTML = messages.map((message, index) => {
                        const previousMessage = messages[index - 1];
                        const compactTop = Boolean(previousMessage) && previousMessage.created_at_label === message.created_at_label;
                        return renderMessage(message, compactTop);
                    }).join('');
                    window.Alpine?.initTree(messagesContainer);
                    latestMessageId = Math.max(...messages.map((message) => Number(message.id) || 0));
                    autoScroll();
                };

                const renderAttachmentsPreview = () => {
                    const files = Array.from(attachmentsInput.files || []);

                    if (files.length === 0) {
                        attachmentsPreview.classList.add('hidden');
                        attachmentsPreview.textContent = '';
                        attachmentsChips.classList.add('hidden');
                        attachmentsChips.innerHTML = '';
                        return;
                    }

                    attachmentsPreview.textContent = files.length === 1
                        ? '1 archivo adjunto'
                        : `${files.length} archivos adjuntos`;
                    attachmentsPreview.classList.remove('hidden');

                    attachmentsChips.innerHTML = files.map((file, index) => `
                        <span class="mb-2 mr-2 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 shadow-sm">
                            <span class="max-w-[14rem] truncate">${escapeHtml(file.name)}</span>
                            <button type="button"
                                class="inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-brand-primary"
                                data-chat-remove-attachment
                                data-attachment-index="${index}"
                                aria-label="Quitar adjunto ${escapeHtml(file.name)}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    `).join('');
                    attachmentsChips.classList.remove('hidden');
                };

                const showServerValidationError = async (response) => {
                    const contentType = response.headers.get('content-type') || '';

                    if (!contentType.includes('application/json')) {
                        setChatError('No se pudo enviar el mensaje.');
                        return;
                    }

                    const payload = await response.json().catch(() => null);
                    const messages = payload?.errors ? Object.values(payload.errors).flat().filter(Boolean) : [];

                    if (messages.length > 0) {
                        setChatError(messages[0]);
                        return;
                    }

                    setChatError(payload?.message || 'No se pudo enviar el mensaje.');
                };

                const insertTextAtCursor = (textToInsert) => {
                    const start = input.selectionStart ?? input.value.length;
                    const end = input.selectionEnd ?? input.value.length;
                    const value = input.value;

                    input.value = `${value.slice(0, start)}${textToInsert}${value.slice(end)}`;
                    const nextPosition = start + textToInsert.length;
                    input.focus();
                    input.setSelectionRange(nextPosition, nextPosition);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                };

                const toggleEmojiPicker = () => {
                    emojiPicker.classList.toggle('hidden');
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
                            autoScroll();
                            refreshSidebar();
                            return;
                        }

                        const currentMineMessages = messages.filter((message) => message.is_mine);
                        const currentDomMessages = Array.from(messagesContainer.querySelectorAll('[data-message-id]'));

                        if (currentDomMessages.length !== messages.length) {
                            renderMessages(messages);
                            autoScroll();
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
                    clearChatError();

                    const body = input.value.trimEnd();
                    const hasAttachments = Array.from(attachmentsInput.files || []).length > 0;

                    if ((body.trim() === '' && !hasAttachments) || isSubmitting) {
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
                            await showServerValidationError(response);
                            return;
                        }

                        const payload = await response.json();
                        const message = payload.message;

                        if (message) {
                            const currentHtml = messagesContainer.innerHTML;
                            if (currentHtml.includes('Chat listo para empezar')) {
                                messagesContainer.innerHTML = '';
                            }
                            const previousNode = messagesContainer.lastElementChild;
                            const previousTime = previousNode?.querySelector('[data-message-time]')?.textContent?.trim();
                            const compactTop = previousTime === (message.created_at_label ?? '');
                            messagesContainer.insertAdjacentHTML('beforeend', renderMessage(message, compactTop));
                            window.Alpine?.initTree(messagesContainer.lastElementChild);
                            updatePreviousTimeVisibility(message);
                            latestMessageId = Number(message.id) || latestMessageId;
                            autoScroll();
                            refreshSidebar();
                        }

                        input.value = '';
                        input.style.height = 'auto';
                        attachmentsInput.value = '';
                        renderAttachmentsPreview();
                        emojiPicker.classList.add('hidden');
                    } catch (error) {
                        console.error(error);
                        if (!chatError.textContent) {
                            setChatError('No se pudo enviar el mensaje.');
                        }
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

                attachmentsButton.addEventListener('click', () => {
                    attachmentSnapshot = Array.from(attachmentsInput.files || []);
                    emojiPicker.classList.add('hidden');
                    attachmentsInput.click();
                });

                attachmentsInput.addEventListener('change', () => {
                    const incomingFiles = Array.from(attachmentsInput.files || []);
                    const mergedFiles = [...attachmentSnapshot, ...incomingFiles];
                    const dedupedFiles = [];
                    const seenFiles = new Set();

                    mergedFiles.forEach((file) => {
                        const fileKey = getAttachmentKey(file);

                        if (seenFiles.has(fileKey)) {
                            return;
                        }

                        seenFiles.add(fileKey);
                        dedupedFiles.push(file);
                    });

                    setAttachmentsFiles(dedupedFiles);
                    filterUnsupportedAttachments();
                });

                emojiButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    toggleEmojiPicker();
                });

                emojiPicker.addEventListener('click', (event) => {
                    const emojiOption = event.target.closest('[data-chat-emoji-option]');

                    if (!emojiOption) {
                        return;
                    }

                    insertTextAtCursor(emojiOption.dataset.emoji || '🙂');
                });

                attachmentsChips.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-chat-remove-attachment]');

                    if (!removeButton) {
                        return;
                    }

                    const index = Number(removeButton.dataset.attachmentIndex);
                    const files = Array.from(attachmentsInput.files || []);

                    if (!Number.isInteger(index) || index < 0 || index >= files.length) {
                        return;
                    }

                    files.splice(index, 1);
                    setAttachmentsFiles(files);
                });

                document.addEventListener('click', (event) => {
                    if (emojiPicker.classList.contains('hidden')) {
                        return;
                    }

                    if (emojiPicker.contains(event.target) || emojiButton.contains(event.target)) {
                        return;
                    }

                    emojiPicker.classList.add('hidden');
                });

                root.addEventListener('click', async (event) => {
                    const link = event.target.closest('[data-chat-conversation-link], [data-chat-recipient-link]');

                    if (!link) {
                        return;
                    }

                    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                        return;
                    }

                    event.preventDefault();
                    await openConversationFromLink(link.href);
                });

                window.addEventListener('popstate', () => {
                    const conversationId = new URL(window.location.href).searchParams.get('conversation');

                    if (conversationId) {
                        void loadConversation(conversationId, { pushState: false });
                    }
                });

                autoScroll();
                renderAttachmentsPreview();
                setInterval(syncMessages, 3000);
                setInterval(refreshSidebar, 5000);
                refreshSidebar();
                syncMessages();
            });
        </script>
    @endif
@endsection
