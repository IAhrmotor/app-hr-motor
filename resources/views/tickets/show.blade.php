@extends('layouts.app')

@section('content')
    @php
        $priorityMeta = $ticketPriorities[$ticket->priority] ?? ['label' => $ticket->priority, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
        $statusMeta = $ticketStatuses[$ticket->status] ?? ['label' => $ticket->status, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
        $messages = $ticket->messages ?? collect();
        $conversationMessagesCount = $messages->count() + 1;
        $activityLogs = $ticket->activityLogs ?? collect();
        $isVisibleItRole = app_visible_role(auth()->user()) === \App\Models\User::ROLE_INFORMATION_TECHNOLOGY;
        $isClosed = $ticket->status === 'closed';
        $ticketOpeningAttachments = collect($ticket->screenshots ?? []);
    @endphp

    <main
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="mx-auto w-full max-w-7xl flex-1 px-6 py-8 lg:px-8"
    >
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
                <div
                    class="relative bg-cover bg-no-repeat px-6 py-8 sm:px-8 sm:py-10"
                    style="background-image: url('{{ asset('images/hero/hero-interior-ticket.webp') }}'); background-position: center 68%;"
                >
                    <div class="absolute inset-0 bg-slate-950/70"></div>
                    <div class="relative flex flex-col gap-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div class="space-y-3">
                                <div class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm">
                                    Detalle del ticket
                                </div>
                                <div class="space-y-2">
                                    <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                                        <span>{{ $ticket->number }}</span>
                                        <span class="mx-2 text-white/60">·</span>
                                        <span>{{ $ticket->title }}</span>
                                    </h1>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-white/20 {{ $statusMeta['badge'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                                @if ($isVisibleItRole)
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-white/20 {{ $priorityMeta['badge'] }}">
                                        {{ $priorityMeta['label'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                            <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-[1.25rem] bg-white/10 px-4 py-4 text-white backdrop-blur-sm ring-1 ring-white/10">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/55">Herramienta</dt>
                                    <dd class="mt-2 text-base font-semibold text-white sm:text-lg">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $ticket->ticketTool?->color ?? '#1d4ed8' }}"></span>
                                            <span>{{ $ticket->ticketTool?->name ?? $ticket->tool }}</span>
                                        </span>
                                    </dd>
                                </div>

                                <div class="rounded-[1.25rem] bg-white/10 px-4 py-4 text-white backdrop-blur-sm ring-1 ring-white/10">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/55">Solicitante</dt>
                                    <dd class="mt-2 text-base font-semibold text-white sm:text-lg">
                                        {{ $ticket->user?->name ?? 'Sin nombre' }}
                                    </dd>
                                </div>

                                <div class="rounded-[1.25rem] bg-white/10 px-4 py-4 text-white backdrop-blur-sm ring-1 ring-white/10">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/55">Asignado a</dt>
                                    <dd class="mt-2 text-base font-semibold text-white sm:text-lg">
                                        {{ $ticket->assignedTo?->name ?? 'Sin asignar' }}
                                    </dd>
                                </div>

                                <div class="rounded-[1.25rem] bg-white/10 px-4 py-4 text-white backdrop-blur-sm ring-1 ring-white/10">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/55">Actualizado</dt>
                                    <dd class="mt-2 text-base font-semibold text-white sm:text-lg">
                                        {{ $ticket->updated_at?->format('d/m/Y H:i') }}
                                    </dd>
                                </div>
                            </dl>

                            @if ($isVisibleItRole)
                                <a href="{{ $backUrl }}"
                                    class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-brand-secondary shadow-sm transition hover:-translate-y-0.5">
                                    Volver
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.65fr)]">
                <div class="space-y-6">
                    <article class="flex max-h-[calc(100vh-14rem)] flex-col overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-brand-secondary/10 pb-4">
                            <h2 class="text-xl font-bold tracking-tight text-brand-secondary">Hilo de conversación</h2>
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/40">
                                {{ $conversationMessagesCount }} mensajes
                            </span>
                        </div>

                        <div class="mt-5 flex-1 space-y-3 overflow-y-auto pr-2">
                            @php
                                $openingAttachments = $ticketOpeningAttachments;
                                $openingIsMine = (int) ($ticket->user_id ?? 0) === (int) auth()->id();
                                $openingAlignClass = $openingIsMine ? 'justify-end' : 'justify-start';
                                $openingToneClass = $openingIsMine
                                    ? 'bg-[#d9fdd3] text-slate-800 shadow-sm'
                                    : 'border border-slate-200 bg-white text-brand-secondary shadow-sm';
                                $openingMetaClass = $openingIsMine ? 'justify-end text-slate-500' : 'justify-start text-slate-400';
                            @endphp

                            <div class="flex {{ $openingAlignClass }}">
                                <div class="flex w-fit max-w-[82%] min-w-0 flex-col">
                                    <div class="group relative min-w-[5.5rem] max-w-full overflow-x-hidden rounded-[1.1rem] px-4 py-3 {{ $openingToneClass }}">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] {{ $openingIsMine ? 'text-slate-700' : 'text-brand-secondary' }}">
                                                {{ $ticket->user?->name ?? 'Usuario' }}
                                            </span>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] bg-brand-primary/10 text-brand-primary">
                                                Incidencia
                                            </span>
                                        </div>

                                        <p class="mt-2 whitespace-pre-line break-words [overflow-wrap:anywhere] text-[15px] leading-[1.45]">
                                            {{ $ticket->description }}
                                        </p>

                                        @if ($openingAttachments->isNotEmpty())
                                            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($openingAttachments as $screenshot)
                                                    @php
                                                        $screenshotPath = data_get($screenshot, 'path');
                                                        $screenshotName = data_get($screenshot, 'name', basename((string) $screenshotPath));
                                                    @endphp
                                                    @if ($screenshotPath)
                                                        <button
                                                            type="button"
                                                            @click="openImage({ src: @js(\Illuminate\Support\Facades\Storage::disk('public')->url($screenshotPath)), alt: @js($screenshotName), title: @js($screenshotName) })"
                                                            class="group relative cursor-zoom-in overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                                            aria-label="Ver imagen {{ $screenshotName }}"
                                                        >
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($screenshotPath) }}" alt="{{ $screenshotName }}" class="h-32 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">Ver</span>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-1 flex items-center gap-1 text-[11px] {{ $openingMetaClass }}">
                                        <span>{{ $ticket->created_at?->translatedFormat('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            @forelse ($messages as $message)
                                @php
                                    $messageAttachments = collect($message->attachments ?? []);
                                    $isTicketOwnerMessage = $message->user_id === $ticket->user_id;
                                    $isMineMessage = (int) $message->user_id === (int) auth()->id();
                                    $messageAlignClass = $isMineMessage ? 'justify-end' : 'justify-start';
                                    $messageToneClass = $isMineMessage
                                        ? 'bg-[#d9fdd3] text-slate-800 shadow-sm'
                                        : 'border border-slate-200 bg-white text-brand-secondary shadow-sm';
                                    $messageMetaClass = $isMineMessage ? 'justify-end text-slate-500' : 'justify-start text-slate-400';
                                @endphp

                                <div class="flex {{ $messageAlignClass }}">
                                    <div class="flex w-fit max-w-[82%] min-w-0 flex-col">
                                        <div class="group relative min-w-[5.5rem] max-w-full overflow-x-hidden rounded-[1.1rem] px-4 py-3 {{ $messageToneClass }}">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.16em] {{ $isMineMessage ? 'text-slate-700' : 'text-brand-secondary' }}">
                                                    {{ $message->author?->name ?? 'Usuario' }}
                                                </span>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] {{ $isTicketOwnerMessage ? 'bg-brand-primary/10 text-brand-primary' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ $isTicketOwnerMessage ? 'Solicitante' : 'IT' }}
                                                </span>
                                            </div>

                                            @if (filled($message->body))
                                                <p class="mt-2 whitespace-pre-line break-words [overflow-wrap:anywhere] text-[15px] leading-[1.45]">
                                                    {{ $message->body }}
                                                </p>
                                            @endif

                                            @if ($messageAttachments->isNotEmpty())
                                                <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($messageAttachments as $attachment)
                                                        @php
                                                            $attachmentPath = data_get($attachment, 'path');
                                                            $attachmentName = data_get($attachment, 'name', basename((string) $attachmentPath));
                                                        @endphp
                                                        @if ($attachmentPath)
                                                            <button
                                                                type="button"
                                                                @click="openImage({ src: @js(\Illuminate\Support\Facades\Storage::disk('public')->url($attachmentPath)), alt: @js($attachmentName), title: @js($attachmentName) })"
                                                                class="group relative cursor-zoom-in overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                                                aria-label="Ver imagen {{ $attachmentName }}"
                                                            >
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachmentPath) }}" alt="{{ $attachmentName }}" class="h-32 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">Ver</span>
                                                            </button>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-1 flex items-center gap-1 text-[11px] {{ $messageMetaClass }}">
                                            <span>{{ $message->created_at?->translatedFormat('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($isClosed)
                            <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white px-5 py-4 text-sm text-brand-secondary/70">
                                Este ticket está cerrado y ya no admite nuevas respuestas.
                            </div>
                        @elseif ($canReplyToTicket)
                            <form method="POST" action="{{ route('tickets.messages.store', $ticket) }}" enctype="multipart/form-data" class="mt-6">
                                @csrf

                                <div class="relative">
                                    <div class="absolute bottom-full left-4 right-4 z-20 mb-3 hidden max-w-2xl overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-2xl" data-ticket-attachments-hint>
                                        <div class="border-b border-slate-100 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Adjuntos</p>
                                        </div>
                                        <div class="px-4 py-3 text-sm text-slate-500">
                                            Puedes adjuntar hasta 4 imágenes en JPG, PNG o WEBP.
                                        </div>
                                    </div>

                                    <div class="relative flex items-end gap-3 rounded-[1.75rem] border border-slate-200 bg-white px-4 py-3 shadow-sm transition">
                                        <label
                                            for="ticket-message-attachments"
                                            class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-slate-400 transition hover:bg-slate-100 hover:text-brand-primary"
                                            aria-label="Adjuntar archivo"
                                            title="Adjuntar archivo"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.5 12.5 21a6.364 6.364 0 1 1-9-9L12 3.5a4.243 4.243 0 1 1 6 6L8.5 19a2.121 2.121 0 1 1-3-3L14 7.5" />
                                            </svg>
                                        </label>

                                        <textarea
                                            name="body"
                                            rows="1"
                                            placeholder="Escribe tu mensaje..."
                                            class="max-h-40 flex-1 resize-none border-0 bg-transparent px-0 py-2 text-[16px] text-brand-secondary outline-none placeholder:text-slate-400 focus:ring-0 md:text-[15px]"
                                        >{{ old('body') }}</textarea>

                                        <input
                                            id="ticket-message-attachments"
                                            type="file"
                                            name="attachments[]"
                                            multiple
                                            accept=".jpg,.jpeg,.png,.webp"
                                            class="sr-only"
                                        >

                                        <button
                                            type="submit"
                                            class="inline-flex h-10 shrink-0 cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 text-sm font-semibold text-white transition hover:opacity-90 md:px-5 md:text-sm"
                                        >
                                            <span class="hidden md:inline">Enviar</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M10.3009 13.6949L20.102 3.89742M10.5795 14.1355L12.8019 18.5804C13.339 19.6545 13.6075 20.1916 13.9458 20.3356C14.2394 20.4606 14.575 20.4379 14.8492 20.2747C15.1651 20.0866 15.3591 19.5183 15.7472 18.3818L19.9463 6.08434C20.2845 5.09409 20.4535 4.59896 20.3378 4.27142C20.2371 3.98648 20.013 3.76234 19.7281 3.66167C19.4005 3.54595 18.9054 3.71502 17.9151 4.05315L5.61763 8.2523C4.48114 8.64037 3.91289 8.83441 3.72478 9.15032C3.56153 9.42447 3.53891 9.76007 3.66389 10.0536C3.80791 10.3919 4.34498 10.6605 5.41912 11.1975L9.86397 13.42C10.041 13.5085 10.1295 13.5527 10.2061 13.6118C10.2742 13.6643 10.3352 13.7253 10.3876 13.7933C10.4468 13.87 10.491 13.9585 10.5795 14.1355Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                @error('body')
                                    <p class="mt-2 px-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('attachments')
                                    <p class="mt-2 px-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('attachments.*')
                                    <p class="mt-2 px-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </form>
                        @else
                            <div class="mt-6 rounded-[1.75rem] border border-brand-secondary/10 bg-white px-4 py-4 text-sm text-brand-secondary/70">
                                No tienes permiso para responder a este ticket.
                            </div>
                        @endif
                    </article>
                </div>

                <aside class="flex max-h-[calc(100vh-14rem)] flex-col overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm lg:sticky lg:top-6">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-secondary/10 pb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Actualizaciones</p>
                            <h2 class="mt-1 text-xl font-bold tracking-tight text-brand-secondary">Log del ticket</h2>
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/40">
                            {{ $activityLogs->count() }} eventos
                        </span>
                    </div>

                    <div class="relative mt-5 flex-1 space-y-4 overflow-y-auto pl-4 pr-2 before:absolute before:left-2 before:top-1 before:bottom-1 before:w-px before:bg-gradient-to-b before:from-brand-primary/30 before:via-brand-secondary/10 before:to-transparent">
                        @forelse ($activityLogs as $activity)
                            @php
                                $eventPillClass = match ($activity->event) {
                                    'created' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                    'assigned' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    'comment_added' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                    'status_changed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    'priority_changed' => 'bg-orange-50 text-orange-700 ring-orange-200',
                                    'closed' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                };
                                $eventAccentClass = match ($activity->event) {
                                    'created' => 'bg-sky-500',
                                    'assigned' => 'bg-amber-500',
                                    'comment_added' => 'bg-violet-500',
                                    'status_changed' => 'bg-emerald-500',
                                    'priority_changed' => 'bg-orange-500',
                                    'closed' => 'bg-slate-500',
                                    default => 'bg-brand-primary',
                                };
                                $eventCardClass = match ($activity->event) {
                                    'created' => 'border-sky-200/70 bg-sky-50/70',
                                    'assigned' => 'border-amber-200/70 bg-amber-50/60',
                                    'comment_added' => 'border-violet-200/70 bg-violet-50/60',
                                    'status_changed' => 'border-emerald-200/70 bg-emerald-50/60',
                                    'priority_changed' => 'border-orange-200/70 bg-orange-50/60',
                                    'closed' => 'border-slate-200 bg-slate-50/70',
                                    default => 'border-brand-secondary/10 bg-slate-50/80',
                                };
                            @endphp
                            <article class="relative rounded-[1.25rem] border p-4 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.03)] {{ $eventCardClass }}">
                                <span class="absolute left-[-0.125rem] top-5 h-3 w-3 rounded-full border-4 border-white shadow-sm {{ $eventAccentClass }}"></span>
                                <div class="min-w-0 pl-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $eventPillClass }}">
                                            {{ $activity->event_label }}
                                        </span>
                                        <span class="text-xs text-brand-secondary/50">
                                            {{ $activity->created_at?->translatedFormat('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-brand-secondary">
                                        {{ $activity->title }}
                                    </p>
                                    <p class="mt-1 text-xs text-brand-secondary/60">
                                        Por {{ $activity->actor_name }}
                                    </p>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-brand-secondary/15 bg-slate-50 px-4 py-8 text-center text-sm text-brand-secondary/60">
                                Todavía no hay movimientos en este ticket.
                            </div>
                        @endforelse
                    </div>
                </aside>
            </section>
        </div>

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
                    <button type="button" @click="zoomOut()" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white" aria-label="Reducir zoom">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                        </svg>
                    </button>
                    <button type="button" @click="resetZoom()" class="inline-flex h-10 min-w-20 items-center justify-center rounded-full bg-white/90 px-3 text-sm font-semibold text-brand-secondary shadow-lg transition hover:bg-white" aria-label="Restablecer zoom">
                        <span x-text="`${imageScale.toFixed(2).replace(/\.00$/, '')}x`"></span>
                    </button>
                    <button type="button" @click="downloadImage()" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white" aria-label="Descargar imagen">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5">
                            <path d="M12.5535 16.5061C12.4114 16.6615 12.2106 16.75 12 16.75C11.7894 16.75 11.5886 16.6615 11.4465 16.5061L7.44648 12.1311C7.16698 11.8254 7.18822 11.351 7.49392 11.0715C7.79963 10.792 8.27402 10.8132 8.55352 11.1189L11.25 14.0682V3C11.25 2.58579 11.5858 2.25 12 2.25C12.4142 2.25 12.75 2.58579 12.75 3V14.0682L15.4465 11.1189C15.726 10.8132 16.2004 10.792 16.5061 11.0715C16.8118 11.351 16.833 11.8254 16.5535 12.1311L12.5535 16.5061Z" fill="#1C274C"/>
                            <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.2536 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="#1C274C"/>
                        </svg>
                    </button>
                    <button type="button" @click="zoomIn()" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white" aria-label="Aumentar zoom">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <p class="mt-4 text-center text-sm font-medium text-white/80" x-text="imageTitle"></p>
            </div>
        </div>
    </main>
@endsection
