@php
    $tickets = $section['tickets'];
    $selectedStatuses = $section['statuses'] ?? [];
    $selectedPriorities = $section['priorities'] ?? [];
    $searchValue = $section['search'] ?? '';
@endphp

<section
    class="rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6"
    data-ticket-section="{{ $sectionKey }}"
    data-ticket-search-fields="{{ $searchFields }}"
>
    <div class="flex flex-col gap-4 border-b border-brand-secondary/10 pb-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">{{ $title }}</h2>
                <p class="mt-1 text-sm text-brand-secondary/60">{{ $description }}</p>
            </div>

            <label class="relative block w-full sm:w-[22rem]">
                <span class="sr-only">Buscar tickets</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3m1.8-5.2a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                    </svg>
                </span>
                <input
                    type="search"
                    value="{{ $searchValue }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-2xl border border-brand-secondary/15 bg-white py-3 pl-11 pr-4 text-sm text-brand-secondary shadow-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/15"
                    data-ticket-search-input
                >
            </label>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Estado</p>
                <div class="flex flex-wrap gap-2" data-ticket-filter-group="status">
                    @foreach ($ticketStatuses as $statusKey => $statusMeta)
                        @php $isActive = in_array($statusKey, $selectedStatuses, true); @endphp
                        <button
                            type="button"
                            class="ticket-filter-pill inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $isActive ? 'border-brand-primary bg-brand-primary text-white' : 'border-brand-secondary/15 bg-white text-brand-secondary/70 hover:border-brand-primary hover:text-brand-primary' }}"
                            data-ticket-filter-value="{{ $statusKey }}"
                            data-ticket-filter-label="{{ $statusMeta['label'] }}"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        >
                            {{ $statusMeta['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">Prioridad</p>
                <div class="flex flex-wrap gap-2" data-ticket-filter-group="priority">
                    @foreach ($ticketPriorities as $priorityKey => $priorityMeta)
                        @php $isActive = in_array($priorityKey, $selectedPriorities, true); @endphp
                        <button
                            type="button"
                            class="ticket-filter-pill inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $isActive ? 'border-brand-primary bg-brand-primary text-white' : 'border-brand-secondary/15 bg-white text-brand-secondary/70 hover:border-brand-primary hover:text-brand-primary' }}"
                            data-ticket-filter-value="{{ $priorityKey }}"
                            data-ticket-filter-label="{{ $priorityMeta['label'] }}"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        >
                            {{ $priorityMeta['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-y-3">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                    <th class="w-[8.5rem] px-3 py-2">Ticket</th>
                    <th class="px-3 py-2">Solicitante</th>
                    <th class="px-3 py-2">Tipo</th>
                    <th class="px-3 py-2">Prioridad</th>
                    <th class="px-3 py-2">Estado</th>
                    @if ($isManaged)
                        <th class="px-3 py-2">Asignado a</th>
                        <th class="px-3 py-2">Acción</th>
                    @else
                        <th class="px-3 py-2">Actualizado</th>
                    @endif
                </tr>
            </thead>
            <tbody data-ticket-table-body>
                @forelse ($tickets as $ticket)
                    @php
                        $statusMeta = $ticketStatuses[$ticket->status] ?? ['label' => $ticket->status, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
                        $priorityMeta = $ticketPriorities[$ticket->priority] ?? ['label' => $ticket->priority, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
                        $isUnassigned = blank($ticket->assigned_to_user_id);
                        $reopenReason = $ticket->messages?->last()?->body ?? 'El usuario no ha dejado un motivo visible.';
                        $rowToneClass = match ($ticket->status) {
                            'reopen_requested' => 'bg-rose-50/90 shadow-[inset_0_0_0_1px_rgba(251,113,133,0.18)] hover:bg-rose-50/90',
                            'new' => $isUnassigned
                                ? 'bg-amber-50/90 shadow-[inset_0_0_0_1px_rgba(217,119,6,0.14)] hover:bg-amber-50/90'
                                : 'bg-slate-50/80 hover:bg-slate-50',
                            default => $isUnassigned
                                ? 'bg-amber-50/90 shadow-[inset_0_0_0_1px_rgba(217,119,6,0.14)] hover:bg-amber-50/90'
                                : 'bg-slate-50/80 hover:bg-slate-50',
                        };
                    @endphp
                    <tr
                        class="rounded-2xl text-sm text-brand-secondary shadow-[inset_0_0_0_1px_rgba(15,23,42,0.06)] transition {{ $rowToneClass }}"
                        data-ticket-row
                        data-ticket-number="{{ strtolower($ticket->number) }}"
                        data-ticket-requester="{{ strtolower(trim(($ticket->user?->name ?? '') . ' ' . ($ticket->user?->email ?? ''))) }}"
                        data-ticket-assignee="{{ strtolower(trim($ticket->assignedTo?->name ?? 'Sin asignar')) }}"
                        data-ticket-status="{{ $ticket->status }}"
                        data-ticket-priority="{{ $ticket->priority }}"
                    >
                        <td class="w-[8.5rem] rounded-l-2xl px-3 py-4 font-semibold">
                            <a href="{{ route('tickets.show', $ticket) }}" class="transition hover:text-brand-primary">
                                {{ $ticket->number }}
                            </a>
                        </td>
                        <td class="px-3 py-4">
                            <div class="font-medium">{{ $ticket->user?->name ?? 'Sin nombre' }}</div>
                        </td>
                        <td class="px-3 py-4">
                            <div class="flex items-center gap-2 font-medium">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $ticket->ticketTool?->color ?? '#1d4ed8' }}"></span>
                                <span>{{ $ticket->ticketTool?->name ?? $ticket->tool }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $priorityMeta['badge'] }}">{{ $priorityMeta['label'] }}</span>
                        </td>
                        <td class="px-3 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusMeta['badge'] }}">{{ $statusMeta['label'] }}</span>
                            @if ($ticket->status === 'reopen_requested')
                                <div x-data="{ reasonOpen: false }" class="mt-2">
                                    <button
                                        type="button"
                                        @click="reasonOpen = true"
                                        class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                    >
                                        Ver motivo
                                    </button>

                                    <div
                                        x-cloak
                                        x-show="reasonOpen"
                                        x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm"
                                        @click.self="reasonOpen = false"
                                    >
                                        <div class="w-full max-w-xl rounded-[2rem] bg-white p-6 shadow-2xl">
                                            <div class="flex items-start justify-between gap-4 border-b border-brand-secondary/10 pb-4">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">Motivo de reapertura</p>
                                                    <h3 class="mt-1 text-xl font-bold tracking-tight text-brand-secondary">Lo que pidió el usuario</h3>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="reasonOpen = false"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700"
                                                    aria-label="Cerrar motivo"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="mt-5 whitespace-pre-line break-words rounded-[1.35rem] border border-rose-100 bg-rose-50/60 px-4 py-4 text-sm leading-6 text-brand-secondary">
                                                {{ $reopenReason }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        @if ($isManaged)
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium">{{ $ticket->assignedTo?->name ?? 'Sin asignar' }}</div>
                            </td>
                            <td class="rounded-r-2xl px-3 py-4">
                                @if ($ticket->status === 'reopen_requested')
                                    <div x-data="{ closeReasonOpen: false, deleteOpen: false }" class="flex flex-col gap-3">
                                        <form method="POST" action="{{ route('tickets.reopen', $ticket) }}" class="flex flex-col gap-2">
                                            @csrf
                                            <select name="priority" class="w-full rounded-xl border border-brand-secondary/15 bg-white px-3 py-2 text-sm text-brand-secondary shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15">
                                                @foreach ($ticketPriorities as $priorityKey => $priorityMetaOption)
                                                    <option value="{{ $priorityKey }}" @selected($ticket->priority === $priorityKey)>
                                                        Prioridad: {{ $priorityMetaOption['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="assigned_to_user_id" class="w-full rounded-xl border border-brand-secondary/15 bg-white px-3 py-2 text-sm text-brand-secondary shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15">
                                                <option value="">Selecciona responsable</option>
                                                @foreach ($assignableUsers as $user)
                                                    <option value="{{ $user['id'] }}" @selected($ticket->assigned_to_user_id === $user['id'])>
                                                        {{ $user['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="flex items-center gap-2">
                                                <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500">
                                                    Reabrir
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="closeReasonOpen = true"
                                                    class="inline-flex flex-1 items-center justify-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                                >
                                                    Clausurar
                                                </button>
                                            </div>
                                        </form>

                                        <div
                                            x-cloak
                                            x-show="closeReasonOpen"
                                            x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm"
                                            @click.self="closeReasonOpen = false"
                                        >
                                            <div class="w-full max-w-xl rounded-[2rem] bg-white p-6 shadow-2xl">
                                                <div class="flex items-start justify-between gap-4 border-b border-brand-secondary/10 pb-4">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Clausurar definitivamente</p>
                                                        <h3 class="mt-1 text-xl font-bold tracking-tight text-brand-secondary">Escribe el motivo</h3>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="closeReasonOpen = false"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700"
                                                        aria-label="Cerrar modal"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <form method="POST" action="{{ route('tickets.permanently-close', $ticket) }}" class="mt-5 space-y-4">
                                                    @csrf
                                                    <div>
                                                        <label for="ticket-close-reason-{{ $ticket->id }}" class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/50">
                                                            Motivo de clausura definitiva
                                                        </label>
                                                        <textarea
                                                            id="ticket-close-reason-{{ $ticket->id }}"
                                                            name="reason"
                                                            rows="5"
                                                            required
                                                            placeholder="Explica por qué este ticket se clausura definitivamente..."
                                                            class="mt-2 w-full rounded-[1.1rem] border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary/30 focus:ring-2 focus:ring-brand-primary/15"
                                                        >{{ old('reason') }}</textarea>
                                                    </div>

                                                    <div class="flex flex-wrap justify-end gap-3">
                                                        <button
                                                            type="button"
                                                            @click="closeReasonOpen = false"
                                                            class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-brand-secondary transition hover:bg-slate-50"
                                                        >
                                                            Cancelar
                                                        </button>

                                                        <button
                                                            type="submit"
                                                            class="inline-flex h-11 items-center justify-center rounded-2xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                                                        >
                                                            Clausurar definitivamente
                                                        </button>
                                                    </div>

                                                    @error('reason')
                                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </form>
                                            </div>
                                        </div>

                                        <form method="POST" action="{{ route('tickets.destroy', $ticket) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                @click="deleteOpen = true"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 hover:text-rose-800"
                                                aria-label="Eliminar incidencia"
                                                title="Eliminar incidencia"
                                            >
                                                <svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4.5 w-4.5 shrink-0 text-current">
                                                    <path d="M4 6H20L18.4199 20.2209C18.3074 21.2337 17.4512 22 16.4321 22H7.56786C6.54876 22 5.69264 21.2337 5.5801 20.2209L4 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M7.34491 3.14716C7.67506 2.44685 8.37973 2 9.15396 2H14.846C15.6203 2 16.3249 2.44685 16.6551 3.14716L18 6H6L7.34491 3.14716Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M2 6H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M10 11V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M14 11V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Eliminar incidencia
                                            </button>
                                        </form>

                                        <div
                                            x-cloak
                                            x-show="deleteOpen"
                                            x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm"
                                            @click.self="deleteOpen = false"
                                        >
                                            <div class="w-full max-w-xl rounded-[2rem] bg-white p-6 shadow-2xl">
                                                <div class="flex items-start justify-between gap-4 border-b border-brand-secondary/10 pb-4">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">Eliminar incidencia</p>
                                                        <h3 class="mt-1 text-xl font-bold tracking-tight text-brand-secondary">¿Seguro que quieres eliminarla?</h3>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="deleteOpen = false"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700"
                                                        aria-label="Cerrar modal"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <p class="mt-5 text-sm leading-6 text-brand-secondary/70">
                                                    Esta acción eliminará la incidencia, sus mensajes y sus archivos adjuntos de forma permanente.
                                                </p>

                                                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="button"
                                                        @click="deleteOpen = false"
                                                        class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-brand-secondary transition hover:bg-slate-50"
                                                    >
                                                        Cancelar
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-11 items-center justify-center rounded-2xl bg-rose-600 px-5 text-sm font-semibold text-white transition hover:bg-rose-500"
                                                    >
                                                        Eliminar incidencia
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="flex flex-col gap-2">
                                        @csrf
                                        <select name="priority" class="w-full rounded-xl border border-brand-secondary/15 bg-white px-3 py-2 text-sm text-brand-secondary shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15">
                                            @foreach ($ticketPriorities as $priorityKey => $priorityMetaOption)
                                                <option value="{{ $priorityKey }}" @selected($ticket->priority === $priorityKey)>
                                                    Prioridad: {{ $priorityMetaOption['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="assigned_to_user_id" class="w-full rounded-xl border border-brand-secondary/15 bg-white px-3 py-2 text-sm text-brand-secondary shadow-sm focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15">
                                            <option value="">Selecciona responsable</option>
                                            @foreach ($assignableUsers as $user)
                                                <option value="{{ $user['id'] }}" @selected($ticket->assigned_to_user_id === $user['id'])>
                                                    {{ $user['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-secondary px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-primary">
                                            Asignar
                                        </button>
                                    </form>

                                    <div x-data="{ deleteOpen: false }" class="mt-2" @keydown.escape.window="deleteOpen = false">
                                    <form method="POST" action="{{ route('tickets.destroy', $ticket) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            @click="deleteOpen = true"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 hover:text-rose-800"
                                            aria-label="Eliminar incidencia"
                                            title="Eliminar incidencia"
                                        >
                                            <svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4.5 w-4.5 shrink-0 text-current">
                                                <path d="M4 6H20L18.4199 20.2209C18.3074 21.2337 17.4512 22 16.4321 22H7.56786C6.54876 22 5.69264 21.2337 5.5801 20.2209L4 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M7.34491 3.14716C7.67506 2.44685 8.37973 2 9.15396 2H14.846C15.6203 2 16.3249 2.44685 16.6551 3.14716L18 6H6L7.34491 3.14716Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M2 6H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10 11V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M14 11V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Eliminar incidencia
                                        </button>
                                    </form>

                                        <div
                                            x-cloak
                                            x-show="deleteOpen"
                                            x-transition.opacity
                                            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm"
                                            @click.self="deleteOpen = false"
                                        >
                                            <div class="w-full max-w-xl rounded-[2rem] bg-white p-6 shadow-2xl">
                                                <div class="flex items-start justify-between gap-4 border-b border-brand-secondary/10 pb-4">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">Eliminar incidencia</p>
                                                        <h3 class="mt-1 text-xl font-bold tracking-tight text-brand-secondary">¿Seguro que quieres eliminarla?</h3>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="deleteOpen = false"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700"
                                                        aria-label="Cerrar modal"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <p class="mt-5 text-sm leading-6 text-brand-secondary/70">
                                                    Esta acción eliminará la incidencia, sus mensajes y sus archivos adjuntos de forma permanente.
                                                </p>

                                                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="button"
                                                        @click="deleteOpen = false"
                                                        class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-brand-secondary transition hover:bg-slate-50"
                                                    >
                                                        Cancelar
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-11 items-center justify-center rounded-2xl bg-rose-600 px-5 text-sm font-semibold text-white transition hover:bg-rose-500"
                                                    >
                                                        Eliminar incidencia
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @else
                            <td class="rounded-r-2xl px-3 py-4">
                                <div class="font-medium">{{ $ticket->updated_at?->format('d/m/Y H:i') }}</div>
                                <div class="mt-1 text-xs text-brand-secondary/55">Creado el {{ $ticket->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr data-ticket-empty-row>
                        <td colspan="{{ $isManaged ? 7 : 6 }}" class="rounded-2xl border border-dashed border-brand-secondary/15 px-4 py-8 text-center text-sm text-brand-secondary/60">
                            @if ($isManaged)
                                Todavía no hay tickets para gestionar.
                            @else
                                Todavía no tienes tickets asignados.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="hidden rounded-2xl border border-dashed border-brand-secondary/15 px-4 py-8 text-center text-sm text-brand-secondary/60" data-ticket-no-results>
            No hay tickets que coincidan con los filtros actuales.
        </div>

        @if ($tickets->hasPages())
            <div class="mt-6" data-ticket-pagination>
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</section>
