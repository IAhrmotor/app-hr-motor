@extends('layouts.app')

@section('content')
    @php
        $sortDirection = function ($column, $sort, $direction) {
            if ($sort !== $column) {
                return 'asc';
            }

            return $direction === 'asc' ? 'desc' : 'asc';
        };
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Gestión de delegaciones</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Listado de delegaciones configuradas en la aplicación.</p>
                </div>

                <a href="{{ route('dealerships.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear delegación" aria-label="Crear delegación">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('dealerships.index') }}" class="mb-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Buscar por nombre, ID Salesforce o URLs"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>
                        @if (request('search') || request('sort') || request('direction'))
                            <a href="{{ route('dealerships.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-secondary/10">
                        <thead class="bg-brand-secondary/5">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <a href="{{ route('dealerships.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $sortDirection('name', $sort, $direction)])) }}"
                                        class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary">
                                        <span>Delegación</span>
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <a href="{{ route('dealerships.index', array_merge(request()->query(), ['sort' => 'salesforce_id', 'direction' => $sortDirection('salesforce_id', $sort, $direction)])) }}"
                                        class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary">
                                        <span>ID Salesforce</span>
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Equipo</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-brand-secondary/10 bg-white">
                            @forelse ($dealerships as $dealership)
                                <tr class="transition hover:bg-brand-secondary/5">
                                    <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                                        <a href="{{ route('dealerships.show', $dealership) }}" class="flex items-center gap-3 transition hover:opacity-80">
                                            @if ($dealership->image_url)
                                                <img src="{{ $dealership->image_url }}" alt="Imagen de {{ $dealership->name }}"
                                                    class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                            @else
                                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-secondary text-sm font-semibold text-white">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($dealership->name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <span>{{ $dealership->name }}</span>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $dealership->salesforce_id ?: 'Sin configurar' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $dealership->users_count }} {{ $dealership->users_count === 1 ? 'usuario' : 'usuarios' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('dealerships.edit', $dealership) }}" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Editar delegación" aria-label="Editar delegación">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.462 3 20l1.538-5.25L16.862 3.487z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('dealerships.destroy', $dealership) }}" onsubmit="return confirm('¿Seguro que quieres eliminar esta delegación?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-700" title="Eliminar delegación" aria-label="Eliminar delegación">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.1 1.02.17m-1.02-.17L18.16 19.67A2.25 2.25 0 0115.91 21.75H8.09a2.25 2.25 0 01-2.25-2.08L4.77 5.79m14.46 0A48.108 48.108 0 0012 5.25c-2.43 0-4.82.18-7.23.54m14.46 0a48.11 48.11 0 00-14.46 0m9.75-2.04v-.23A1.5 1.5 0 0013.02 2h-2.04a1.5 1.5 0 00-1.5 1.5v.23m5.04 0A49.5 49.5 0 009.48 3.75" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No hay delegaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($dealerships->hasPages())
                <div class="mt-6">{{ $dealerships->links() }}</div>
            @endif
        </section>
    </main>
@endsection
