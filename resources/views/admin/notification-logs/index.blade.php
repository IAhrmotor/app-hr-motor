@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">
                    Auditoría
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-brand-secondary md:text-3xl">
                    Logs de notificaciones
                </h1>

                <p class="mt-2 text-sm text-brand-secondary/70">
                    Aquí puedes revisar cada notificación prioritaria enviada desde administración.
                </p>
            </div>

            @if ($missingTable ?? false)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                    La tabla de logs de notificaciones todavía no existe en esta base de datos. Ejecuta la migración para empezar a registrar actividad.
                </div>
            @endif

            <form method="GET" action="{{ route('admin.notification-logs.index') }}" class="grid gap-4 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-5 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Gestor</label>
                    <select name="actor" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        <option value="" @selected(blank($actorId ?? null))>Todos</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" @selected((string) ($actorId ?? '') === (string) $actor->id)>{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Desde</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Hasta</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                </div>

                <div class="flex items-end gap-3 lg:col-span-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                        Filtrar
                    </button>
                    <a href="{{ route('admin.notification-logs.index') }}" class="inline-flex items-center justify-center rounded-xl border border-brand-secondary/15 px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Limpiar
                    </a>
                    <a href="{{ route('admin.notification-logs.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-xl border border-brand-secondary/15 px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Exportar CSV
                    </a>
                    <div class="ml-auto text-sm text-brand-secondary/60">
                        Mostrando historial de notificaciones enviadas
                    </div>
                </div>
            </form>

            <div class="mt-8 overflow-hidden rounded-3xl border border-brand-secondary/10">
                <table class="min-w-full divide-y divide-brand-secondary/10">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Título</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Roles</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Destinatarios</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Gestor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Enlace</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-secondary/10 bg-white">
                        @forelse ($logs as $log)
                            @php
                                $roleLabels = \App\Models\User::roleLabels();
                            @endphp
                            <tr>
                                <td class="px-4 py-4 text-sm text-brand-secondary">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-brand-secondary">
                                    <div>{{ $log->title }}</div>
                                    <div class="mt-1 max-w-xl text-xs text-brand-secondary/55">
                                        {{ $log->description }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach (($log->target_roles ?? []) as $role)
                                            <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                                                {{ $roleLabels[$role] ?? $role }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        {{ $log->recipient_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
                                    <div class="font-medium">{{ $log->actor_name }}</div>
                                    @if ($log->actor_email)
                                        <div class="mt-1 text-xs text-brand-secondary/55 break-all">{{ $log->actor_email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
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
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-brand-secondary/60">
                                    No hay registros para estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $logs->links() }}
            </div>
        </section>
    </main>
@endsection
