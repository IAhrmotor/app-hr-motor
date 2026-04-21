@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Contactos de agenda</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Gestión de contactos externos visibles en la agenda.</p>
                </div>

                <a href="{{ route('admin.contacts.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear contacto" aria-label="Crear contacto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <circle cx="9" cy="8" r="2.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 17c.9-2 2.5-3 4.5-3s3.6 1 4.5 3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8v6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 11h6" />
                    </svg>
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.contacts.index') }}" class="mb-6">
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
                            <a href="{{ route('admin.contacts.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
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
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Extensión Enreach</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-brand-secondary/10 bg-white">
                            @forelse ($contacts as $contact)
                                <tr class="transition hover:bg-brand-secondary/5">
                                    <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                                        <a href="{{ route('agenda.contacts.show', $contact) }}" class="transition hover:text-brand-primary">
                                            {{ $contact->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $contact->email ?: 'No disponible' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $contact->phone ?: 'No disponible' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $contact->enreach_extension ?: 'No disponible' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('agenda.contacts.show', $contact) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Ver contacto" aria-label="Ver contacto">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12 18 19.5 12 19.5 2.25 12 2.25 12Z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('admin.contacts.edit', $contact) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Editar contacto" aria-label="Editar contacto">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.462 3 20l1.538-5.25L16.862 3.487z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" onsubmit="return confirm('Seguro que quieres eliminar este contacto?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-700" title="Eliminar contacto" aria-label="Eliminar contacto">
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
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No hay contactos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($contacts->hasPages())
                <div class="mt-6">{{ $contacts->links() }}</div>
            @endif
        </section>
    </main>
@endsection
