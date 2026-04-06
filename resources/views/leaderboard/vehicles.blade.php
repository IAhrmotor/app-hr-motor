@extends('layouts.app')

@section('content')
    <section
        x-data="{
            isVehicleImageOpen: false,
            isSyncing: false,
            vehicleImageSrc: null,
            vehicleImageAlt: '',
            vehicleImageTitle: '',
            openVehicleImage(payload) {
                this.vehicleImageSrc = payload.src;
                this.vehicleImageAlt = payload.alt ?? '';
                this.vehicleImageTitle = payload.title ?? '';
                this.isVehicleImageOpen = true;
            },
        }"
        @open-vehicle-image.window="openVehicleImage($event.detail)"
        @keydown.escape.window="isVehicleImageOpen = false"
        class="py-10 sm:py-14"
    >
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
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
                                    Pendiente de autorizar la conexión en Salesforce
                                @else
                                    Configuración de Salesforce pendiente
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-brand-secondary/60">
                                @if ($connection?->last_synced_at)
                                    Última sincronización: {{ $connection->last_synced_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                @elseif ($salesforceConfigReady)
                                    La app está preparada, pero aún no se ha completado el OAuth.
                                @else
                                    Faltan credenciales o la URL de callback en este entorno.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

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
                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
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

                                <form method="POST" action="{{ route('leaderboard.sync') }}" @submit="isSyncing = true">
                                    @csrf
                                    <button type="submit"
                                        @disabled(! $connection || ! $leaderboardTablesReady)
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-brand-secondary/15 bg-white px-5 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                                        :disabled="isSyncing || {{ (! $connection || ! $leaderboardTablesReady) ? 'true' : 'false' }}">
                                        <svg x-cloak x-show="isSyncing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 0 0-7-7V2z"></path>
                                        </svg>
                                        <span x-text="isSyncing ? 'Sincronizando...' : 'Sincronizar ahora'"></span>
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
                    @include('leaderboard.partials.vehicle-section', ['leaderboard' => $hotLeaderboard, 'emptyDescription' => $emptyDescription])
                    <div id="coches-frios" class="scroll-mt-28">
                        @include('leaderboard.partials.vehicle-section', ['leaderboard' => $coldLeaderboard, 'emptyDescription' => $emptyDescription])
                    </div>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="isSyncing"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 px-6 py-8 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-[2rem] border border-white/60 bg-white/95 p-7 text-center shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.18),rgba(255,255,255,0.95))] ring-1 ring-brand-primary/10">
                    <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-brand-primary/20 border-t-brand-primary"></div>
                </div>
                <h2 class="mt-5 text-xl font-semibold text-brand-secondary">Recargando rankings</h2>
                <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                    Estamos sincronizando Salesforce y actualizando los datos. Esta pantalla se cerrará sola al terminar.
                </p>
            </div>
        </div>

        <div
            x-cloak
            x-show="isVehicleImageOpen && vehicleImageSrc"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-6 py-8 backdrop-blur-sm"
            @click.self="isVehicleImageOpen = false"
        >
            <div class="relative w-full max-w-5xl">
                <button
                    type="button"
                    @click="isVehicleImageOpen = false"
                    class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                    aria-label="Cerrar imagen ampliada"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 shadow-2xl">
                    <img
                        :src="vehicleImageSrc"
                        :alt="vehicleImageAlt"
                        class="max-h-[80vh] w-full object-contain bg-slate-900"
                    >
                </div>

                <p x-show="vehicleImageTitle" class="mt-4 text-center text-sm font-medium text-white/80" x-text="vehicleImageTitle"></p>
            </div>
        </div>
    </section>

    <script>
        window.setTimeout(() => window.location.reload(), 600000);
    </script>
@endsection
