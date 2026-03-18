@extends('layouts.app')

@section('content')
    <section class="py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex flex-col gap-6 rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur sm:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-brand-primary">Salesforce</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-secondary sm:text-4xl">
                            {{ $title }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-brand-secondary/70 sm:text-base">
                            {{ $description }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary/80">
                        <p class="font-semibold">Estado</p>
                        <p class="mt-1">
                            @if ($connection)
                                Conectado con Salesforce
                            @elseif ($salesforceConfigReady)
                                Pendiente de autorizar la conexion en Salesforce
                            @else
                                Configuracion de Salesforce pendiente
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-brand-secondary/60">
                            @if ($connection?->last_synced_at)
                                Ultima sincronizacion: {{ $connection->last_synced_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            @elseif ($salesforceConfigReady)
                                La app esta preparada, pero aun no se ha completado el OAuth.
                            @else
                                Faltan credenciales o la URL de callback en este entorno.
                            @endif
                        </p>
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
                        El ranking esta en modo preparacion. La pagina ya no falla aunque falten tablas, pero todavia necesitas ejecutar las migraciones para guardar la conexion y los datos de este ranking.
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
                                        Configuracion pendiente
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
                                La integracion esta en modo espera. La app no falla, pero no intentara conectar ni sincronizar hasta que completes
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_ID</code>,
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_SECRET</code>
                                y
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_REDIRECT_URI</code>.
                            </div>
                        @elseif (! $connection)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                En Salesforce Connected App debes autorizar exactamente la misma callback URL que uses aqui en
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">{{ config('services.salesforce.redirect_uri') }}</code>.
                                Hasta que eso ocurra, la web seguira funcionando y este ranking quedara pendiente de conexion.
                            </div>
                        @endif
                    @endif
                @endauth

                @include('leaderboard.partials.section', [
                    'leaderboard' => $leaderboard,
                    'eyebrow' => $eyebrow,
                    'title' => $title,
                    'description' => $description,
                    'metricLabel' => $metricLabel,
                    'metricField' => $metricField,
                    'emptyTitle' => $emptyTitle,
                    'emptyDescription' => $emptyDescription,
                ])
            </div>
        </div>
    </section>

    <script>
        window.setTimeout(() => window.location.reload(), 600000);
    </script>
@endsection
