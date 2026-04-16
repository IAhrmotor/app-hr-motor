@extends('layouts.app')

@section('content')
    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-primary">
                    Auditoría
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-brand-secondary md:text-3xl">
                    Logs de contenidos
                </h1>

                <p class="mt-2 text-sm text-brand-secondary/70">
                    Aquí puedes revisar los cambios sobre la revista mensual y los tags del foro en un único historial.
                </p>
            </div>

            @if ($missingTable ?? false)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                    La tabla de logs de contenidos todavía no existe en esta base de datos. Ejecuta la migración para empezar a registrar actividad.
                </div>
            @endif

            @php
                $contentTypeLabel = match ($contentType ?? null) {
                    \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE => 'Revista mensual',
                    \App\Models\ContentActivityLog::CONTENT_TYPE_FORUM_TAG => 'Tag del foro',
                    default => 'Todo el contenido',
                };
            @endphp

            <form method="GET" action="{{ route('admin.content-logs.index') }}" class="grid gap-4 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-5 lg:grid-cols-5">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Tipo</label>
                    <select name="content_type" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        <option value="" @selected(blank($contentType ?? null))>Todo el contenido</option>
                        <option value="{{ \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE }}" @selected(($contentType ?? null) === \App\Models\ContentActivityLog::CONTENT_TYPE_MAGAZINE)>Revista mensual</option>
                        <option value="{{ \App\Models\ContentActivityLog::CONTENT_TYPE_FORUM_TAG }}" @selected(($contentType ?? null) === \App\Models\ContentActivityLog::CONTENT_TYPE_FORUM_TAG)>Tags del foro</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Acción</label>
                    <select name="action" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-brand-secondary outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        <option value="" @selected(blank($action ?? null))>Todas</option>
                        <option value="{{ \App\Models\ContentActivityLog::ACTION_CREATED }}" @selected(($action ?? null) === \App\Models\ContentActivityLog::ACTION_CREATED)>Alta</option>
                        <option value="{{ \App\Models\ContentActivityLog::ACTION_UPDATED }}" @selected(($action ?? null) === \App\Models\ContentActivityLog::ACTION_UPDATED)>Edición</option>
                        <option value="{{ \App\Models\ContentActivityLog::ACTION_DELETED }}" @selected(($action ?? null) === \App\Models\ContentActivityLog::ACTION_DELETED)>Eliminación</option>
                    </select>
                </div>

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

                <div class="flex items-end gap-3 lg:col-span-5">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                        Filtrar
                    </button>
                    <a href="{{ route('admin.content-logs.index') }}" class="inline-flex items-center justify-center rounded-xl border border-brand-secondary/15 px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Limpiar
                    </a>
                    <a href="{{ route('admin.content-logs.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-xl border border-brand-secondary/15 px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Exportar CSV
                    </a>
                    <div class="ml-auto text-sm text-brand-secondary/60">
                        Mostrando: <span class="font-semibold text-brand-secondary">{{ $contentTypeLabel }}</span>
                    </div>
                </div>
            </form>

            <div class="mt-8 overflow-hidden rounded-3xl border border-brand-secondary/10">
                <table class="min-w-full divide-y divide-brand-secondary/10">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Contenido</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Acción</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Elemento</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Gestor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Cambios</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-secondary/10 bg-white">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-4 text-sm text-brand-secondary">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-brand-secondary">{{ $log->content_type_label }}</td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
                                    <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                                        {{ $log->action_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
                                    <div class="font-medium">{{ $log->target_name }}</div>
                                    @if ($log->target_reference)
                                        <div class="mt-1 text-xs text-brand-secondary/55 break-all">{{ $log->target_reference }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary">
                                    <div class="font-medium">{{ $log->actor_name }}</div>
                                    @if ($log->actor_email)
                                        <div class="mt-1 text-xs text-brand-secondary/55 break-all">{{ $log->actor_email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-brand-secondary/75">
                                    @if (($log->changes ?? []) === [])
                                        Sin cambios detallados
                                    @else
                                        <ul class="space-y-2">
                                            @foreach ($log->changes as $field => $change)
                                                <li class="rounded-2xl bg-slate-50 px-3 py-2">
                                                    <span class="block text-xs font-semibold uppercase tracking-[0.14em] text-brand-secondary/45">{{ $field }}</span>
                                                    <span class="block text-xs text-brand-secondary/70">
                                                        {{ $change['from'] ?? 'vacio' }} → {{ $change['to'] ?? 'vacio' }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
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
