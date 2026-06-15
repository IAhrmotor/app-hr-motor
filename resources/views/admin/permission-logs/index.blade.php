@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        @if ($missingTable ?? false)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                La tabla de logs de permisos todavía no existe en esta base de datos. Ejecuta la migración para empezar a registrar actividad.
            </div>
        @endif

        <div id="admin-logs-container">
            <section id="admin-logs-view" class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-3xl">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Auditoría</span>
                        <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Logs de permisos</h1>
                        <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                            Revisa quién ha creado grupos, movido miembros o cambiado accesos a herramientas de administración.
                        </p>
                    </div>

                    <a href="{{ route('admin.permission-logs.export', request()->only(['action', 'date_from', 'date_to', 'actor'])) }}"
                        class="inline-flex items-center justify-center rounded-full bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        Descargar CSV
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.permission-logs.index') }}" class="mt-6 grid gap-4 rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm lg:grid-cols-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45" for="action">Acción</label>
                        <select id="action" name="action" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary">
                            <option value="">Todas</option>
                            @foreach ([
                                'group_created' => 'Grupo creado',
                                'group_updated' => 'Grupo actualizado',
                                'group_deleted' => 'Grupo eliminado',
                                'permission_synced' => 'Permisos sincronizados',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected($action === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45" for="actor">Administrador</label>
                        <select id="actor" name="actor" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary">
                            <option value="">Todos</option>
                            @foreach ($actors as $actor)
                                <option value="{{ $actor->id }}" @selected((int) $actorId === (int) $actor->id)>{{ $actor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45" for="date_from">Desde</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/45" for="date_to">Hasta</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary">
                    </div>

                    <div class="lg:col-span-4 flex gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.permission-logs.index') }}" class="inline-flex items-center justify-center rounded-full border border-brand-secondary/15 bg-white px-4 py-2.5 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Limpiar
                        </a>
                    </div>
                </form>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-brand-secondary/10 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.16em] text-brand-secondary/45">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Acción</th>
                                <th class="px-4 py-3">Administrador</th>
                                <th class="px-4 py-3">Objetivo</th>
                                <th class="px-4 py-3">Permiso</th>
                                <th class="px-4 py-3">Cambios</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logs as $log)
                                @php
                                    $changesSummary = collect($log->changes ?? [])
                                        ->map(function ($change, $field) {
                                            $from = is_array($change) ? ($change['from'] ?? null) : null;
                                            $to = is_array($change) ? ($change['to'] ?? null) : null;

                                            if (is_array($to)) {
                                                $to = implode(', ', array_map('strval', $to));
                                            }

                                            return $field . ': ' . ($from ?? 'vacío') . ' -> ' . ($to ?? 'vacío');
                                        })
                                        ->implode(' | ');
                                @endphp
                                <tr>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-semibold text-brand-secondary">{{ $log->created_at?->format('d/m/Y') }}</p>
                                        <p class="mt-1 text-xs text-brand-secondary/60">{{ $log->created_at?->format('H:i:s') }}</p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-semibold text-brand-secondary">{{ $log->action_label }}</p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.14em] text-brand-secondary/45">{{ $log->result }}</p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-semibold text-brand-secondary">{{ $log->actor_name }}</p>
                                        <p class="mt-1 text-xs text-brand-secondary/60">{{ $log->actor_email }}</p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-semibold text-brand-secondary">{{ $log->target_name ?? 'Sin objetivo' }}</p>
                                        <p class="mt-1 text-xs text-brand-secondary/60">{{ $log->target_type ?? 'Sin tipo' }}</p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-semibold text-brand-secondary">{{ $log->permission_key ?: 'N/A' }}</p>
                                        <p class="mt-1 text-xs text-brand-secondary/60">{{ $log->scope ?: 'Global' }}</p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="text-sm text-brand-secondary/75">{{ blank($changesSummary) ? 'Sin cambios detallados' : $changesSummary }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                        Todavía no hay logs de permisos para los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="mt-6">
                        {{ $logs->links() }}
                    </div>
                @endif
            </section>
        </div>
    </main>
@endsection
