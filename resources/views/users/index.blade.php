@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">
                        Gestión de usuarios
                    </h1>

                    <p class="mt-2 text-sm text-brand-secondary/70">
                        Listado inicial de usuarios registrados en la plataforma.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-secondary/10">
                        <thead class="bg-brand-secondary/5">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">
                                    Nombre
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">
                                    Correo
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">
                                    Rol
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-brand-secondary/10 bg-white">
                            @forelse ($users as $user)
                                <tr class="transition hover:bg-brand-secondary/5">
                                    <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                        <span
                                            class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-brand-secondary/70">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
@endsection