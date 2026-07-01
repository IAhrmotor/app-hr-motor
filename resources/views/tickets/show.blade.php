@extends('layouts.app')

@section('content')
    @php
        $priorityMeta = $ticketPriorities[$ticket->priority] ?? ['label' => $ticket->priority, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
        $statusMeta = $ticketStatuses[$ticket->status] ?? ['label' => $ticket->status, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
        $screenshots = collect($ticket->screenshots ?? []);
        $messages = $ticket->messages ?? collect();
        $isVisibleItRole = app_visible_role(auth()->user()) === \App\Models\User::ROLE_INFORMATION_TECHNOLOGY;
        $isClosed = $ticket->status === 'closed';
    @endphp

    <main
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="mx-auto w-full max-w-6xl flex-1 px-6 py-8 lg:px-8"
    >
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
                <div
                    class="relative bg-cover bg-no-repeat px-6 py-8 sm:px-8 sm:py-10"
                    style="background-image: url('{{ asset('images/hero/hero-interior-ticket.webp') }}'); background-position: center 68%;"
                >
                    <div class="absolute inset-0 bg-slate-950/70"></div>
                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-3">
                            <div class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm">
                                Detalle del ticket
                            </div>
                            <div class="space-y-2">
                                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                                    {{ $ticket->number }}
                                </h1>
                                <p class="max-w-3xl text-sm leading-6 text-white/80 sm:text-base">
                                    Aquí puedes ver un resumen de la información operativa del ticket y todo lo que el usuario ha indicado en esta incidencia.
                                </p>
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
                </div>
            </section>

            <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-brand-secondary/10 pb-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-1">
                        <h2 class="text-xl font-bold tracking-tight text-brand-secondary">Resumen rápido</h2>
                        <p class="text-sm text-brand-secondary/60">Aquí se muestra un resumen de la información operativa del ticket.</p>
                    </div>

                    @if ($isVisibleItRole)
                        <a href="{{ $backUrl }}"
                            class="inline-flex items-center justify-center rounded-full border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary shadow-sm transition hover:-translate-y-0.5 hover:border-brand-primary hover:text-brand-primary">
                            Volver
                        </a>
                    @endif
                </div>

                <dl class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Herramienta</dt>
                        <dd class="mt-2 text-sm font-medium text-brand-secondary">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $ticket->ticketTool?->color ?? '#1d4ed8' }}"></span>
                                <span>{{ $ticket->ticketTool?->name ?? $ticket->tool }}</span>
                            </span>
                        </dd>
                    </div>

                    <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Solicitante</dt>
                        <dd class="mt-2 text-sm font-medium text-brand-secondary">
                            {{ $ticket->user?->name ?? 'Sin nombre' }}
                            <div class="mt-1 text-xs font-normal text-brand-secondary/55">{{ $ticket->user?->email }}</div>
                        </dd>
                    </div>

                    <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Asignado a</dt>
                        <dd class="mt-2 text-sm font-medium text-brand-secondary">
                            {{ $ticket->assignedTo?->name ?? 'Sin asignar' }}
                        </dd>
                    </div>

                    <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Actualizado</dt>
                        <dd class="mt-2 text-sm font-medium text-brand-secondary">
                            {{ $ticket->updated_at?->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-6">
                <article class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-secondary/10 pb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Incidencia</p>
                            <h2 class="mt-1 text-xl font-bold tracking-tight text-brand-secondary">Resumen inicial del ticket</h2>
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/40">
                            {{ $ticket->created_at?->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="mt-5 space-y-6">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Título</p>
                                <p class="mt-2 text-lg font-semibold text-brand-secondary">{{ $ticket->title }}</p>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Descripción</p>
                                <div class="mt-2 rounded-2xl bg-slate-50 px-4 py-4 text-sm leading-6 text-brand-secondary/80">
                                    {!! nl2br(e($ticket->description)) !!}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3 border-b border-brand-secondary/10 pb-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Capturas</h3>
                                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/40">
                                    {{ $screenshots->count() }} archivos
                                </span>
                            </div>

                            <div class="mt-5">
                                @if ($screenshots->isNotEmpty())
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        @foreach ($screenshots as $screenshot)
                                            @php
                                                $screenshotPath = data_get($screenshot, 'path');
                                                $screenshotName = data_get($screenshot, 'name', basename((string) $screenshotPath));
                                            @endphp
                                            @if ($screenshotPath)
                                                <button
                                                    type="button"
                                                    @click="openImage({ src: @js(\Illuminate\Support\Facades\Storage::disk('public')->url($screenshotPath)), alt: @js($screenshotName), title: @js($screenshotName) })"
                                                    class="group cursor-zoom-in overflow-hidden rounded-2xl border border-brand-secondary/10 bg-slate-50 text-left transition hover:-translate-y-0.5 hover:shadow-md"
                                                    aria-label="Ver captura {{ $screenshotName }}"
                                                >
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($screenshotPath) }}"
                                                        alt="{{ $screenshotName }}"
                                                        class="h-52 w-full object-cover">
                                                    <div class="border-t border-brand-secondary/10 px-4 py-3 text-xs font-medium text-brand-secondary/60">
                                                        {{ $screenshotName }}
                                                    </div>
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-2xl border border-dashed border-brand-secondary/15 bg-slate-50 px-4 py-8 text-center text-sm text-brand-secondary/60">
                                        El usuario no adjuntó capturas en esta incidencia.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-secondary/10 pb-4">
                        <h2 class="text-xl font-bold tracking-tight text-brand-secondary">Hilo de conversación</h2>
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/40">
                            {{ $messages->count() }} mensajes
                        </span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($messages as $message)
                            @php
                                $messageAttachments = collect($message->attachments ?? []);
                                $isTicketOwnerMessage = $message->user_id === $ticket->user_id;
                                $messageShellClass = $isTicketOwnerMessage
                                    ? 'border-brand-primary/15 bg-brand-primary/5'
                                    : 'border-brand-secondary/10 bg-slate-50';
                            @endphp
                            <article class="rounded-[1.5rem] border {{ $messageShellClass }} p-5">
                                <div class="flex items-start gap-4">
                                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-2xl ring-1 ring-brand-secondary/10">
                                        <img src="{{ $message->author?->avatar_url }}" alt="Avatar de {{ $message->author?->name ?? 'Usuario' }}" class="h-full w-full object-cover">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="font-semibold text-brand-secondary">{{ $message->author?->name ?? 'Usuario' }}</span>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $isTicketOwnerMessage ? 'bg-brand-primary/10 text-brand-primary' : 'bg-brand-secondary/5 text-brand-secondary/65' }}">
                                                {{ $isTicketOwnerMessage ? 'Incidencia inicial' : 'Respuesta' }}
                                            </span>
                                            <span class="text-xs text-brand-secondary/50">{{ $message->created_at?->translatedFormat('d/m/Y H:i') }}</span>
                                        </div>

                                        @if (filled($message->body))
                                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-brand-secondary/80">{{ $message->body }}</p>
                                        @endif

                                        @if ($messageAttachments->isNotEmpty())
                                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($messageAttachments as $attachment)
                                                    @php
                                                        $attachmentPath = data_get($attachment, 'path');
                                                        $attachmentName = data_get($attachment, 'name', basename((string) $attachmentPath));
                                                    @endphp
                                                    @if ($attachmentPath)
                                                        <button
                                                            type="button"
                                                            @click="openImage({ src: @js(\Illuminate\Support\Facades\Storage::disk('public')->url($attachmentPath)), alt: @js($attachmentName), title: @js($attachmentName) })"
                                                            class="group relative cursor-zoom-in overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white text-left shadow-sm transition hover:-translate-y-1 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                                                            aria-label="Ver imagen {{ $attachmentName }}"
                                                        >
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachmentPath) }}" alt="{{ $attachmentName }}"
                                                                class="h-44 w-full object-cover transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                                                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/30 group-hover:opacity-100">
                                                                Ver
                                                            </span>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-brand-secondary/15 bg-slate-50 px-4 py-8 text-center text-sm text-brand-secondary/60">
                                Todavía no hay mensajes en este ticket.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6 rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50/80 p-5">
                        <h3 class="text-lg font-bold tracking-tight text-brand-secondary">Responder al ticket</h3>
                        <p class="mt-1 text-sm text-brand-secondary/70">
                            El creador, la persona asignada y quien tenga permiso para gestionar tickets pueden contestar siempre.
                        </p>

                        @if ($isClosed)
                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-brand-secondary/70">
                                Este ticket está cerrado y ya no admite nuevas respuestas.
                            </div>
                        @elseif ($canReplyToTicket)
                            <form method="POST" action="{{ route('tickets.messages.store', $ticket) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                                @csrf
                                <textarea name="body" rows="5" placeholder="Escribe tu respuesta..."
                                    class="w-full rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div>
                                    <label for="ticket-message-attachments" class="mb-2 block text-sm font-semibold text-brand-secondary">Imágenes adjuntas</label>
                                    <input id="ticket-message-attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp"
                                        class="block w-full cursor-pointer rounded-2xl border border-dashed border-brand-secondary/20 bg-white px-4 py-4 text-sm text-brand-secondary/75 file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:font-semibold file:text-white hover:file:opacity-90">
                                    <p class="mt-2 text-sm text-brand-secondary/60">Puedes adjuntar hasta 4 imágenes en JPG, PNG o WEBP.</p>
                                    @error('attachments')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('attachments.*')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    <button
                                        type="submit"
                                        class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:border-brand-primary hover:text-brand-primary"
                                    >
                                        Responder
                                    </button>
                                    @if ($canCloseTicket)
                                        <button
                                            type="submit"
                                            name="close_ticket"
                                            value="1"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                                        >
                                            Responder y cerrar incidencia
                                        </button>
                                    @endif
                                </div>
                            </form>
                        @else
                            <div class="mt-4 rounded-2xl border border-brand-secondary/10 bg-white px-4 py-4 text-sm text-brand-secondary/70">
                                No tienes permiso para responder a este ticket.
                            </div>
                        @endif
                    </div>
                </article>
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
