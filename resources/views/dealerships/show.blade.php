@extends('layouts.app')

@section('content')
    @php
        $salesRank = $dealershipMonthlyRankings['sales'] ?? null;
        $purchasesRank = $dealershipMonthlyRankings['purchases'] ?? null;
    @endphp
    <main class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-5">
                    @if ($dealership->image_url)
                        <img src="{{ $dealership->image_url }}" alt="Imagen de {{ $dealership->name }}"
                            class="h-24 w-24 rounded-3xl object-cover ring-2 ring-brand-primary/10">
                    @else
                        <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-brand-secondary text-3xl font-semibold text-white">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($dealership->name, 0, 2)) }}
                        </div>
                    @endif

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Delegacion</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $dealership->name }}</h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">{{ $dealership->phone }}</p>
                        @if ($salesRank || $purchasesRank)
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($salesRank)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800 ring-1 ring-amber-200">
                                        Top {{ $salesRank }} en ventas
                                    </span>
                                @endif
                                @if ($purchasesRank)
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-800 ring-1 ring-sky-200">
                                        Top {{ $purchasesRank }} en compras
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($dealership->google_maps_url)
                        <a href="{{ $dealership->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Google Maps
                        </a>
                    @endif

                    @if ($dealership->reviews_url)
                        <a href="{{ $dealership->reviews_url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Reseñas
                        </a>
                    @endif

                    <a href="{{ route('dealerships.index') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Volver a delegaciones
                    </a>
                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Informacion general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-brand-secondary/60">Nombre</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $dealership->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Telefono</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $dealership->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Google Maps</dt>
                        @if ($dealership->google_maps_url)
                            <dd class="mt-1 font-semibold text-brand-secondary break-all">
                                <a href="{{ $dealership->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-primary">
                                    {{ $dealership->google_maps_url }}
                                </a>
                            </dd>
                        @else
                            <dd class="mt-1 font-semibold text-brand-secondary">Sin configurar</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Reseñas</dt>
                        @if ($dealership->reviews_url)
                            <dd class="mt-1 font-semibold text-brand-secondary break-all">
                                <a href="{{ $dealership->reviews_url }}" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-primary">
                                    {{ $dealership->reviews_url }}
                                </a>
                            </dd>
                        @else
                            <dd class="mt-1 font-semibold text-brand-secondary">Sin configurar</dd>
                        @endif
                    </div>
                </dl>
            </section>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold text-brand-secondary">Usuarios asignados</h2>
                    <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                        {{ $dealership->users->count() }} {{ $dealership->users->count() === 1 ? 'usuario' : 'usuarios' }}
                    </span>
                </div>

                @if ($dealership->users->isEmpty())
                    <p class="mt-4 text-sm text-brand-secondary/70">No hay usuarios asociados a esta delegacion.</p>
                @else
                    <div class="mt-4 grid gap-3">
                        @foreach ($dealership->users as $user)
                            @php
                                $monthlyStats = $userMonthlyStats[$user->id] ?? ['sales' => 0, 'purchases' => 0];
                            @endphp
                            <a href="{{ route('users.show', $user) }}" class="flex items-center justify-between gap-4 rounded-2xl border border-brand-secondary/10 px-4 py-3 transition hover:bg-brand-secondary/5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-brand-secondary">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-brand-secondary/60">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-secondary/45">Este mes</p>
                                    <div class="mt-1 flex items-center justify-end gap-2">
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                            {{ number_format((float) $monthlyStats['sales'], 0, ',', '.') }} V
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
                                            {{ number_format((float) $monthlyStats['purchases'], 0, ',', '.') }} C
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </section>
    </main>
@endsection
