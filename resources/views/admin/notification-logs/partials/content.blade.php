@php
    $hasActiveRange = filled($dateFrom) || filled($dateTo);
    $rangeLabel = match (true) {
        filled($dateFrom) && filled($dateTo) => 'Del ' . \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') . ' al ' . \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y'),
        filled($dateFrom) => 'Desde ' . \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y'),
        filled($dateTo) => 'Hasta ' . \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y'),
        default => 'Rango de fechas',
    };
@endphp

<section id="admin-logs-view" class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Historial</span>
                <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Logs de notificaciones</h1>
                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                    Aqui puedes revisar cada notificacion prioritaria enviada desde administracion con su fecha, hora y la persona que realizo la gestion.
                </p>
            </div>

            <a href="{{ route('admin.notification-logs.export', request()->only(['date_from', 'date_to', 'actor'])) }}"
                data-logs-export-link
                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                Descargar CSV
            </a>
        </div>

        <form method="GET" action="{{ route('admin.notification-logs.index') }}" data-logs-filter-form
            class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-4 shadow-inner shadow-white/60">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-stretch xl:justify-between">
                    <div class="grid flex-1 gap-3 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
                        <div class="grid gap-3">
                            <div class="relative rounded-2xl border border-brand-secondary/10 bg-white px-4 py-3">
                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">Gestor</div>
                                <div data-display-actor class="pointer-events-none flex min-h-[3.25rem] items-center pr-10 text-[0.95rem] font-semibold leading-snug text-brand-secondary">
                                    {{ optional($actors->firstWhere('id', $actorId))->name ?? 'Todos los gestores/admin' }}
                                </div>
                                <select name="actor"
                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer appearance-none rounded-2xl opacity-0 outline-none">
                                    <option value="">Todos los gestores/admin</option>
                                    @foreach ($actors as $actor)
                                        <option value="{{ $actor->id }}" @selected((string) ($actorId ?? '') === (string) $actor->id)>{{ $actor->name }}</option>
                                    @endforeach
                                </select>

                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <div class="grid h-full grid-rows-[auto_1fr] rounded-2xl border {{ $hasActiveRange ? 'border-brand-primary/25 bg-brand-primary/10' : 'border-brand-secondary/10 bg-white' }} px-4 py-3 transition">
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

                        <a href="{{ route('admin.notification-logs.index') }}" data-logs-reset
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
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Titulo</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Roles</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Destinatarios</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Gestionado por</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Enlace</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-secondary/10 bg-white">
                    @forelse ($logs as $log)
                        @php
                            $roleLabels = array_merge([
                                '__all_users__' => 'Todos los usuarios',
                            ], \App\Models\User::roleLabels());
                        @endphp
                        <tr class="align-top">
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <p class="font-semibold">{{ $log->created_at?->format('d/m/Y') }}</p>
                                <p class="mt-1 text-brand-secondary/65">{{ $log->created_at?->format('H:i:s') }}</p>
                            </td>
                            <td class="px-6 py-5 text-sm font-medium text-brand-secondary">
                                <div>{{ $log->title }}</div>
                                <div class="mt-1 max-w-xl text-xs text-brand-secondary/55">
                                    {{ $log->description }}
                                </div>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <div class="flex flex-wrap gap-2">
                                    @foreach (($log->target_roles ?? []) as $role)
                                        <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                                            {{ $roleLabels[$role] ?? $role }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    {{ $log->recipient_count }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                <p class="font-semibold">{{ $log->actor_name }}</p>
                                @if ($log->actor_email)
                                    <p class="mt-1 text-brand-secondary/65 break-all">{{ $log->actor_email }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm text-brand-secondary">
                                @if ($log->link_url)
                                    <a href="{{ $log->link_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-brand-primary">
                                        Abrir
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-brand-secondary/45">Sin enlace</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-brand-secondary/65">
                                Todavia no hay logs de notificaciones para los filtros seleccionados.
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
