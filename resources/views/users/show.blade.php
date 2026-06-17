@extends('layouts.app')

@section('content')
    @php
        $isOwnProfile = auth()->id() === $user->id;
        $visibleRole = app_visible_role(auth()->user());
        $salesRankingPosition = $rankingPositions['sales']['position'] ?? null;
        $salesTotal = $rankingPositions['sales']['total'] ?? 0;
        $purchaseRankingPosition = $rankingPositions['purchases']['position'] ?? null;
        $purchaseTotal = $rankingPositions['purchases']['total'] ?? 0;
    @endphp

    <main
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8"
    >
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                <div class="flex min-w-0 items-center gap-5">
                    <button
                        type="button"
                        @click="openImage({ src: @js($user->avatar_url), alt: @js('Avatar de '.$user->name), title: @js($user->name) })"
                        class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                        aria-label="Ampliar imagen de {{ $user->name }}"
                    >
                        <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="block h-full w-full rounded-full object-cover ring-2 ring-brand-primary/10 transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-full bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                            Ver
                        </span>
                    </button>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Perfil de usuario</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $user->name }}</h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">{{ $user->email }}</p>
                        <div class="mt-2 space-y-2">
                            @if (filled($user->job_position))
                                <p class="text-base font-medium tracking-tight text-brand-secondary/80 md:text-lg">
                                    {{ $user->job_position }}
                                </p>
                            @endif

                            @if ($user->company_entry_date)
                                <p class="text-sm text-brand-secondary/60">
                                    Desde: {{ $user->company_entry_date->format('d/m/Y') }}
                                </p>
                            @endif

                            @if ($user->isDisabled())
                                <span class="mt-3 inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Desactivado</span>
                            @endif
                        </div>
                        @if ($user->isDisabled())
                            <div class="mt-4 inline-flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-700">Cuenta desactivada</p>
                                    <p class="mt-0.5 text-slate-500">Este usuario no puede iniciar sesión ni acceder a la aplicación hasta que se reactive.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-start gap-3 lg:justify-end">
                    @unless ($isOwnProfile)
                        <a href="{{ route('chat.beta', ['recipient' => $user->id]) }}" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90" title="Chatear" aria-label="Chatear con {{ $user->name }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 10.5H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M8 14H13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M17 3.33782C15.5291 2.48697 13.8214 2 12 2C6.47715 2 2 6.47715 2 12C2 13.5997 2.37562 15.1116 3.04346 16.4525C3.22094 16.8088 3.28001 17.2161 3.17712 17.6006L2.58151 19.8267C2.32295 20.793 3.20701 21.677 4.17335 21.4185L6.39939 20.8229C6.78393 20.72 7.19121 20.7791 7.54753 20.9565C8.88837 21.6244 10.4003 22 12 22C17.5228 22 22 17.5228 22 12C22 10.1786 21.513 8.47087 20.6622 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </a>
                    @endunless

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

                    @if (in_array($visibleRole, ['admin', 'gestor'], true))
                        <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver a usuarios</a>
                    @else
                        <a href="{{ route('agenda.index') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver a agenda</a>
                    @endif
                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Información general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-3">
                    <div>
                        <dt class="text-brand-secondary/60">Rol</dt>
                        <dd class="mt-1">
                            <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">{{ $user->role_label }}</span>
                        </dd>
                    </div>

                    @if (filled($user->phone))
                        <div>
                            <dt class="text-brand-secondary/60">Teléfono</dt>
                            <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->phone }}</dd>
                        </div>
                    @endif

                    @if (filled($user->enreach_extension))
                        <div>
                            <dt class="text-brand-secondary/60">Extensión Enreach</dt>
                            <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->enreach_extension }}</dd>
                        </div>
                    @endif

                    @if (filled($user->job_position))
                        <div>
                            <dt class="text-brand-secondary/60">Puesto</dt>
                            <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->job_position }}</dd>
                        </div>
                    @endif

                    @if ($user->resolved_dealership_name)
                        <div>
                            <dt class="text-brand-secondary/60">Delegación</dt>
                            <dd class="mt-2">
                                @if ($user->assignedDealership)
                                    <a href="{{ route('dealerships.show', $user->assignedDealership) }}"
                                        class="font-semibold text-brand-secondary transition hover:text-brand-primary">
                                        {{ $user->resolved_dealership_name }}
                                    </a>
                                @else
                                    <span class="font-semibold text-brand-secondary">{{ $user->resolved_dealership_name }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    @if ($user->isDisabled())
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-3">
                            <dt class="text-brand-secondary/60">Estado de la cuenta</dt>
                            <dd class="mt-1 text-sm font-semibold uppercase tracking-wide text-slate-600">Cuenta desactivada</dd>
                            <p class="mt-2 text-sm text-slate-600">
                                Usuario desactivado desde {{ $user->disabled_at?->format('d/m/Y H:i') ?? 'fecha desconocida' }}.
                            </p>
                            @if ($user->disabled_reason)
                                <p class="mt-2 text-sm text-slate-500"><span class="font-semibold text-slate-600">Motivo:</span> {{ $user->disabled_reason }}</p>
                            @endif
                        </div>
                    @endif
                </dl>
            </section>

            @if ($user->isRankedCommercial() && ($salesRankingPosition || $purchaseRankingPosition))
                <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-white p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-brand-secondary">Posición en rankings</h2>
                            <p class="mt-1 text-sm text-brand-secondary/65">Puesto actual del mes en ventas y compras.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-brand-secondary/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/70">
                            {{ $user->role_label }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <a href="{{ route('leaderboard.sales') }}" class="block rounded-3xl transition hover:-translate-y-1 hover:shadow-md">
                            <div class="rounded-3xl border border-amber-200/70 bg-amber-50/80 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700/80">Ranking ventas</p>
                                <p class="mt-3 text-3xl font-bold text-amber-800">
                                    {{ $salesRankingPosition ? 'Top ' . $salesRankingPosition : 'Sin posición' }}
                                </p>
                                <p class="mt-2 text-sm font-semibold text-amber-800/85">
                                    {{ number_format((float) $salesTotal, 0, ',', '.') }} ventas este mes
                                </p>
                                <p class="mt-1 text-sm text-amber-800/75">Según el ranking mensual de ventas.</p>
                            </div>
                        </a>

                        <a href="{{ route('leaderboard.purchases') }}" class="block rounded-3xl transition hover:-translate-y-1 hover:shadow-md">
                            <div class="rounded-3xl border border-sky-200/70 bg-sky-50/80 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700/80">Ranking compras</p>
                                <p class="mt-3 text-3xl font-bold text-sky-800">
                                    {{ $purchaseRankingPosition ? 'Top ' . $purchaseRankingPosition : 'Sin posición' }}
                                </p>
                                <p class="mt-2 text-sm font-semibold text-sky-800/85">
                                    {{ number_format((float) $purchaseTotal, 0, ',', '.') }} compras este mes
                                </p>
                                <p class="mt-1 text-sm text-sky-800/75">Según el ranking mensual de compras.</p>
                            </div>
                        </a>
                    </div>
                </section>
            @endif
        </section>

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

                <p class="mt-4 text-center text-sm font-medium text-white/80" x-text="imageTitle || @js($user->name)">
                </p>
            </div>
        </div>
    </main>
@endsection
