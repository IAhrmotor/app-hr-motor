@extends('layouts.app')

@section('content')
    @php
        $authUser = auth()->user();
        $invitationExpiresInMinutes = (int) config('auth.passwords.users.expire', 60);

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
                    <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Gestión de usuarios</h1>
                    <p class="mt-2 text-sm text-brand-secondary/70">Listado de usuarios registrados en la plataforma.</p>
                </div>

                <a href="{{ route('users.create') }}"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90"
                    title="Crear usuario" aria-label="Crear usuario">
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

            <form method="GET" action="{{ route('users.index') }}" class="mb-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="relative w-full md:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-brand-secondary/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Buscar por nombre, correo, rol, delegación o ID Salesforce"
                            class="w-full rounded-2xl border border-gray-300 py-3 pl-11 pr-4 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div class="relative w-full md:w-56">
                        <select name="status"
                            class="w-full cursor-pointer appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                            <option value="">Todos los estados</option>
                            <option value="active" @selected($status === 'active')>Activos</option>
                            <option value="pending" @selected($status === 'pending')>Pendientes</option>
                        </select>

                        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex cursor-pointer items-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">Buscar</button>

                        @if (request('search') || request('status') || request('sort') || request('direction'))
                            <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Limpiar</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-secondary/10">
                        <thead class="bg-brand-secondary/5">
                            <tr>
                                @foreach ([
                                    'name' => 'Nombre',
                                    'email' => 'Correo',
                                    'role' => 'Rol',
                                    'dealership' => 'Delegación',
                                    'is_active' => 'Estado',
                                    'salesforce_user_id' => 'ID Salesforce',
                                ] as $column => $label)
                                    <th class="px-6 py-4 text-left">
                                        <a href="{{ route('users.index', array_merge(request()->query(), ['sort' => $column, 'direction' => $sortDirection($column, $sort, $direction)])) }}"
                                            class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary">
                                            <span>{{ $label }}</span>
                                        </a>
                                    </th>
                                @endforeach
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-brand-secondary/10 bg-white">
                            @forelse ($users as $user)
                                @php
                                    $canManageUser = $authUser->role === \App\Models\User::ROLE_ADMIN
                                        || ($authUser->role === \App\Models\User::ROLE_MANAGER
                                            && $authUser->id !== $user->id
                                            && $user->isCommercialLike());
                                    $isInvitationExpired = ! $user->is_active
                                        && $user->must_change_password
                                        && $user->created_at?->lt(now()->subMinutes($invitationExpiresInMinutes));
                                    $rowClasses = $user->is_active
                                        ? 'transition hover:bg-brand-secondary/5'
                                        : ($isInvitationExpired
                                            ? 'bg-red-50/70 transition hover:bg-red-100/70'
                                            : 'bg-amber-50/70 transition hover:bg-amber-100/70');
                                @endphp
                                <tr class="{{ $rowClasses }}">
                                    <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                                        <a href="{{ route('users.show', $user) }}" class="flex items-center gap-3 transition hover:opacity-80">
                                            <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10">
                                            <span>{{ $user->name }}</span>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $user->email }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                        <span class="inline-flex min-w-[7.25rem] justify-center rounded-full bg-brand-primary/10 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-brand-primary">{{ $user->role_label }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $user->resolved_dealership_name ?: 'No aplica' }}</td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">
                                        @if ($user->is_active)
                                            <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-green-100 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-green-700">Activo</span>
                                        @elseif ($isInvitationExpired)
                                            <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-red-100 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-red-700">Caducado</span>
                                        @else
                                            <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-amber-100 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-amber-700">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $user->salesforce_user_id ?: 'No aplica' }}</td>
                                    <td class="px-6 py-4">
                                        @if ($canManageUser)
                                            <div class="flex justify-end gap-2">
                                                @if (! $user->is_active || $user->must_change_password)
                                                    <form method="POST" action="{{ route('users.resend-invitation', $user) }}">
                                                        @csrf
                                                        <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100 hover:text-amber-800" title="Reenviar correo de activación" aria-label="Reenviar correo de activación">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 7.5v9A2.25 2.25 0 0119.5 18.75h-15A2.25 2.25 0 012.25 16.5v-9m19.5 0A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615A2.25 2.25 0 012.25 7.743V7.5" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('users.edit', $user) }}" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Editar usuario" aria-label="Editar usuario">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.462 3 20l1.538-5.25L16.862 3.487z" />
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('¿Seguro que quieres eliminar este usuario?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-700" title="Eliminar usuario" aria-label="Eliminar usuario">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.1 1.02.17m-1.02-.17L18.16 19.67A2.25 2.25 0 0115.91 21.75H8.09a2.25 2.25 0 01-2.25-2.08L4.77 5.79m14.46 0A48.108 48.108 0 0012 5.25c-2.43 0-4.82.18-7.23.54m14.46 0a48.11 48.11 0 00-14.46 0m9.75-2.04v-.23A1.5 1.5 0 0013.02 2h-2.04a1.5 1.5 0 00-1.5 1.5v.23m5.04 0A49.5 49.5 0 009.48 3.75" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No hay usuarios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($users->hasPages())
                <div class="mt-6">{{ $users->links() }}</div>
            @endif
        </section>
    </main>
@endsection
