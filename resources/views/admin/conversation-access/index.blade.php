@extends('layouts.app')

@section('content')
    @php
        $selectedConversationMessages = $selectedConversationMessages ?? collect();
        $selectedParticipantsLabel = $selectedConversation?->retention_hold_target_label ?? 'Selecciona una conversación';
        $selectedConversationTypeLabel = $selectedConversation?->conversation_type_label ?? 'Privada';
        $selectedConversationStatusLabel = $selectedConversationHasAccess ? 'Acceso concedido' : 'Pendiente de justificar';
    @endphp

    <main
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="mx-auto max-w-7xl px-6 py-10 lg:px-8"
    >
        @if ($missingTable ?? false)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                La tabla de auditoría de accesos a conversaciones todavía no existe en esta base de datos. Ejecuta la migración para empezar a registrar accesos.
            </div>
        @endif

        <section
            class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8"
            x-data="conversationAccessPage({{ \Illuminate\Support\Js::from((bool) $showAccessModal) }})"
        >
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Auditoría</span>
                    <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Acceso justificado a conversaciones</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Busca una conversación, selecciona el hilo y registra un motivo antes de ver su contenido. Cada acceso queda auditado.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 xl:w-full xl:max-w-xl">
                    <div class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Tipo</p>
                        <p class="mt-2 text-sm font-semibold text-brand-secondary">{{ $selectedConversationTypeLabel }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Estado</p>
                        <p class="mt-2 text-sm font-semibold {{ $selectedConversationHasAccess ? 'text-emerald-600' : 'text-amber-600' }}">{{ $selectedConversationStatusLabel }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Conversación</p>
                        <p class="mt-2 text-sm font-semibold text-brand-secondary">{{ $selectedConversation?->id ? '#' . $selectedConversation->id : 'Sin seleccionar' }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
                <aside class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm">
                    <form method="GET" action="{{ route('admin.conversation-access.index') }}" class="flex flex-col gap-3">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45" for="conversation-search">Buscar conversación</label>
                        <div class="flex gap-2">
                            <input
                                id="conversation-search"
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Usuario, email o # de conversación"
                                class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                            >
                            <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                                Buscar
                            </button>
                        </div>
                    </form>

                    <div class="mt-5 max-h-[34rem] space-y-3 overflow-y-auto pr-1">
                        @forelse ($conversations as $conversation)
                            @php
                                $isSelected = (int) ($selectedConversation?->id ?? 0) === (int) $conversation->id;
                                $isGranted = (bool) data_get($grantMap, (string) $conversation->id);
                            @endphp

                            <a
                                href="{{ route('admin.conversation-access.index', array_filter(['search' => $search, 'conversation' => $conversation->id], fn ($value) => filled($value) || $value === 0)) }}"
                                class="block rounded-[1.5rem] border px-4 py-4 transition {{ $isSelected ? 'border-brand-primary/30 bg-brand-primary/5 shadow-sm' : 'border-brand-secondary/10 bg-white hover:-translate-y-0.5 hover:border-brand-primary/20 hover:shadow-sm' }}"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">{{ $conversation->conversation_type_label }} · #{{ $conversation->id }}</p>
                                        <p class="mt-1 truncate text-sm font-semibold text-brand-secondary">{{ $conversation->retention_hold_target_label }}</p>
                                        <p class="mt-1 truncate text-xs text-brand-secondary/65">
                                            {{ $conversation->userOne?->email }} · {{ $conversation->userTwo?->email }}
                                        </p>
                                    </div>

                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $isGranted ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $isGranted ? 'Acceso' : 'Pendiente' }}
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-brand-secondary/60">
                                    <span>{{ $conversation->last_message_at?->format('d/m/Y H:i') ?? 'Sin mensajes' }}</span>
                                    <span>{{ $conversation->messages_count ?? $conversation->messages()->count() }} mensajes</span>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                                No hay conversaciones que coincidan con la búsqueda.
                            </div>
                        @endforelse
                    </div>
                </aside>

                <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm md:p-6">
                    @if ($selectedConversation)
                        <div class="flex flex-col gap-4 border-b border-brand-secondary/10 pb-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary/80">Vista previa protegida</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">{{ $selectedParticipantsLabel }}</h2>
                                    <p class="mt-2 text-sm text-brand-secondary/70">
                                        Conversación #{{ $selectedConversation->id }} · {{ $selectedConversationTypeLabel }} · último mensaje {{ $selectedConversation->last_message_at?->format('d/m/Y H:i') ?? 'sin fecha' }}
                                    </p>
                                </div>

                                <div class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-secondary/60 shadow-sm">
                                    {{ $selectedConversationStatusLabel }}
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Participante 1</p>
                                    <p class="mt-1 text-sm font-semibold text-brand-secondary">{{ $selectedConversation->userOne?->name ?? 'N/D' }}</p>
                                    <p class="mt-1 text-xs text-brand-secondary/60">{{ $selectedConversation->userOne?->email ?? 'N/D' }}</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Participante 2</p>
                                    <p class="mt-1 text-sm font-semibold text-brand-secondary">{{ $selectedConversation->userTwo?->name ?? 'N/D' }}</p>
                                    <p class="mt-1 text-xs text-brand-secondary/60">{{ $selectedConversation->userTwo?->email ?? 'N/D' }}</p>
                                </div>
                            </div>
                        </div>

                        @if ($selectedConversationHasAccess)
                            <div class="mt-5">
                                <div class="mx-auto flex min-h-full max-w-5xl flex-col justify-end">
                                    <div class="space-y-0">
                                        @php
                                            $chatDateToday = now()->startOfDay();
                                            $chatDateYesterday = $chatDateToday->copy()->subDay();
                                            $chatDateWeekStart = $chatDateToday->copy()->startOfWeek();

                                            $chatDateLabel = static function ($date) use ($chatDateToday, $chatDateYesterday, $chatDateWeekStart) {
                                                if (! $date) {
                                                    return '';
                                                }

                                                $date = $date->copy()->startOfDay();

                                                if ($date->equalTo($chatDateToday)) {
                                                    return 'Hoy';
                                                }

                                                if ($date->equalTo($chatDateYesterday)) {
                                                    return 'Ayer';
                                                }

                                                if ($date->greaterThanOrEqualTo($chatDateWeekStart)) {
                                                    return mb_strtolower($date->translatedFormat('l'));
                                                }

                                                return $date->translatedFormat('d/m/Y');
                                            };
                                        @endphp

                                        @forelse ($selectedConversationMessages as $message)
                                            @php
                                                $isFromUserOne = $message->sender_id === $selectedConversation->user_one_id;
                                                $previousMessage = $selectedConversationMessages->get($loop->index - 1);
                                                $nextMessage = $selectedConversationMessages->get($loop->index + 1);
                                                $currentDateKey = $message->created_at?->format('Y-m-d');
                                                $previousDateKey = $previousMessage?->created_at?->format('Y-m-d');
                                                $nextDateKey = $nextMessage?->created_at?->format('Y-m-d');
                                                $currentTimeLabel = $message->created_at?->translatedFormat('H:i');
                                                $previousTimeLabel = $previousMessage?->created_at?->translatedFormat('H:i');
                                                $nextTimeLabel = $nextMessage?->created_at?->translatedFormat('H:i');
                                                $showDateSeparator = $loop->first || $previousDateKey !== $currentDateKey;
                                                $showTime = $loop->last || $nextDateKey !== $currentDateKey || $nextTimeLabel !== $currentTimeLabel;
                                                $topMarginClass = $loop->first ? 'mt-0' : (($previousDateKey === $currentDateKey && $previousTimeLabel === $currentTimeLabel) ? 'mt-0.5' : 'mt-3');
                                                $messageAttachments = collect($message->attachments ?? []);
                                                $isDeleted = $message->trashed();
                                                $isEdited = $message->edited_at !== null && ! $isDeleted;
                                            @endphp

                                            @if ($showDateSeparator)
                                                <div class="my-5 flex justify-center">
                                                    <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-[11px] font-semibold text-sky-700 shadow-sm ring-1 ring-sky-200">
                                                        {{ $chatDateLabel($message->created_at) }}
                                                    </span>
                                                </div>
                                            @endif

                                            <div class="flex {{ $isFromUserOne ? 'justify-start' : 'justify-end' }} {{ $topMarginClass }}">
                                                <div class="flex max-w-[78%] flex-col {{ $isFromUserOne ? 'items-start' : 'items-end' }}">
                                                    <div class="group relative min-w-[5rem] rounded-[1.1rem] px-3 py-2 shadow-sm transition {{ $isDeleted ? 'border border-rose-200 bg-rose-50 text-rose-700' : ($isFromUserOne ? 'border border-slate-200 bg-white text-brand-secondary' : 'bg-[#d9fdd3] pb-4 pr-8 text-slate-800 hover:shadow-md') }}">
                                                        <div>
                                                            @if ($isDeleted)
                                                                <div class="mb-1.5 inline-flex items-center gap-1.5">
                                                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-rose-700">
                                                                        Eliminado
                                                                    </span>
                                                                </div>
                                                                <div class="flex items-center gap-2 text-sm font-medium text-rose-700">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 self-center text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 2.82 18a2 2 0 0 0 1.75 3h15.86a2 2 0 0 0 1.75-3L14.71 3.86a2 2 0 0 0-3.42 0Z" />
                                                                    </svg>
                                                                    <div class="min-w-0 leading-none">
                                                                        @if (filled($message->body))
                                                                            <p class="whitespace-pre-line py-0.5 text-[15px] leading-none line-through decoration-rose-400 decoration-2 decoration-from-font">
                                                                                {{ $message->body }}
                                                                            </p>
                                                                        @else
                                                                            <p>Mensaje eliminado sin texto.</p>
                                                                        @endif
                                                                        @if ($messageAttachments->isNotEmpty())
                                                                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-rose-500">
                                                                                {{ $messageAttachments->count() }} adjunto{{ $messageAttachments->count() === 1 ? '' : 's' }} eliminado{{ $messageAttachments->count() === 1 ? '' : 's' }}
                                                                            </p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @elseif (filled($message->body))
                                                                <p class="whitespace-pre-line text-[15px] leading-[1.45]">{{ $message->body }}</p>
                                                            @endif

                                                            @if (! $isDeleted && $messageAttachments->isNotEmpty())
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

                                                            @if (! $isFromUserOne && ! $isDeleted)
                                                                <span class="absolute bottom-1.5 right-3 inline-flex items-center {{ $message->read_at ? 'text-sky-500' : 'text-slate-400' }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                                        <line x1="13.22" y1="16.5" x2="21" y2="7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                                        <polyline points="3 11.88 7 16.5 14.78 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" />
                                                                    </svg>
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="{{ $showTime ? 'mt-1' : 'mt-0.5' }} flex items-center gap-1 text-[11px] {{ $isFromUserOne ? 'justify-start text-slate-400' : 'justify-end text-slate-500' }}">
                                                        @if ($isEdited)
                                                            <span class="text-[10px] italic text-slate-400">Editado</span>
                                                        @endif
                                                        <span @if (! $showTime) class="hidden" @endif>{{ $currentTimeLabel }}</span>
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
                        @else
                            <div class="mt-6 rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"></path>
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-brand-secondary">Acceso pendiente de justificación</h3>
                                <p class="mt-2 text-sm leading-6 text-brand-secondary/65">
                                    Antes de ver cualquier mensaje debes registrar un motivo. El acceso quedará auditado.
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="flex min-h-[32rem] items-center justify-center rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                            <div class="max-w-md">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"></path>
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-brand-secondary">Selecciona una conversación</h3>
                                <p class="mt-2 text-sm leading-6 text-brand-secondary/65">
                                    Filtra por usuario, email o identificador y pulsa en una conversación para justificar el acceso antes de ver su contenido.
                                </p>
                            </div>
                        </div>
                    @endif
                </section>
            </div>

            <div
                x-show="showAccessModal"
                x-cloak
                class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/60 px-4 py-8 backdrop-blur-sm"
                @keydown.escape.window="showAccessModal = false"
            >
                <div class="absolute inset-0" @click="showAccessModal = false"></div>

                <form method="POST" action="{{ route('admin.conversation-access.store') }}" class="relative z-10 w-full max-w-2xl rounded-[2.5rem] border border-white/70 bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)] md:p-8">
                    @csrf
                    <input type="hidden" name="conversation_id" value="{{ $selectedConversation?->id }}">

                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none">
                                <circle opacity="0.5" cx="12" cy="12" r="10" stroke="#1C274C" stroke-width="1.5"/>
                                <path d="M9 17C9.85038 16.3697 10.8846 16 12 16C13.1154 16 14.1496 16.3697 15 17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/>
                                <ellipse cx="15" cy="10.5" rx="1" ry="1.5" fill="#1C274C"/>
                                <ellipse cx="9" cy="10.5" rx="1" ry="1.5" fill="#1C274C"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-2xl font-semibold text-brand-secondary">Vas a acceder al contenido de una conversación en la que no participas.</h2>
                            <div class="mt-4 space-y-3 text-sm leading-6 text-brand-secondary/70">
                                <p>Este acceso debe estar justificado por motivos de seguridad, cumplimiento normativo, investigación de incidencias, mantenimiento técnico, requerimiento legal o control laboral proporcionado.</p>
                                <p>El acceso quedará registrado en el sistema de auditoría.</p>
                                <p>Indica el motivo del acceso:</p>
                            </div>

                            <label class="mt-5 block text-sm font-semibold text-brand-secondary" for="access-reason">Motivo</label>
                            <textarea
                                id="access-reason"
                                name="reason"
                                required
                                rows="5"
                                maxlength="2000"
                                class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                placeholder="Describe el motivo justificado del acceso..."
                            ></textarea>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <button type="button" class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-slate-50" @click="showAccessModal = false">
                                    Cancelar
                                </button>
                                <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                    Registrar motivo y acceder
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
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
    </main>

    <script>
        function conversationAccessPage(initialOpen = false) {
            return {
                showAccessModal: Boolean(initialOpen),
            };
        }
    </script>
@endsection
