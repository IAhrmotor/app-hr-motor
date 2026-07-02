@extends('layouts.app')

@section('content')
    @php
        $ticketCounts = collect($tickets)->countBy('status');
        $statusOrder = array_keys($ticketStatuses);
        $formatPriority = fn (string $priority): array => $ticketPriorities[$priority] ?? ['label' => $priority, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
        $formatStatus = fn (string $status): array => $ticketStatuses[$status] ?? ['label' => $status, 'badge' => 'bg-slate-100 text-slate-700 ring-slate-200'];
    @endphp

    <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
        <div class="space-y-6">
            <section
                class="relative overflow-hidden rounded-[2.25rem] border border-brand-secondary/10 bg-cover bg-center text-white shadow-sm"
                style="background-image: linear-gradient(135deg, rgba(15,23,42,0.72), rgba(15,23,42,0.42)), url('{{ $heroImageUrl }}');"
            >
                <div class="px-6 py-10 sm:px-8 sm:py-14">
                    <div class="max-w-3xl space-y-6">
                        <div class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white/90 ring-1 ring-white/15">
                            Portal de incidencias
                        </div>

                        <div class="space-y-3">
                            <h1 class="text-3xl font-bold tracking-tight sm:text-5xl">
                                Tus tickets de IT, en un solo sitio
                            </h1>
                            <p class="max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                                Consulta el estado de tus incidencias y abre una nueva solicitud cuando tengas un problema en cualquier plataforma.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('it-tickets.create') }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-brand-secondary shadow-sm transition hover:translate-y-[-1px]">
                                Crear incidencia
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 border-b border-brand-secondary/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Tus incidencias
                        </h2>
                        <p class="mt-1 text-sm text-brand-secondary/65">
                            Ordenadas por estado para que se vea primero lo que más necesita seguimiento.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($statusOrder as $status)
                            @php $meta = $ticketStatuses[$status]; @endphp
                            <button
                                type="button"
                                data-ticket-filter
                                data-status="{{ $status }}"
                                aria-pressed="false"
                                class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-xs font-semibold text-brand-secondary ring-1 ring-brand-secondary/10 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-primary/20"
                            >
                                <span class="h-2.5 w-2.5 rounded-full {{ str_contains($meta['badge'], 'sky') ? 'bg-sky-500' : (str_contains($meta['badge'], 'amber') ? 'bg-amber-500' : (str_contains($meta['badge'], 'violet') ? 'bg-violet-500' : 'bg-emerald-500')) }}"></span>
                                {{ $meta['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-y-3">
                        <thead>
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/45">
                                <th class="w-[8.5rem] px-3 py-2">Ticket</th>
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2">Título</th>
                                <th class="px-3 py-2">Prioridad</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Actualizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tickets as $ticket)
                                @php
                                    $statusMeta = $formatStatus($ticket->status);
                                    $priorityMeta = $formatPriority($ticket->priority);
                                @endphp
                                <tr data-ticket-row data-ticket-status="{{ $ticket->status }}" class="rounded-2xl bg-slate-50/80 text-sm text-brand-secondary shadow-[inset_0_0_0_1px_rgba(15,23,42,0.06)]">
                                    <td class="w-[8.5rem] rounded-l-2xl px-3 py-4 font-semibold">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="transition hover:text-brand-primary">
                                            {{ $ticket->number }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-2 font-medium">
                                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $ticket->ticketTool?->color ?? '#1d4ed8' }}"></span>
                                            <span>{{ $ticket->ticketTool?->name ?? $ticket->tool }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="font-medium">{{ $ticket->title }}</div>
                                        <div class="mt-1 line-clamp-2 text-xs leading-5 text-brand-secondary/60">
                                            {{ $ticket->description }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $priorityMeta['badge'] }}">
                                            {{ $priorityMeta['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusMeta['badge'] }}">
                                            {{ $statusMeta['label'] }}
                                        </span>
                                    </td>
                                    <td class="rounded-r-2xl px-3 py-4">
                                        <div class="font-medium">{{ $ticket->updated_at?->format('d/m/Y H:i') }}</div>
                                        <div class="mt-1 text-xs text-brand-secondary/55">
                                            Creado el {{ $ticket->created_at?->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="rounded-2xl border border-dashed border-brand-secondary/15 px-4 py-8 text-center text-sm text-brand-secondary/60">
                                        Todavía no hay incidencias para mostrar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script>
        (() => {
            const filterButtons = Array.from(document.querySelectorAll('[data-ticket-filter]'));
            const rows = Array.from(document.querySelectorAll('[data-ticket-row]'));
            const activeStatuses = new Set();
            const activeClassNames = ['bg-brand-primary', 'text-white', 'ring-brand-primary/20'];
            const inactiveClassNames = ['bg-slate-50', 'text-brand-secondary', 'ring-brand-secondary/10', 'hover:bg-slate-100'];

            const applyStyles = (button, active) => {
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                button.classList.remove(...activeClassNames, ...inactiveClassNames);

                if (active) {
                    button.classList.add(...activeClassNames);
                } else {
                    button.classList.add(...inactiveClassNames);
                }
            };

            const refreshRows = () => {
                const hasActiveFilters = activeStatuses.size > 0;

                rows.forEach((row) => {
                    const status = row.dataset.ticketStatus;
                    const showRow = !hasActiveFilters || activeStatuses.has(status);
                    row.classList.toggle('hidden', !showRow);
                });

                filterButtons.forEach((button) => {
                    applyStyles(button, activeStatuses.has(button.dataset.status));
                });
            };

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const status = button.dataset.status;

                    if (activeStatuses.has(status)) {
                        activeStatuses.delete(status);
                    } else {
                        activeStatuses.add(status);
                    }

                    refreshRows();
                });
            });

            refreshRows();
        })();
    </script>
@endsection
