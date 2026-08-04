@php
    $hasActiveRange = filled($dateFrom) || filled($dateTo);
    $rangeLabel = match (true) {
        filled($dateFrom) && filled($dateTo) => 'Del ' . \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') . ' al ' . \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y'),
        filled($dateFrom) => 'Desde ' . \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y'),
        filled($dateTo) => 'Hasta ' . \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y'),
        default => 'Rango de fechas',
    };

    $contentTypeLabel = match ($contentType ?? null) {
        \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE => 'Revista mensual',
        \App\Models\ContentActivityLog::CONTENT_TYPE_CONTACT => 'Contactos',
        \App\Models\ContentActivityLog::CONTENT_TYPE_BULLETIN => 'Tablón',
        default => 'Todo el contenido',
    };
@endphp

<section id="admin-logs-view" class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Historial</span>
                <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Logs de contenidos</h1>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Aqui puedes revisar los cambios de la revista mensual, los contactos y el tablón en un único historial.
                </p>
            </div>

            <a href="{{ route('admin.content-logs.export', request()->only(['content_type', 'action', 'date_from', 'date_to', 'actor'])) }}"
                data-logs-export-link
                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                Descargar CSV
            </a>
        </div>

        <form method="GET" action="{{ route('admin.content-logs.index') }}" data-logs-filter-form
            class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-4 shadow-inner shadow-white/60">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-stretch xl:justify-between">
                    <div class="grid flex-1 gap-3 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
                        <div class="grid gap-3">
                            <div class="relative rounded-2xl border border-brand-secondary/10 bg-white px-4 py-3">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Tipo</div>
                                <div data-display-content-type class="pointer-events-none pr-10 text-base font-semibold text-brand-secondary">
                                    {{ match ($contentType) {
                                        \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE => 'Revista mensual',
                                        \App\Models\ContentActivityLog::CONTENT_TYPE_CONTACT => 'Contactos',
                                        \App\Models\ContentActivityLog::CONTENT_TYPE_BULLETIN => 'Tablón',
                                        default => 'Todo el contenido',
                                    } }}
                                </div>
                                <select name="content_type"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer appearance-none rounded-2xl opacity-0 outline-none">
                                    <option value="">Todo el contenido</option>
                                    <option value="{{ \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE }}" @selected(($contentType ?? null) === \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE)>Revista mensual</option>
                                    <option value="{{ \App\Models\ContentActivityLog::CONTENT_TYPE_CONTACT }}" @selected(($contentType ?? null) === \App\Models\ContentActivityLog::CONTENT_TYPE_CONTACT)>Contactos</option>
                                    <option value="{{ \App\Models\ContentActivityLog::CONTENT_TYPE_BULLETIN }}" @selected(($contentType ?? null) === \App\Models\ContentActivityLog::CONTENT_TYPE_BULLETIN)>Tablón</option>
                                </select>

                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </div>

                            <div class="relative rounded-2xl border border-brand-secondary/10 bg-white px-4 py-3">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Accion</div>
                                <div data-display-action class="pointer-events-none pr-10 text-base font-semibold text-brand-secondary">
                                    {{ match ($action) {
                                        \App\Models\ContentActivityLog::ACTION_CREATED => 'Altas',
                                        \App\Models\ContentActivityLog::ACTION_UPDATED => 'Ediciones',
                                        \App\Models\ContentActivityLog::ACTION_DELETED => 'Eliminaciones',
                                        default => 'Todas las acciones',
                                    } }}
                                </div>
                                <select name="action"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer appearance-none rounded-2xl opacity-0 outline-none">
                                    <option value="">Todas las acciones</option>
                                    <option value="{{ \App\Models\ContentActivityLog::ACTION_CREATED }}" @selected(($action ?? null) === \App\Models\ContentActivityLog::ACTION_CREATED)>Altas</option>
                                    <option value="{{ \App\Models\ContentActivityLog::ACTION_UPDATED }}" @selected(($action ?? null) === \App\Models\ContentActivityLog::ACTION_UPDATED)>Ediciones</option>
                                    <option value="{{ \App\Models\ContentActivityLog::ACTION_DELETED }}" @selected(($action ?? null) === \App\Models\ContentActivityLog::ACTION_DELETED)>Eliminaciones</option>
                                </select>

                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <div class="relative rounded-2xl border border-brand-secondary/10 bg-white px-4 py-3">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Gestor</div>
                                <div data-display-actor class="pointer-events-none pr-10 text-base font-semibold text-brand-secondary">
                                    {{ optional($actors->firstWhere('id', $actorId))->name ?? 'Todos los gestores/admin' }}
                                </div>
                                <select name="actor"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer appearance-none rounded-2xl opacity-0 outline-none">
                                    <option value="">Todos los gestores/admin</option>
                                    @foreach ($actors as $actor)
                                        <option value="{{ $actor->id }}" @selected((string) $actorId === (string) $actor->id)>{{ $actor->name }}</option>
                                    @endforeach
                                </select>

                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </div>

                            <div class="grid grid-rows-[auto_1fr] rounded-2xl border {{ $hasActiveRange ? 'border-brand-primary/25 bg-brand-primary/10' : 'border-brand-secondary/10 bg-white' }} px-4 py-3 transition">
                                <div data-display-range-label class="text-center text-[11px] font-semibold uppercase tracking-[0.18em] {{ $hasActiveRange ? 'text-brand-primary' : 'text-brand-secondary/45' }}">
                                    {{ $rangeLabel }}
                                </div>

                                <div class="mt-2 grid h-full gap-2 sm:grid-cols-2">
                                    <button type="button" data-date-trigger="from"
                                        class="relative inline-flex h-full min-h-0 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold outline-none transition focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/20 {{ filled($dateFrom) ? 'border-brand-primary bg-white text-brand-primary shadow-sm' : 'border-transparent bg-transparent text-brand-secondary hover:bg-brand-secondary/5' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v2.25m7.5-2.25v2.25M3.75 8.25h16.5M4.5 5.25h15A2.25 2.25 0 0121.75 7.5v11.25A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V7.5A2.25 2.25 0 014.5 5.25z" />
                                        </svg>
                                        <span data-display-date-from>{{ filled($dateFrom) ? \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') : 'Desde' }}</span>
                                        <input type="date" name="date_from" value="{{ $dateFrom }}" data-date-input="from"
                                            class="pointer-events-none absolute inset-0 opacity-0" tabindex="-1">
                                    </button>

                                    <button type="button" data-date-trigger="to"
                                        class="relative inline-flex h-full min-h-0 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold outline-none transition focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/20 {{ filled($dateTo) ? 'border-brand-primary bg-white text-brand-primary shadow-sm' : 'border-transparent bg-transparent text-brand-secondary hover:bg-brand-secondary/5' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v2.25m7.5-2.25v2.25M3.75 8.25h16.5M4.5 5.25h15A2.25 2.25 0 0121.75 7.5v11.25A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V7.5A2.25 2.25 0 014.5 5.25z" />
                                        </svg>
                                        <span data-display-date-to>{{ filled($dateTo) ? \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') : 'Hasta' }}</span>
                                        <input type="date" name="date_to" value="{{ $dateTo }}" data-date-input="to"
                                            class="pointer-events-none absolute inset-0 opacity-0" tabindex="-1">
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 xl:min-w-[12rem] xl:grid-cols-1">
                        <button type="submit" data-filter-submit
                            class="flex min-h-[4.25rem] cursor-pointer flex-col justify-between rounded-2xl bg-brand-primary px-5 py-3 text-left text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">Aplicar</span>
                            <span class="text-lg font-semibold">Filtrar</span>
                        </button>

                        <a href="{{ route('admin.content-logs.index') }}" data-logs-reset
                            class="flex min-h-[4.25rem] cursor-pointer flex-col justify-between rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-left text-brand-secondary transition hover:bg-brand-secondary/5">
                            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Reset</span>
                            <span class="text-lg font-semibold">Limpiar</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-8 overflow-hidden rounded-3xl border border-brand-secondary/10">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-brand-secondary/10">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Fecha y hora</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Tipo</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Accion</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Gestionado por</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Elemento afectado</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Detalle</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-brand-secondary/10 bg-white">
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <p class="font-semibold">{{ $log->created_at?->format('d/m/Y') }}</p>
                                <p class="mt-1 text-brand-secondary/65">{{ $log->created_at?->format('H:i:s') }}</p>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                    {{ $log->content_type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                    {{ $log->action_label }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <p class="font-semibold">{{ $log->actor_name }}</p>
                                <p class="mt-1 text-brand-secondary/65">{{ $log->actor_email }}</p>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <p class="font-semibold">{{ $log->target_name }}</p>
                                @if ($log->target_reference)
                                    <p class="mt-1 text-brand-secondary/65 break-all">{{ $log->target_reference }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary/75">
                                @if (filled($log->changes))
                                    <ul class="space-y-2">
                                        @foreach ($log->changes as $field => $change)
                                            <li>
                                                <span class="font-semibold text-brand-secondary">{{ $field }}:</span>
                                                {{ $change['from'] ?? 'vacio' }} -> {{ $change['to'] ?? 'vacio' }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-brand-secondary/60">Sin cambios adicionales registrados.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-brand-secondary/65">
                                Todavia no hay logs de contenidos para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logs->hasPages())
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif
</section>
