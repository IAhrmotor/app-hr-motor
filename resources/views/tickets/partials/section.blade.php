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
                    @endphp
                    <tr
                        class="rounded-2xl text-sm text-brand-secondary shadow-[inset_0_0_0_1px_rgba(15,23,42,0.06)] transition {{ $isUnassigned ? 'bg-amber-50/90 shadow-[inset_0_0_0_1px_rgba(217,119,6,0.14)] hover:bg-amber-50/90' : 'bg-slate-50/80 hover:bg-slate-50' }}"
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
                        </td>
                        @if ($isManaged)
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium">{{ $ticket->assignedTo?->name ?? 'Sin asignar' }}</div>
                            </td>
                            <td class="rounded-r-2xl px-3 py-4">
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
                                                {{ $user['name'] }} - {{ $user['email'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-secondary px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-primary">
                                        Asignar
                                    </button>
                                </form>
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
