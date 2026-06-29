@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10">
        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            @if ($missingTable)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                    La tabla de logs de zonas todavía no existe en esta base de datos. Ejecuta la migración para empezar a registrar actividad.
                </div>
            @endif

            <div class="flex flex-col gap-6">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Administración</span>
                    <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Logs de zonas</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Consulta el historial de altas, ediciones y eliminaciones de zonas, junto con las delegaciones afectadas.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <p class="text-sm text-brand-secondary/70">Usa los filtros para acotar el historial y descarga el CSV cuando lo necesites.</p>
                    <a href="{{ route('admin.zone-logs.export', request()->only(['action', 'date_from', 'date_to', 'actor'])) }}"
                        class="inline-flex items-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        Descargar CSV
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.zone-logs.index') }}" class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label for="action" class="block text-sm font-semibold text-brand-secondary">Acción</label>
                            <select id="action" name="action" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                                <option value="">Todas</option>
                                <option value="created" @selected($action === 'created')>Alta</option>
                                <option value="updated" @selected($action === 'updated')>Edición</option>
                                <option value="deleted" @selected($action === 'deleted')>Eliminación</option>
                            </select>
                        </div>

                        <div>
                            <label for="actor" class="block text-sm font-semibold text-brand-secondary">Gestor</label>
                            <select id="actor" name="actor" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                                <option value="">Todos</option>
                                @foreach ($actors as $actor)
                                    <option value="{{ $actor->id }}" @selected((string) $actorId === (string) $actor->id)>{{ $actor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="date_from" class="block text-sm font-semibold text-brand-secondary">Desde</label>
                            <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                        </div>

                        <div>
                            <label for="date_to" class="block text-sm font-semibold text-brand-secondary">Hasta</label>
                            <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                            Filtrar
                        </button>

                        @if ($action || $dateFrom || $dateTo || $actorId)
                            <a href="{{ route('admin.zone-logs.index') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 bg-white px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>

                <div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-brand-secondary/10">
                            <thead class="bg-brand-secondary/5">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Fecha</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acción</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Gestor</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Zona</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Delegaciones</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Cambios</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                                @forelse ($logs as $log)
                                    <tr class="align-top">
                                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                            <p class="font-semibold">{{ $log->created_at?->format('d/m/Y') }}</p>
                                            <p class="mt-1 text-brand-secondary/65">{{ $log->created_at?->format('H:i:s') }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $log->action === 'created' ? 'bg-emerald-100 text-emerald-700' : ($log->action === 'deleted' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                                {{ $log->action_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                            <p class="font-semibold">{{ $log->actor_name }}</p>
                                            <p class="mt-1 text-brand-secondary/65">{{ $log->actor_email }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                            <p class="font-semibold">{{ $log->target_name }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                            {{ filled($log->target_dealerships) ? implode(' | ', $log->target_dealerships) : 'Sin delegaciones' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                            @if (filled($log->changes))
                                                <div class="space-y-2">
                                                    @foreach ($log->changes as $field => $change)
                                                        <div class="rounded-xl border border-brand-secondary/10 bg-slate-50 px-3 py-2">
                                                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/60">{{ $field }}</p>
                                                            <p class="mt-1 text-sm leading-6">{{ $change['from'] ?? 'vacío' }} → {{ $change['to'] ?? 'vacío' }}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-brand-secondary/60">Sin cambios detallados</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-brand-secondary/70">Todavía no hay logs de zonas para los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($logs->hasPages())
                    <div>
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection
