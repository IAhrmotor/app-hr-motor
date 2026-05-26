@extends('layouts.chat-shell')

@section('content')
    @php
        $authUser = auth()->user();
        $selectedParticipant = $selectedConversation?->otherParticipant($authUser);
        $selectedConversationMessages = $selectedConversation?->messages ?? collect();
        $favoriteUserIds = $favoriteUserIds ?? [];
        $selectedParticipantIsFavorite = $selectedParticipant?->id ? in_array($selectedParticipant->id, $favoriteUserIds, true) : false;
    @endphp

    <section
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        @open-image.window="openImage($event.detail)"
        class="flex min-h-0 flex-1 w-full overflow-hidden bg-slate-100"
        data-chat-root
        data-chat-summary-url="{{ route('chat.beta.summary') }}"
        data-selected-conversation-id="{{ $selectedConversation?->id ?? '' }}"
    >
        <aside class="flex h-full w-[21rem] min-w-[21rem] max-w-[21rem] flex-col border-r border-slate-200 bg-white shadow-[12px_0_40px_rgba(15,23,42,0.04)]" data-chat-sidebar>
            <div class="flex min-h-[4.75rem] items-center border-b border-slate-200 px-4 py-2">
                <div class="flex w-full items-center gap-2">
                        <button type="button" data-chat-sidebar-tab="favorites" aria-pressed="false"
                            class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-transparent text-slate-500 transition hover:bg-slate-100 hover:text-brand-primary"
                            aria-label="Abrir favoritos">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M11.245 4.174C11.4765 3.50808 11.5922 3.17513 11.7634 3.08285C11.9115 3.00298 12.0898 3.00298 12.238 3.08285C12.4091 3.17513 12.5248 3.50808 12.7563 4.174L14.2866 8.57639C14.3525 8.76592 14.3854 8.86068 14.4448 8.93125C14.4972 8.99359 14.5641 9.04218 14.6396 9.07278C14.725 9.10743 14.8253 9.10947 15.0259 9.11356L19.6857 9.20852C20.3906 9.22288 20.743 9.23007 20.8837 9.36432C21.0054 9.48051 21.0605 9.65014 21.0303 9.81569C20.9955 10.007 20.7146 10.2199 20.1528 10.6459L16.4387 13.4616C16.2788 13.5829 16.1989 13.6435 16.1501 13.7217C16.107 13.7909 16.0815 13.8695 16.0757 13.9507C16.0692 14.0427 16.0982 14.1387 16.1563 14.3308L17.506 18.7919C17.7101 19.4667 17.8122 19.8041 17.728 19.9793C17.6551 20.131 17.5108 20.2358 17.344 20.2583C17.1513 20.2842 16.862 20.0829 16.2833 19.6802L12.4576 17.0181C12.2929 16.9035 12.2106 16.8462 12.1211 16.8239C12.042 16.8043 11.9593 16.8043 11.8803 16.8239C11.7908 16.8462 11.7084 16.9035 11.5437 17.0181L7.71805 19.6802C7.13937 20.0829 6.85003 20.2842 6.65733 20.2583C6.49056 20.2358 6.34626 20.131 6.27337 19.9793C6.18915 19.8041 6.29123 19.4667 6.49538 18.7919L7.84503 14.3308C7.90313 14.1387 7.93218 14.0427 7.92564 13.9507C7.91986 13.8695 7.89432 13.7909 7.85123 13.7217C7.80246 13.6435 7.72251 13.5829 7.56262 13.4616L3.84858 10.6459C3.28678 10.2199 3.00588 10.007 2.97101 9.81569C2.94082 9.65014 2.99594 9.48051 3.11767 9.36432C3.25831 9.23007 3.61074 9.22289 4.31559 9.20852L8.9754 9.11356C9.176 9.10947 9.27631 9.10743 9.36177 9.07278C9.43726 9.04218 9.50414 8.99359 9.55657 8.93125C9.61593 8.86068 9.64887 8.76592 9.71475 8.57639L11.245 4.174Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
                            data-chat-search-input
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:bg-white focus:ring-4 focus:ring-brand-primary/10">
                    </form>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto">
                <div class="border-b border-slate-200 px-4 py-3">
                    <div class="grid grid-cols-3 rounded-2xl bg-slate-100 p-1 text-xs font-semibold">
                        <button type="button" data-chat-sidebar-tab="chats" aria-pressed="true"
                            class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-brand-primary px-3 py-2 text-white shadow-sm transition hover:bg-brand-primary/95">
                            Chats
                        </button>
                        <button type="button" data-chat-sidebar-tab="team" aria-pressed="false"
                            class="inline-flex cursor-pointer items-center justify-center rounded-xl px-3 py-2 text-slate-500 transition hover:bg-slate-100">
                            Equipo
                        </button>
                        <button type="button" data-chat-sidebar-tab="groups" aria-pressed="false"
                            class="inline-flex cursor-pointer items-center justify-center rounded-xl px-3 py-2 text-slate-500 transition hover:bg-slate-100">
                            Grupos
                        </button>
                    </div>
                </div>

                <div class="hidden" data-chat-sidebar-panel="favorites">
                    <div data-chat-favorites-list>
                        @if (! empty($favoriteContacts))
                            <div class="divide-y divide-slate-100 border-y border-slate-100">
                                @foreach ($favoriteContacts as $favoriteContact)
                                    <a href="{{ route('chat.beta', ['recipient' => $favoriteContact['id']]) }}"
                                        data-chat-recipient-link
                                        class="group flex w-full cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-slate-50">
                                        <div class="shrink-0">
                                            <img src="{{ $favoriteContact['avatar_url'] }}"
                                                alt="Avatar de {{ $favoriteContact['name'] }}"
                                                class="h-11 w-11 rounded-2xl object-cover">
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-amber-600">
                                                        {{ $favoriteContact['name'] }}
                                                    </p>
                                                    <p class="truncate text-xs text-slate-500">{{ $favoriteContact['chat_role_label'] }}{{ $favoriteContact['resolved_dealership_name'] ? ' · ' . $favoriteContact['resolved_dealership_name'] : '' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="border-y border-slate-100 px-4 py-8 text-center text-sm text-slate-500">
                                Marca contactos como favoritos para verlos aquí.
                            </div>
                        @endif
                    </div>
                </div>

                <div data-chat-sidebar-panel="chats">
                    <div data-chat-search-results>
                        @include('tools.chat-beta.partials.search-results', ['people' => $people, 'search' => $search])
                    </div>

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
                                            <p class="truncate text-sm font-semibold {{ in_array($partner?->id, $favoriteUserIds, true) ? 'text-amber-600' : 'text-brand-secondary' }}" data-chat-partner-name>{{ $partner?->name ?? 'Conversación' }}</p>
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

                <div class="hidden" data-chat-sidebar-panel="team">
                    <div class="divide-y divide-slate-100 border-y border-slate-100">
                        @forelse ($teamUsers as $group)
                            <details class="group" data-chat-team-accordion>
                                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 transition hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-brand-secondary">{{ $group['role_label'] }}</p>
                                        <p class="text-xs text-slate-400">{{ count($group['users']) }} persona{{ count($group['users']) === 1 ? '' : 's' }}</p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </summary>

                                <div class="border-t border-slate-100">
                                    @foreach ($group['users'] as $teamUser)
                                        <a href="{{ route('chat.beta', ['recipient' => $teamUser['id']]) }}" class="flex cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-slate-50">
                                            <img src="{{ $teamUser['avatar_url'] }}" alt="Avatar de {{ $teamUser['name'] }}" class="h-10 w-10 rounded-2xl object-cover">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold {{ in_array($teamUser['id'], $favoriteUserIds, true) ? 'text-amber-600' : 'text-brand-secondary' }}">{{ $teamUser['name'] }}</p>
                                                <p class="truncate text-xs text-slate-500">{{ $teamUser['chat_role_label'] }}{{ $teamUser['resolved_dealership_name'] ? ' · ' . $teamUser['resolved_dealership_name'] : '' }}</p>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-slate-500">
                                No hay usuarios activos para mostrar.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="hidden" data-chat-sidebar-panel="groups">
                    <div class="border-y border-slate-100 px-4 py-8 text-center text-sm text-slate-500">
                        Próximamente.
                    </div>
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
                            <img src="{{ $selectedParticipant->avatar_url }}" alt="Avatar de {{ $selectedParticipant->name }}" class="h-11 w-11 rounded-2xl object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75" data-chat-header-avatar>
                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-2xl bg-brand-secondary/0 text-[10px] font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                                Ver
                            </span>
                        </button>
                        <a href="{{ route('users.show', $selectedParticipant) }}" class="min-w-0 transition hover:opacity-90" aria-label="Ver perfil de {{ $selectedParticipant->name }}">
                            <span class="flex min-w-0 items-center gap-2">
                                <h1 class="truncate text-base font-semibold text-brand-secondary" data-chat-header-name>{{ $selectedParticipant->name }}</h1>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-amber-500 {{ $selectedParticipantIsFavorite ? '' : 'hidden' }}" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-chat-favorite-star>
                                    <path d="M11.245 4.174C11.4765 3.50808 11.5922 3.17513 11.7634 3.08285C11.9115 3.00298 12.0898 3.00298 12.238 3.08285C12.4091 3.17513 12.5248 3.50808 12.7563 4.174L14.2866 8.57639C14.3525 8.76592 14.3854 8.86068 14.4448 8.93125C14.4972 8.99359 14.5641 9.04218 14.6396 9.07278C14.725 9.10743 14.8253 9.10947 15.0259 9.11356L19.6857 9.20852C20.3906 9.22288 20.743 9.23007 20.8837 9.36432C21.0054 9.48051 21.0605 9.65014 21.0303 9.81569C20.9955 10.007 20.7146 10.2199 20.1528 10.6459L16.4387 13.4616C16.2788 13.5829 16.1989 13.6435 16.1501 13.7217C16.107 13.7909 16.0815 13.8695 16.0757 13.9507C16.0692 14.0427 16.0982 14.1387 16.1563 14.3308L17.506 18.7919C17.7101 19.4667 17.8122 19.8041 17.728 19.9793C17.6551 20.131 17.5108 20.2358 17.344 20.2583C17.1513 20.2842 16.862 20.0829 16.2833 19.6802L12.4576 17.0181C12.2929 16.9035 12.2106 16.8462 12.1211 16.8239C12.042 16.8043 11.9593 16.8043 11.8803 16.8239C11.7908 16.8462 11.7084 16.9035 11.5437 17.0181L7.71805 19.6802C7.13937 20.0829 6.85003 20.2842 6.65733 20.2583C6.49056 20.2358 6.34626 20.131 6.27337 19.9793C6.18915 19.8041 6.29123 19.4667 6.49538 18.7919L7.84503 14.3308C7.90313 14.1387 7.93218 14.0427 7.92564 13.9507C7.91986 13.8695 7.89432 13.7909 7.85123 13.7217C7.80246 13.6435 7.72251 13.5829 7.56262 13.4616L3.84858 10.6459C3.28678 10.2199 3.00588 10.007 2.97101 9.81569C2.94082 9.65014 2.99594 9.48051 3.11767 9.36432C3.25831 9.23007 3.61074 9.22289 4.31559 9.20852L8.9754 9.11356C9.176 9.10947 9.27631 9.10743 9.36177 9.07278C9.43726 9.04218 9.50414 8.99359 9.55657 8.93125C9.61593 8.86068 9.64887 8.76592 9.71475 8.57639L11.245 4.174Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <p class="truncate text-xs text-slate-500" data-chat-header-role>{{ $selectedParticipant->chat_role_label }} · {{ $selectedParticipant->resolved_dealership_name ?: 'Sin delegación' }}</p>
                        </a>
                    </div>

                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button
                            type="button"
                            data-chat-contact-menu-button
                            @click="open = !open"
                            class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl text-slate-500 transition hover:bg-slate-50 hover:text-brand-secondary"
                            aria-label="Acciones del contacto"
                            :aria-expanded="open.toString()"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <circle cx="5" cy="12" r="1.8" />
                                <circle cx="12" cy="12" r="1.8" />
                                <circle cx="19" cy="12" r="1.8" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute right-0 top-full z-20 mt-2 w-56 overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-xl">
                            <form method="POST" action="{{ route('chat.beta.favorites.toggle', $selectedParticipant) }}" data-chat-favorite-toggle-form data-chat-favorite-toggle-url-template="{{ route('chat.beta.favorites.toggle', ['user' => '__USER_ID__']) }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-full cursor-pointer items-center gap-3 px-4 py-3 text-left text-sm font-medium text-brand-secondary transition hover:bg-brand-secondary/5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M11.245 4.174C11.4765 3.50808 11.5922 3.17513 11.7634 3.08285C11.9115 3.00298 12.0898 3.00298 12.238 3.08285C12.4091 3.17513 12.5248 3.50808 12.7563 4.174L14.2866 8.57639C14.3525 8.76592 14.3854 8.86068 14.4448 8.93125C14.4972 8.99359 14.5641 9.04218 14.6396 9.07278C14.725 9.10743 14.8253 9.10947 15.0259 9.11356L19.6857 9.20852C20.3906 9.22288 20.743 9.23007 20.8837 9.36432C21.0054 9.48051 21.0605 9.65014 21.0303 9.81569C20.9955 10.007 20.7146 10.2199 20.1528 10.6459L16.4387 13.4616C16.2788 13.5829 16.1989 13.6435 16.1501 13.7217C16.107 13.7909 16.0815 13.8695 16.0757 13.9507C16.0692 14.0427 16.0982 14.1387 16.1563 14.3308L17.506 18.7919C17.7101 19.4667 17.8122 19.8041 17.728 19.9793C17.6551 20.131 17.5108 20.2358 17.344 20.2583C17.1513 20.2842 16.862 20.0829 16.2833 19.6802L12.4576 17.0181C12.2929 16.9035 12.2106 16.8462 12.1211 16.8239C12.042 16.8043 11.9593 16.8043 11.8803 16.8239C11.7908 16.8462 11.7084 16.9035 11.5437 17.0181L7.71805 19.6802C7.13937 20.0829 6.85003 20.2842 6.65733 20.2583C6.49056 20.2358 6.34626 20.131 6.27337 19.9793C6.18915 19.8041 6.29123 19.4667 6.49538 18.7919L7.84503 14.3308C7.90313 14.1387 7.93218 14.0427 7.92564 13.9507C7.91986 13.8695 7.89432 13.7909 7.85123 13.7217C7.80246 13.6435 7.72251 13.5829 7.56262 13.4616L3.84858 10.6459C3.28678 10.2199 3.00588 10.007 2.97101 9.81569C2.94082 9.65014 2.99594 9.48051 3.11767 9.36432C3.25831 9.23007 3.61074 9.22289 4.31559 9.20852L8.9754 9.11356C9.176 9.10947 9.27631 9.10743 9.36177 9.07278C9.43726 9.04218 9.50414 8.99359 9.55657 8.93125C9.61593 8.86068 9.64887 8.76592 9.71475 8.57639L11.245 4.174Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span data-chat-favorite-toggle-label>{{ $selectedParticipantIsFavorite ? 'Quitar de favoritos' : 'Marcar como favorito' }}</span>
                                </button>
                            </form>
                        </div>
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
                                @foreach (['😀','😁','😂','😃','😍','🥰','😎','🤩','💩','🙌','👍','👏','🔥','✨','❤️','💡','🎯','🚀','💬','🤠','🙏','😆','🥳','🤯'] as $emoji)
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
                const searchInput = document.querySelector('[data-chat-search-input]');
                const searchResults = document.querySelector('[data-chat-search-results]');
                let pollUrl = wrapper?.dataset.pollUrl;
                const summaryRoot = document.querySelector('[data-chat-summary-url]');
                const sidebarList = document.querySelector('[data-chat-conversations-list]');
                const sidebarFavoritesList = document.querySelector('[data-chat-favorites-list]');
                const sidebarUnreadTotal = document.querySelector('[data-chat-unread-total]');
                const summaryUrl = summaryRoot?.dataset.chatSummaryUrl;
                const messagesUrlTemplate = wrapper?.dataset.chatMessagesUrlTemplate;
                const storeUrlTemplate = wrapper?.dataset.chatStoreUrlTemplate;
                const headerName = document.querySelector('[data-chat-header-name]');
                const headerRole = document.querySelector('[data-chat-header-role]');
                const headerAvatar = document.querySelector('[data-chat-header-avatar]');
                const headerFavoriteStar = document.querySelector('[data-chat-favorite-star]');
                const headerFavoriteToggleLabel = document.querySelector('[data-chat-favorite-toggle-label]');
                const headerFavoriteToggleForm = document.querySelector('[data-chat-favorite-toggle-form]');
                const headerFavoriteMenuButton = document.querySelector('[data-chat-contact-menu-button]');
                const sidebarTabButtons = Array.from(document.querySelectorAll('[data-chat-sidebar-tab]'));
                const sidebarPanels = Array.from(document.querySelectorAll('[data-chat-sidebar-panel]'));

                if (!root || !sidebar || !wrapper || !messagesContainer || !form || !input || !pollUrl || !messagesUrlTemplate || !storeUrlTemplate || !attachmentsInput || !attachmentsButton || !attachmentsPreview || !attachmentsChips || !chatError || !emojiButton || !emojiPicker || !searchInput || !searchResults) {
                    return;
                }

                const csrfToken = form.querySelector('input[name="_token"]')?.value ?? '';
                let isSubmitting = false;
                let pollingLocked = false;
                let attachmentSnapshot = [];
                let sidebarTab = 'chats';
                let searchAbortController = null;
                let searchDebounce = null;
                let latestMessageId = Number(messagesContainer.querySelector('[data-message-id]')?.dataset.messageId ?? 0);
                let sidebarSelectedConversationId = Number(summaryRoot?.dataset.selectedConversationId ?? 0);
                const favoriteUserIds = new Set(@js($favoriteUserIds ?? []));
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

                const buildConversationMessagesUrl = (conversationId) => {
                    if (!messagesUrlTemplate) {
                        return '';
                    }

                    return messagesUrlTemplate.replace('__CONVERSATION_ID__', encodeURIComponent(String(conversationId)));
                };

                const buildConversationStoreUrl = (conversationId) => {
                    if (!storeUrlTemplate) {
                        return '';
                    }

                    return storeUrlTemplate.replace('__CONVERSATION_ID__', encodeURIComponent(String(conversationId)));
                };

                const renderAttachmentMarkup = (attachment) => {
                    const url = attachment.url ?? '';
                    const name = attachment.original_name ?? 'archivo';
                    const sizeLabel = attachment.size_label ?? '';
                    const isImageAttachment = Boolean(attachment.is_image ?? false);

                    if (isImageAttachment) {
                        return `
                            <button
                                type="button"
                                data-chat-image-src="${escapeHtml(url)}"
                                data-chat-image-alt="${escapeHtml(name)}"
                                data-chat-image-title="${escapeHtml(name)}"
                                onclick="window.dispatchEvent(new CustomEvent('open-image', { detail: { src: this.dataset.chatImageSrc, alt: this.dataset.chatImageAlt, title: this.dataset.chatImageTitle } }))"
                                class="group relative block cursor-pointer overflow-hidden rounded-[1rem] border border-black/5 bg-white/50 text-left transition hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                aria-label="Ver ${escapeHtml(name)}"
                            >
                                <img src="${escapeHtml(url)}" alt="${escapeHtml(name)}" class="max-h-72 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">
                                    Ver
                                </span>
                            </button>
                        `;
                    }

                    return `
                        <a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-[1rem] border border-black/5 bg-white/60 px-3 py-2 transition hover:bg-white">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-brand-secondary">${escapeHtml(name)}</span>
                                ${sizeLabel !== '' ? `<span class="block text-xs text-slate-500">${escapeHtml(sizeLabel)}</span>` : ''}
                            </span>
                        </a>
                    `;
                };

                const renderMessage = (message, index, messages) => {
                    const isMine = Boolean(message.is_mine);
                    const nextMessage = messages[index + 1];
                    const currentTimeLabel = message.created_at_label || '';
                    const nextTimeLabel = nextMessage?.created_at_label || '';
                    const showTime = Boolean(message.show_time ?? (index === messages.length - 1 || nextTimeLabel !== currentTimeLabel));
                    const messageAttachments = Array.isArray(message.attachments) ? message.attachments : [];
                    const body = message.body || '';
                    const attachmentsHtml = messageAttachments.map((attachment) => renderAttachmentMarkup(attachment)).join('');

                    return `
                        <div class="flex ${isMine ? 'justify-end' : 'justify-start'} ${index === 0 ? 'mt-0' : (showTime ? 'mt-3' : 'mt-0.5')}" data-message-id="${message.id}">
                            <div class="flex max-w-[78%] flex-col ${isMine ? 'items-end' : 'items-start'}">
                                <div class="relative rounded-[1.1rem] px-3 py-2 shadow-sm ${isMine ? 'bg-[#d9fdd3] pb-5 text-slate-800' : 'border border-slate-200 bg-white text-brand-secondary'}">
                                    ${body !== '' ? `<p class="whitespace-pre-line text-[15px] leading-[1.45]">${escapeHtml(body)}</p>` : ''}
                                    ${attachmentsHtml !== '' ? `<div class="${body !== '' ? 'mt-2' : ''} space-y-2">${attachmentsHtml}</div>` : ''}
                                    ${isMine ? `<span class="absolute bottom-1.5 right-2 inline-flex items-center ${message.read_at ? 'text-sky-500' : 'text-slate-400'}" data-message-checks>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                            <line x1="13.22" y1="16.5" x2="21" y2="7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                            <polyline points="3 11.88 7 16.5 14.78 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" />
                                        </svg>
                                    </span>` : ''}
                                </div>
                                <div class="${showTime ? 'mt-1' : 'mt-0.5'} flex items-center gap-1 text-[11px] ${isMine ? 'justify-end text-slate-500' : 'justify-start text-slate-400'}">
                                    <span data-message-time ${showTime ? '' : 'class="hidden"'}>${escapeHtml(currentTimeLabel)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                };

                const renderMessages = (messages) => {
                    const safeMessages = Array.isArray(messages) ? messages : [];

                    if (safeMessages.length === 0) {
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

                    messagesContainer.innerHTML = safeMessages.map((message, index) => renderMessage(message, index, safeMessages)).join('');
                    latestMessageId = Number(safeMessages[safeMessages.length - 1]?.id ?? 0);
                    autoScroll();
                };

                const renderConversation = (conversation) => {
                    const isSelected = Number(conversation.id) === Number(sidebarSelectedConversationId);
                    const itemClass = isSelected ? 'bg-brand-primary/10' : 'hover:bg-slate-50';
                    const unreadBadge = Number(conversation.unread_messages_count || 0);
                    const nameClass = conversation.partner_is_favorite ? 'text-amber-600' : 'text-brand-secondary';
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
                                        <p class="truncate text-sm font-semibold ${nameClass}" data-chat-partner-name>${escapeHtml(conversation.partner_name || 'Conversación')}</p>
                                        <p class="truncate text-xs text-slate-500" data-chat-partner-role>${escapeHtml(conversation.partner_chat_role_label || '')}</p>
                                        <p class="truncate text-xs text-slate-500" data-chat-last-message>${escapeHtml(conversation.last_message_excerpt || 'Empieza la conversación')}</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] text-slate-400" data-chat-last-message-at>${escapeHtml(conversation.last_message_at_label || '')}</span>
                                </div>
                            </div>
                        </a>
                    `;
                };

                const renderFavoriteContact = (contact) => {
                    return `
                        <a href="{{ route('chat.beta') }}?recipient=${encodeURIComponent(contact.id)}"
                            data-chat-recipient-link
                            class="group flex w-full cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-slate-50">
                            <div class="relative shrink-0">
                                <img src="${escapeHtml(contact.avatar_url || '{{ asset('images/users/hrmotor-default-user-avatar.png') }}')}"
                                    alt="Avatar de ${escapeHtml(contact.name || 'Usuario')}"
                                    class="h-11 w-11 rounded-2xl object-cover">
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-amber-600">${escapeHtml(contact.name || 'Usuario')}</p>
                                        <p class="truncate text-xs text-slate-500">${escapeHtml(contact.chat_role_label || '')}${contact.resolved_dealership_name ? ' · ' + escapeHtml(contact.resolved_dealership_name) : ''}</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    `;
                };

                const setHeaderFavoriteState = (isFavorite) => {
                    if (headerFavoriteStar) {
                        headerFavoriteStar.classList.toggle('hidden', !isFavorite);
                    }

                    if (headerFavoriteToggleLabel) {
                        headerFavoriteToggleLabel.textContent = isFavorite ? 'Quitar de favoritos' : 'Marcar como favorito';
                    }
                };

                const updateHeader = (payload) => {
                    if (headerName && payload.partner_name) {
                        headerName.textContent = payload.partner_name;
                    }

                    if (headerRole) {
                        const partnerRoleLabel = payload.partner_chat_role_label || '';
                        const partnerDealershipName = payload.partner_dealership_name || 'Sin delegación';
                        headerRole.textContent = `${partnerRoleLabel}${partnerRoleLabel ? ' · ' : ''}${partnerDealershipName}`;
                    }

                    if (headerAvatar && payload.partner_avatar_url) {
                        headerAvatar.src = payload.partner_avatar_url;
                        headerAvatar.alt = `Avatar de ${payload.partner_name || 'Usuario'}`;
                    }

                    if (headerFavoriteToggleForm && payload.partner_id) {
                        headerFavoriteToggleForm.action = (headerFavoriteToggleForm.dataset.chatFavoriteToggleUrlTemplate || '').replace('__USER_ID__', String(payload.partner_id));
                    }

                    setHeaderFavoriteState(Boolean(payload.partner_is_favorite));
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

                        sidebarSelectedConversationId = activeConversationId;
                        pollUrl = buildConversationMessagesUrl(activeConversationId);
                        wrapper.dataset.pollUrl = pollUrl;
                        wrapper.dataset.conversationId = String(activeConversationId);

                        updateHeader(payload);
                        renderMessages(messages);
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
                        const nextUrl = new URL(url, window.location.href);

                        if (nextUrl.searchParams.has('conversation')) {
                            await loadConversation(nextUrl.searchParams.get('conversation'), { pushState: true });
                            return;
                        }

                        const response = await fetch(nextUrl.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });

                        const redirectedUrl = new URL(response.url, window.location.href);
                        const conversationId = redirectedUrl.searchParams.get('conversation');

                        if (conversationId) {
                            await loadConversation(conversationId, { pushState: true });
                        }
                    } catch (error) {
                        console.error(error);
                    }
                };

                window.loadConversation = loadConversation;
                window.openConversationFromLink = openConversationFromLink;

                const renderAttachmentsPreview = () => {
                    if (!attachmentSnapshot.length) {
                        attachmentsPreview.classList.add('hidden');
                        attachmentsPreview.innerHTML = '';
                        attachmentsChips.classList.add('hidden');
                        attachmentsChips.innerHTML = '';
                        return;
                    }

                    const previewText = attachmentSnapshot.map((file) => `${file.name} (${Math.ceil(file.size / 1024)} KB)`).join(' · ');
                    attachmentsPreview.textContent = `${attachmentSnapshot.length} archivo${attachmentSnapshot.length === 1 ? '' : 's'} seleccionado${attachmentSnapshot.length === 1 ? '' : 's'}: ${previewText}`;
                    attachmentsPreview.classList.remove('hidden');

                    attachmentsChips.innerHTML = attachmentSnapshot.map((file, index) => `
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-brand-secondary">
                            <span class="truncate max-w-[9rem]">${escapeHtml(file.name)}</span>
                            <button type="button" class="cursor-pointer text-slate-400 transition hover:text-rose-500" data-chat-remove-attachment-index="${index}" aria-label="Quitar ${escapeHtml(file.name)}">
                                ×
                            </button>
                        </span>
                    `).join('');
                    attachmentsChips.classList.remove('hidden');
                };

                const refreshSidebar = async () => {
                    if (!summaryUrl || (!sidebarList && !sidebarFavoritesList)) {
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
                        const favoriteContacts = Array.isArray(payload.favorite_contacts) ? payload.favorite_contacts : [];
                        const unreadTotal = Number(payload.unread_messages_total || 0);

                        if (sidebarUnreadTotal) {
                            sidebarUnreadTotal.textContent = String(unreadTotal);
                        }

                        if (sidebarList) {
                            sidebarList.innerHTML = conversations.length === 0
                                ? `
                                    <div class="px-4 py-8 text-center text-sm text-slate-500">
                                        Sin conversaciones aún
                                    </div>
                                `
                                : conversations.map(renderConversation).join('');
                        }

                        if (sidebarFavoritesList) {
                            sidebarFavoritesList.innerHTML = favoriteContacts.length === 0
                                ? `
                                    <div class="border-y border-slate-100 px-4 py-8 text-center text-sm text-slate-500">
                                        Marca contactos como favoritos para verlos aquí.
                                    </div>
                                `
                                : `
                                    <div class="divide-y divide-slate-100 border-y border-slate-100">
                                        ${favoriteContacts.map(renderFavoriteContact).join('')}
                                    </div>
                                `;
                        }
                    } catch (error) {
                        console.error(error);
                    }
                };

                const syncMessages = async () => {
                    if (!pollUrl || pollingLocked) {
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
                        const nextLatest = Number(messages[messages.length - 1]?.id ?? 0);

                        if (nextLatest !== latestMessageId) {
                            renderMessages(messages);
                        }
                    } catch (error) {
                        console.error(error);
                    }
                };

                const syncAttachmentInputFiles = () => {
                    const dataTransfer = new DataTransfer();

                    attachmentSnapshot.forEach((file) => {
                        dataTransfer.items.add(file);
                    });

                    attachmentsInput.files = dataTransfer.files;
                };

                const isAllowedAttachment = (file) => {
                    const extension = (file?.name?.split('.').pop() || '').toLowerCase();
                    return allowedAttachmentExtensions.includes(extension);
                };

                const showChatError = (message) => {
                    if (!message) {
                        clearChatError();
                        return;
                    }

                    chatError.textContent = message;
                    chatError.classList.remove('hidden');
                };

                const appendAttachments = (files) => {
                    const incomingFiles = Array.from(files || []);

                    if (!incomingFiles.length) {
                        return;
                    }

                    const currentKeys = new Set(attachmentSnapshot.map((file) => `${file.name}:${file.size}:${file.lastModified}`));
                    const accepted = [];
                    const rejected = [];

                    incomingFiles.forEach((file) => {
                        if (!isAllowedAttachment(file)) {
                            rejected.push(file);
                            return;
                        }

                        const key = `${file.name}:${file.size}:${file.lastModified}`;

                        if (currentKeys.has(key) || (attachmentSnapshot.length + accepted.length) >= 4) {
                            return;
                        }

                        currentKeys.add(key);
                        accepted.push(file);
                    });

                    if (accepted.length > 0) {
                        attachmentSnapshot = [...attachmentSnapshot, ...accepted];
                        syncAttachmentInputFiles();
                        renderAttachmentsPreview();
                        clearChatError();
                    }

                    if (rejected.length > 0) {
                        showChatError(`No se puede adjuntar ${rejected[0].name}. Tipo de archivo no permitido.`);
                    }
                };

                const removeAttachmentAtIndex = (index) => {
                    attachmentSnapshot = attachmentSnapshot.filter((_, currentIndex) => currentIndex !== index);
                    syncAttachmentInputFiles();
                    renderAttachmentsPreview();
                };

                const closeEmojiPicker = () => {
                    emojiPicker.classList.add('hidden');
                };

                const toggleEmojiPicker = () => {
                    emojiPicker.classList.toggle('hidden');
                };

                const insertEmoji = (emoji) => {
                    if (!emoji) {
                        return;
                    }

                    const start = input.selectionStart ?? input.value.length;
                    const end = input.selectionEnd ?? input.value.length;
                    const value = input.value;
                    const nextValue = `${value.slice(0, start)}${emoji}${value.slice(end)}`;

                    input.value = nextValue;
                    const nextCursor = start + emoji.length;
                    input.setSelectionRange(nextCursor, nextCursor);
                    input.focus();
                };

                const performLiveSearch = async (searchValue) => {
                    const search = String(searchValue ?? '').trim();

                    if (search === '') {
                        searchResults.innerHTML = '';
                        return;
                    }

                    if (searchAbortController) {
                        searchAbortController.abort();
                    }

                    searchAbortController = new AbortController();

                    try {
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('search', search);
                        nextUrl.searchParams.set('ajax', '1');

                        const response = await fetch(nextUrl.toString(), {
                            signal: searchAbortController.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        searchResults.innerHTML = payload.html || '';
                    } catch (error) {
                        if (error?.name !== 'AbortError') {
                            console.error(error);
                        }
                    }
                };

                const sendMessage = async () => {
                    if (isSubmitting) {
                        return;
                    }

                    clearChatError();

                    const body = input.value.trim();
                    const conversationId = Number(wrapper.dataset.conversationId || sidebarSelectedConversationId || 0);

                    if (!conversationId) {
                        showChatError('No hay ninguna conversación activa.');
                        return;
                    }

                    if (body === '' && attachmentSnapshot.length === 0) {
                        showChatError('Escribe un mensaje o adjunta un archivo.');
                        return;
                    }

                    const url = buildConversationStoreUrl(conversationId);
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('conversation_id', String(conversationId));
                    formData.append('body', input.value);

                    attachmentSnapshot.forEach((file) => {
                        formData.append('attachments[]', file, file.name);
                    });

                    isSubmitting = true;

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                            body: formData,
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            if (response.status === 422 && payload?.errors) {
                                const firstError = Object.values(payload.errors).flat()[0];
                                showChatError(firstError || 'No se pudo enviar el mensaje.');
                            } else {
                                showChatError(payload?.message || 'No se pudo enviar el mensaje.');
                            }
                            return;
                        }

                        input.value = '';
                        attachmentSnapshot = [];
                        syncAttachmentInputFiles();
                        renderAttachmentsPreview();
                        closeEmojiPicker();

                        if (payload.conversation_id) {
                            await loadConversation(payload.conversation_id, { pushState: false });
                        } else {
                            await loadConversation(conversationId, { pushState: false });
                        }

                        await refreshSidebar();
                    } catch (error) {
                        console.error(error);
                        showChatError('No se pudo enviar el mensaje.');
                    } finally {
                        isSubmitting = false;
                        input.focus();
                    }
                };

                window.renderAttachmentsPreview = renderAttachmentsPreview;
                window.refreshSidebar = refreshSidebar;
                window.syncMessages = syncMessages;

                const setSidebarTab = (tab) => {
                    sidebarTab = tab;

                    sidebarTabButtons.forEach((button) => {
                        const isActive = button.dataset.chatSidebarTab === tab;
                        const isFavoriteButton = button.dataset.chatSidebarTab === 'favorites';

                        button.classList.toggle('bg-brand-primary', isActive && !isFavoriteButton);
                        button.classList.toggle('text-white', isActive && !isFavoriteButton);
                        button.classList.toggle('shadow-sm', isActive && !isFavoriteButton);
                        button.classList.toggle('bg-brand-primary/10', isActive && isFavoriteButton);
                        button.classList.toggle('text-brand-primary', isActive && isFavoriteButton);
                        button.classList.toggle('shadow-sm', isActive && isFavoriteButton);
                        button.classList.toggle('text-slate-500', !isActive);
                        button.classList.toggle('hover:bg-slate-100', !isActive && !isFavoriteButton);
                        button.classList.toggle('hover:bg-brand-primary/10', isFavoriteButton && !isActive);
                        button.classList.toggle('hover:text-brand-primary', !isFavoriteButton && !isActive);
                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });

                    sidebarPanels.forEach((panel) => {
                        const isActive = panel.dataset.chatSidebarPanel === tab;
                        panel.classList.toggle('hidden', !isActive);
                    });
                };

                setSidebarTab('chats');

                sidebarTabButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const nextTab = button.dataset.chatSidebarTab === 'favorites' && sidebarTab === 'favorites' ? 'chats' : (button.dataset.chatSidebarTab || 'chats');
                        setSidebarTab(nextTab);
                    });
                });

                attachmentsButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    attachmentsInput.value = '';
                    attachmentsInput.click();
                });

                attachmentsInput.addEventListener('change', (event) => {
                    appendAttachments(event.target.files);
                    event.target.value = '';
                });

                attachmentsChips.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-chat-remove-attachment-index]');

                    if (!removeButton) {
                        return;
                    }

                    const index = Number(removeButton.dataset.chatRemoveAttachmentIndex);

                    if (Number.isNaN(index)) {
                        return;
                    }

                    removeAttachmentAtIndex(index);
                });

                emojiButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    toggleEmojiPicker();
                });

                emojiPicker.addEventListener('click', (event) => {
                    const emojiButton = event.target.closest('[data-chat-emoji-option]');

                    if (!emojiButton) {
                        return;
                    }

                    insertEmoji(emojiButton.dataset.emoji || emojiButton.textContent || '');
                });

                document.addEventListener('click', (event) => {
                    if (emojiPicker.contains(event.target) || emojiButton.contains(event.target)) {
                        return;
                    }

                    closeEmojiPicker();
                });

                searchInput.addEventListener('input', () => {
                    clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(() => {
                        void performLiveSearch(searchInput.value);
                    }, 250);
                });

                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                    }
                });

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    void sendMessage();
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        void sendMessage();
                    }
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
                    await window.openConversationFromLink?.(link.href);
                });

                window.addEventListener('popstate', () => {
                    const conversationId = new URL(window.location.href).searchParams.get('conversation');

                    if (conversationId) {
                        void window.loadConversation?.(conversationId, { pushState: false });
                    }
                });

                autoScroll();
                window.renderAttachmentsPreview?.();
                setInterval(syncMessages, 3000);
                setInterval(refreshSidebar, 5000);
                refreshSidebar();
                syncMessages();
            });
        </script>
    @endif
@endsection

