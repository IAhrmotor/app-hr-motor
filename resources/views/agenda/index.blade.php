@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Agenda</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Directorio interno y contactos externos de la empresa.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('agenda.index') }}" class="mb-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Buscar por nombre, correo o número"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>

                        @if (request('search'))
                            <a href="{{ route('agenda.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-secondary/10">
                        <thead class="bg-brand-secondary/5">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Nombre</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Correo</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Teléfono</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">3CX</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Extensión Enreach</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Tipo</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-brand-secondary/10 bg-white">
                            @forelse ($results as $entry)
                                <tr class="transition hover:bg-brand-secondary/5">
                                    <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                                        <a href="{{ $entry['route'] }}" class="flex items-center gap-3 transition hover:opacity-80">
                                            <img src="{{ $entry['avatar'] }}" alt="Avatar de {{ $entry['name'] }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10">
                                            <div>
                                                <span class="block">{{ $entry['name'] }}</span>
                                                <span class="mt-1 block text-xs font-medium text-brand-secondary/60">{{ $entry['subtitle'] }}</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $entry['email'] ?: 'No disponible' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $entry['phone'] ?: 'No disponible' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $entry['threecx_extension'] ?: 'No disponible' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                        {{ $entry['enreach_extension'] ?? 'No disponible' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                        <span class="inline-flex min-w-[7rem] justify-center rounded-full px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide {{ $entry['type'] === 'user' ? 'bg-brand-primary/10 text-brand-primary' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $entry['type'] === 'user' ? 'Usuario' : 'Contacto' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No se han encontrado resultados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($results->hasPages())
                <div class="mt-6">{{ $results->links() }}</div>
            @endif
        </section>
    </main>
@endsection
