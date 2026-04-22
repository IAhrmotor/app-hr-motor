@extends('layouts.app')

@section('content')
    <section
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @open-vehicle-image.window="openImage($event.detail)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="py-10 sm:py-14"
    >
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            @php
                $visibleRole = app_visible_role(auth()->user());
            @endphp
            <div class="flex flex-col gap-6 rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur sm:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-brand-primary">Salesforce</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-secondary sm:text-4xl">
                            Ranking de coches calientes y fríos
                        </h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-brand-secondary/70 sm:text-base">
                            Vista del parque disponible con garantía: arriba los coches con más leads asociados y justo debajo
                            los que menos tracción están generando para detectar oportunidades.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-end">
                        <a href="#coches-frios"
                            @click.prevent="document.getElementById('coches-frios')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-sky-200/80 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                            <x-icons.ice class="h-5 w-5 shrink-0 text-sky-600" />
                            <span>Ir a coches fríos</span>
                        </a>

                        <div class="rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary/80">
                            <p class="font-semibold">Estado</p>
                            <p class="mt-1">
                                @if ($connection)
                                    Conectado con Salesforce
                                @elseif ($salesforceConfigReady)
                                    Sin conexión a Salesforce
                                @else
                                    Sin conexión a Salesforce
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-brand-secondary/60">
                                @if ($connection?->last_synced_at)
                                    Última sincronización: {{ $connection->last_synced_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                @elseif ($demoMode)
                                    Estás viendo una simulación con nombres ficticios mientras Salesforce no está conectado.
                                @elseif ($salesforceConfigReady)
                                    La app está preparada, pero aún no se ha completado el OAuth.
                                @else
                                    Faltan credenciales o la URL de callback en este entorno.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if ($demoMode)
                    <div class="rounded-2xl border border-dashed border-sky-300 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">
                        Modo demo activo. El hot y cold ranking muestran una simulación con nombres ficticios hasta que conectes Salesforce.
                    </div>
                @endif

                @if (session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if (! $leaderboardTablesReady)
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm leading-6 text-sky-900">
                        El ranking está en modo preparación. La página ya no falla aunque falten tablas, pero todavía necesitas ejecutar las migraciones para guardar la conexión y los datos de este ranking.
                    </div>
                @endif

                @auth
                    @if (in_array($visibleRole, ['admin', 'gestor'], true))
                        @if (! $connection || ! $leaderboardTablesReady)
                            <div class="flex flex-col gap-3 sm:flex-row">
                                @if ($salesforceConfigReady && $leaderboardTablesReady)
                                    <a href="{{ route('salesforce.connect') }}"
                                        class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:brightness-95">
                                        Conectar Salesforce
                                    </a>
                                @else
                                    <span
                                        class="inline-flex items-center justify-center rounded-2xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-500">
                                        Configuración pendiente
                                    </span>
                                @endif

                                <form method="POST" action="{{ route('leaderboard.sync') }}">
                                    @csrf
                                    <button type="submit"
                                        @disabled(! $connection || ! $leaderboardTablesReady)
                                        class="inline-flex w-full items-center justify-center rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5 sm:w-auto">
                                        Sincronizar ahora
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if (! $leaderboardTablesReady)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                Antes de conectar Salesforce, ejecuta las migraciones de Laravel para crear las tablas nuevas de este ranking.
                            </div>
                        @elseif (! $salesforceConfigReady)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                La integración está en modo espera. La app no falla, pero no intentará conectar ni sincronizar hasta que completes
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_ID</code>,
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_SECRET</code>
                                y
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_REDIRECT_URI</code>.
                            </div>
                        @elseif (! $connection)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                En Salesforce Connected App debes autorizar exactamente la misma callback URL que uses aquí en
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">{{ config('services.salesforce.redirect_uri') }}</code>.
                                Hasta que eso ocurra, la web seguirá funcionando y este ranking quedará pendiente de conexión.
                            </div>
                        @endif
                    @endif
                @endauth

                <div class="flex flex-col gap-6">
                    @include('leaderboard.partials.vehicle-section', ['leaderboard' => $hotLeaderboard, 'emptyDescription' => $emptyDescription, 'demoMode' => $demoMode])
                    <div id="coches-frios" class="scroll-mt-28">
                        @include('leaderboard.partials.vehicle-section', ['leaderboard' => $coldLeaderboard, 'emptyDescription' => $emptyDescription, 'demoMode' => $demoMode])
                    </div>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="isImageOpen && imageUrl"
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
                        @click="zoomIn()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Aumentar zoom"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <p x-show="imageTitle" class="mt-4 text-center text-sm font-medium text-white/80" x-text="imageTitle"></p>
            </div>
        </div>
    </section>

    <script>
        window.setTimeout(() => window.location.reload(), 600000);
    </script>
@endsection
