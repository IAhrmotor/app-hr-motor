@extends('layouts.app')

@section('content')
    @php
        $isOwnProfile = auth()->id() === $user->id;
        $salesRankingPosition = $rankingPositions['sales'] ?? null;
        $purchaseRankingPosition = $rankingPositions['purchases'] ?? null;
    @endphp

    <main
        x-data="{ isImageOpen: false }"
        @keydown.escape.window="isImageOpen = false"
        class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8"
    >
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-5">
                    <button
                        type="button"
                        @click="isImageOpen = true"
                        class="group relative cursor-pointer overflow-hidden rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                        aria-label="Ampliar imagen de {{ $user->name }}"
                    >
                        <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="h-24 w-24 rounded-full object-cover ring-2 ring-brand-primary/10 transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-full bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                            Ver
                        </span>
                    </button>

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Perfil de usuario</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $user->name }}</h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($user->linkedin_url)
                        <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0A66C2] text-white transition hover:opacity-90" title="Ver LinkedIn" aria-label="Ver LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M6.94 8.5H3.56V19h3.38V8.5ZM5.25 3C4.17 3 3.3 3.88 3.3 4.96c0 1.07.87 1.94 1.95 1.94 1.08 0 1.95-.87 1.95-1.94C7.2 3.88 6.33 3 5.25 3Zm14.45 9.47c0-3.17-1.69-4.64-3.95-4.64-1.82 0-2.64 1-3.09 1.7V8.5H9.28c.04.68 0 10.5 0 10.5h3.38v-5.86c0-.31.02-.62.11-.84.25-.62.82-1.27 1.79-1.27 1.27 0 1.78.96 1.78 2.37V19h3.38v-6.53Z" />
                            </svg>
                        </a>
                    @endif

                    @if ($isOwnProfile)
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Editar perfil</a>
                    @endif

                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                        <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver a usuarios</a>
                    @endif
                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Informacion general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-3">
                    <div>
                        <dt class="text-brand-secondary/60">Nombre</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Correo</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Rol</dt>
                        <dd class="mt-1">
                            <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">{{ ucfirst($user->role) }}</span>
                        </dd>
                    </div>

                    @if ($user->resolved_dealership_name)
                        <div>
                            <dt class="text-brand-secondary/60">Delegacion</dt>
                            <dd class="mt-2">
                                @if ($user->assignedDealership)
                                    <a href="{{ route('dealerships.show', $user->assignedDealership) }}"
                                        class="inline-flex items-center gap-2 rounded-2xl border border-brand-primary/15 bg-white px-3 py-2 text-sm font-semibold text-brand-primary transition hover:border-brand-primary/30 hover:bg-brand-primary/5 hover:text-brand-secondary">
                                        <span>{{ $user->resolved_dealership_name }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="font-semibold text-brand-secondary">{{ $user->resolved_dealership_name }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($user->role === 'comercial' && ($salesRankingPosition || $purchaseRankingPosition))
                <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-white p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-brand-secondary">Posicion en rankings</h2>
                            <p class="mt-1 text-sm text-brand-secondary/65">Puesto actual del mes en ventas y compras.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-brand-secondary/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/70">
                            Comercial
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-3xl border border-amber-200/70 bg-amber-50/80 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700/80">Ranking ventas</p>
                            <p class="mt-3 text-3xl font-bold text-amber-800">
                                {{ $salesRankingPosition ? 'Top ' . $salesRankingPosition : 'Sin posicion' }}
                            </p>
                            <p class="mt-2 text-sm text-amber-800/75">Segun el ranking mensual de ventas.</p>
                        </div>

                        <div class="rounded-3xl border border-sky-200/70 bg-sky-50/80 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700/80">Ranking compras</p>
                            <p class="mt-3 text-3xl font-bold text-sky-800">
                                {{ $purchaseRankingPosition ? 'Top ' . $purchaseRankingPosition : 'Sin posicion' }}
                            </p>
                            <p class="mt-2 text-sm text-sky-800/75">Segun el ranking mensual de compras.</p>
                        </div>
                    </div>
                </section>
            @endif
        </section>

        <div
            x-cloak
            x-show="isImageOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-6 py-8 backdrop-blur-sm"
            @click.self="isImageOpen = false"
        >
            <div class="relative w-full max-w-4xl">
                <button
                    type="button"
                    @click="isImageOpen = false"
                    class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                    aria-label="Cerrar imagen ampliada"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl">
                    <img
                        src="{{ $user->avatar_url }}"
                        alt="Imagen ampliada de {{ $user->name }}"
                        class="max-h-[80vh] w-full object-contain bg-slate-900"
                    >
                </div>

                <p class="mt-4 text-center text-sm font-medium text-white/80">
                    {{ $user->name }}
                </p>
            </div>
        </div>
    </main>
@endsection
