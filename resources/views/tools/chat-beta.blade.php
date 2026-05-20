@extends('layouts.chat-shell')

@section('content')
    @php
        $authUser = auth()->user();
        $selectedParticipant = $selectedConversation?->otherParticipant($authUser);
        $selectedConversationMessages = $selectedConversation?->messages ?? collect();
    @endphp

    <section class="flex min-h-0 flex-1 w-full overflow-hidden bg-slate-100">
        <aside class="flex h-full w-[21rem] min-w-[21rem] max-w-[21rem] flex-col border-r border-slate-200 bg-white shadow-[12px_0_40px_rgba(15,23,42,0.04)]">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-2">
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
                                        <p class="truncate text-xs text-slate-500">{{ $person->role_label }}</p>
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
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Recientes</p>
                        <span class="text-xs text-slate-400">{{ $conversations->count() }}</span>
                    </div>

                    <div class="space-y-1.5">
                        @forelse ($conversations as $conversation)
                            @php
                                $partner = $conversation->otherParticipant($authUser);
                                $isSelected = $selectedConversation?->id === $conversation->id;
                            @endphp
                            <a href="{{ route('chat.beta', ['conversation' => $conversation->id]) }}"
                                class="group flex cursor-pointer items-center gap-3 rounded-2xl px-3 py-2.5 transition {{ $isSelected ? 'bg-brand-primary/10 ring-1 ring-brand-primary/15' : 'hover:bg-slate-50' }}">
                                <div class="relative shrink-0">
                                    <img src="{{ $partner?->avatar_url ?? asset('images/users/hrmotor-default-user-avatar.png') }}"
                                        alt="Avatar de {{ $partner?->name ?? 'Usuario' }}"
                                        class="h-11 w-11 rounded-2xl object-cover">
                                    @if (($conversation->unread_messages_count ?? 0) > 0)
                                        <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-primary px-1 text-[11px] font-semibold text-white">
                                            {{ $conversation->unread_messages_count }}
                                        </span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-brand-secondary">{{ $partner?->name ?? 'Conversación' }}</p>
                                            <p class="truncate text-xs text-slate-500">
                                                {{ $conversation->last_message_excerpt ?: 'Empieza la conversación' }}
                                            </p>
                                        </div>

                                        @if ($conversation->last_message_at)
                                            <span class="shrink-0 text-[11px] text-slate-400">
                                                {{ $conversation->last_message_at->translatedFormat('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-brand-secondary">Sin conversaciones aún</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    Busca a un compañero y abre el primer chat.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>

        <section class="flex h-full min-w-0 flex-1 flex-col bg-slate-100">
            @if ($selectedConversation && $selectedParticipant)
                <header class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ $selectedParticipant->avatar_url }}" alt="Avatar de {{ $selectedParticipant->name }}" class="h-11 w-11 rounded-2xl object-cover">
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-semibold text-brand-secondary">{{ $selectedParticipant->name }}</h1>
                            <p class="truncate text-xs text-slate-500">{{ $selectedParticipant->role_label }} · {{ $selectedParticipant->resolved_dealership_name ?: 'Sin delegación' }}</p>
                        </div>
                    </div>

                    <div class="hidden items-center gap-2 text-slate-400 md:flex">
                        <button type="button" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition hover:bg-slate-100 hover:text-brand-primary" aria-label="Añadir persona">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9l-6-6m0 0-6 6m6-6v18" />
                            </svg>
                        </button>
                        <button type="button" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition hover:bg-slate-100 hover:text-brand-primary" aria-label="Llamar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6.62 10.79a15.53 15.53 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24 11.36 11.36 0 0 0 3.56.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A18 18 0 0 1 4 6a1 1 0 0 1 1-1h2.49a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.56 1 1 0 0 1-.24 1.02Z" />
                            </svg>
                        </button>
                        <button type="button" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition hover:bg-slate-100 hover:text-brand-primary" aria-label="Más opciones">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 12a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm5 0a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm7-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                            </svg>
                        </button>
                    </div>
                </header>

                <div x-data x-init="$nextTick(() => { $refs.messages.scrollTop = $refs.messages.scrollHeight; })"
                    class="flex-1 min-h-0 overflow-y-auto px-4 py-5 sm:px-6">
                    <div class="mx-auto flex min-h-full max-w-5xl flex-col justify-end" x-ref="messages">
                        <div class="space-y-3">
                            @forelse ($selectedConversationMessages as $message)
                                @php
                                    $isMine = $message->sender_id === $authUser->id;
                                @endphp
                                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[78%] rounded-[1.25rem] px-4 py-3 shadow-sm {{ $isMine ? 'bg-[#d9fdd3] text-slate-800' : 'bg-white text-brand-secondary border border-slate-200' }}">
                                        <p class="whitespace-pre-line text-[15px] leading-6">{{ $message->body }}</p>
                                        <div class="mt-1 flex items-center justify-end gap-1 text-[11px] {{ $isMine ? 'text-slate-500' : 'text-slate-400' }}">
                                            <span>{{ $message->created_at->translatedFormat('H:i') }}</span>
                                            @if ($isMine)
                                                <span>✓✓</span>
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
                    <form method="POST" action="{{ route('chat.beta.messages.store', $selectedConversation) }}" class="mx-auto max-w-5xl">
                        @csrf
                        <div class="flex items-end gap-3 rounded-[1.75rem] border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <button type="button"
                                class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-slate-400 transition hover:bg-slate-100 hover:text-brand-primary"
                                aria-label="Adjuntar archivo">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.5 12.5 21a6.364 6.364 0 1 1-9-9L12 3.5a4.243 4.243 0 1 1 6 6L8.5 19a2.121 2.121 0 1 1-3-3L14 7.5" />
                                </svg>
                            </button>

                            <textarea name="body" rows="1" placeholder="Escribir mensaje..."
                                class="max-h-40 flex-1 resize-none border-0 bg-transparent px-0 py-2 text-[15px] text-brand-secondary outline-none placeholder:text-slate-400 focus:ring-0">{{ old('body') }}</textarea>

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
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-primary">Chat interno</p>
                        <h2 class="mt-4 text-2xl font-bold tracking-tight text-brand-secondary">Busca a un compañero para empezar</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Selecciona una conversación reciente o usa la lupa para abrir un chat nuevo.
                        </p>
                    </div>
                </div>
            @endif
        </section>
    </section>
@endsection
