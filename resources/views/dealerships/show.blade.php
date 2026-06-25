@extends('layouts.app')

@section('content')
    @php
        $salesRank = $dealershipMonthlyRankings['sales'] ?? null;
        $purchasesRank = $dealershipMonthlyRankings['purchases'] ?? null;
        $googleRating = (float) ($googleBusinessProfileStats['average_rating'] ?? 0);
        $googleRatingCount = (int) ($googleBusinessProfileStats['total_reviews'] ?? 0);
        $starFillWidth = fn ($value) => max(0, min(100, ((float) $value / 5) * 100));
    @endphp
    <main
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8"
    >
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-5">
                    @if ($dealership->image_url)
                        <button
                            type="button"
                            @click="openImage({ src: @js($dealership->image_url), alt: @js('Imagen de '.$dealership->name), title: @js($dealership->name) })"
                            class="group relative cursor-pointer overflow-hidden rounded-3xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                            aria-label="Ampliar imagen de {{ $dealership->name }}"
                        >
                            <img src="{{ $dealership->image_url }}" alt="Imagen de {{ $dealership->name }}"
                                class="h-24 w-24 rounded-3xl object-cover ring-2 ring-brand-primary/10 transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-3xl bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                                Ver
                            </span>
                        </button>
                    @else
                        <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-brand-secondary text-3xl font-semibold text-white">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($dealership->name, 0, 2)) }}
                        </div>
                    @endif

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Delegación</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $dealership->name }}</h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">{{ $dealership->phone }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <div class="relative inline-flex text-2xl leading-none" aria-label="Valoración media en Google Maps {{ number_format($googleRating, 2) }} de 5">
                                <div class="flex text-gray-200" aria-hidden="true">
                                    <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                                </div>
                                <div class="absolute inset-0 overflow-hidden whitespace-nowrap text-amber-400" aria-hidden="true" style="width: {{ $starFillWidth($googleRating) }}%;">
                                    <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-brand-secondary">{{ number_format($googleRating, 2) }}/5</span>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                    {{ number_format($googleRatingCount, 0, ',', '.') }} reseñas en Google
                                </span>
                            </div>
                        </div>
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

                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Información general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-brand-secondary/60">Nombre</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $dealership->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Teléfono</dt>
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
                @php
                    $commercialUsers = $dealership->users->filter(fn ($user) => $user->isRankedCommercial())->values();
                    $nonCommercialUsers = $dealership->users->reject(fn ($user) => $user->isRankedCommercial())->values();
                    $storeManagerTooltipClasses = 'pointer-events-none absolute left-full top-1/2 z-10 ml-3 inline-flex -translate-y-1/2 whitespace-nowrap rounded-xl bg-brand-secondary px-3 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition duration-200 group-hover:opacity-100';
                @endphp

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold text-brand-secondary">Usuarios asignados</h2>
                    <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                        {{ $dealership->users->count() }} {{ $dealership->users->count() === 1 ? 'usuario' : 'usuarios' }}
                    </span>
                </div>

                @if ($dealership->users->isEmpty())
                    <p class="mt-4 text-sm text-brand-secondary/70">No hay usuarios asociados a esta delegación.</p>
                @else
                    @if ($commercialUsers->isNotEmpty())
                        <div class="mt-6">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-secondary/55">Comerciales</h3>
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                    {{ $commercialUsers->count() }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3">
                                @foreach ($commercialUsers as $user)
                                    @php
                                        $monthlyStats = $userMonthlyStats[$user->id] ?? ['sales' => 0, 'purchases' => 0];
                                    @endphp
                                    <a href="{{ route('users.show', $user) }}" class="flex items-center justify-between gap-4 rounded-2xl border border-brand-secondary/10 px-4 py-3 transition hover:bg-brand-secondary/5">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="group relative inline-flex max-w-full">
                                                        <p class="truncate text-sm font-semibold {{ $user->isStoreManager() ? 'text-amber-700' : 'text-brand-secondary' }}">{{ $user->name }}</p>
                                                        @if ($user->isStoreManager())
                                                            <span class="{{ $storeManagerTooltipClasses }}">Jefe de tienda</span>
                                                        @endif
                                                    </span>
                                                    <span class="inline-flex rounded-full bg-brand-secondary/5 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] {{ $user->isStoreManager() ? 'text-amber-700 ring-1 ring-amber-200 bg-amber-50' : 'text-brand-secondary/55 ring-1 ring-brand-secondary/10' }}">
                                                        {{ $user->role_label }}
                                                    </span>
                                                </div>
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
                        </div>
                    @endif

                    @if ($nonCommercialUsers->isNotEmpty())
                        <div class="mt-8">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-secondary/55">No comerciales</h3>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                    {{ $nonCommercialUsers->count() }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3">
                                @foreach ($nonCommercialUsers as $user)
                                    <a href="{{ route('users.show', $user) }}" class="flex items-center gap-4 rounded-2xl border border-brand-secondary/10 px-4 py-3 transition hover:bg-brand-secondary/5">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-brand-secondary">{{ $user->name }}</p>
                                                    <span class="inline-flex rounded-full bg-brand-secondary/5 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-secondary/55 ring-1 ring-brand-secondary/10">
                                                        {{ $user->role_label }}
                                                    </span>
                                                </div>
                                                <p class="truncate text-xs text-brand-secondary/60">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </section>
        </section>

        @if ($dealership->image_url)
            <div
                x-cloak
                x-show="isImageOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-6 py-8 backdrop-blur-sm"
                @click.self="closeImage()"
            >
                <div class="inline-flex max-w-[calc(100vw-3rem)] flex-col items-center">
                    <div
                        x-ref="imageViewport"
                        class="relative touch-none overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl"
                        :class="imageScale > 1 ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                        @wheel.prevent="handleWheel($event)"
                        @pointerdown="handlePointerDown($event)"
                        @pointermove="handlePointerMove($event)"
                        @pointerup="handlePointerUp($event)"
                        @pointercancel="handlePointerCancel($event)"
                    >
                        <button
                            type="button"
                            @pointerdown.stop
                            @click.stop="closeImage()"
                            class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                            aria-label="Cerrar imagen ampliada"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <img
                            :src="imageUrl"
                            :alt="imageAlt"
                            @dblclick="toggleZoom($event.clientX, $event.clientY)"
                            draggable="false"
                            @dragstart.prevent
                            class="block max-h-[80vh] w-auto max-w-[calc(100vw-3rem)] select-none object-contain bg-slate-900 will-change-transform"
                            :class="isDragging ? 'transition-none' : 'transition-transform duration-200'"
                            :style="`transform: translate3d(${translateX}px, ${translateY}px, 0) scale(${imageScale}); transform-origin: center center;`"
                        >
                    </div>

                    <div class="mt-4 flex items-center justify-center gap-2">
                        <button
                            type="button"
                            @click="zoomOut()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                            aria-label="Reducir zoom"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                            </svg>
                        </button>
                    <button
                        type="button"
                        @click="resetZoom()"
                        class="inline-flex h-10 min-w-20 items-center justify-center rounded-full bg-white/90 px-3 text-sm font-semibold text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Restablecer zoom"
                    >
                        <span x-text="`${imageScale.toFixed(2).replace(/\.00$/, '')}x`"></span>
                    </button>
                    <button
                        type="button"
                        @click="downloadImage()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Descargar imagen"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5">
                            <path d="M12.5535 16.5061C12.4114 16.6615 12.2106 16.75 12 16.75C11.7894 16.75 11.5886 16.6615 11.4465 16.5061L7.44648 12.1311C7.16698 11.8254 7.18822 11.351 7.49392 11.0715C7.79963 10.792 8.27402 10.8132 8.55352 11.1189L11.25 14.0682V3C11.25 2.58579 11.5858 2.25 12 2.25C12.4142 2.25 12.75 2.58579 12.75 3V14.0682L15.4465 11.1189C15.726 10.8132 16.2004 10.792 16.5061 11.0715C16.8118 11.351 16.833 11.8254 16.5535 12.1311L12.5535 16.5061Z" fill="#1C274C"/>
                            <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.2536 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="#1C274C"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="zoomIn()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Aumentar zoom"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>

                    <p class="mt-4 text-center text-sm font-medium text-white/80" x-text="imageTitle || @js($dealership->name)">
                    </p>
                </div>
            </div>
        @endif
    </main>
@endsection
