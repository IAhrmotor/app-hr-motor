@extends('layouts.app')

@section('content')
    <section class="bg-[radial-gradient(circle_at_top,_rgba(229,26,46,0.14),_transparent_45%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex flex-col gap-6 rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur sm:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-brand-primary">Salesforce</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-brand-secondary sm:text-4xl">
                            Leaderboard comercial
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-brand-secondary/70 sm:text-base">
                            Ranking de comerciales por volumen de ventas sincronizado desde Salesforce cada 10 minutos.
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
                                Ultima sincronizacion: {{ $connection->last_synced_at->format('d/m/Y H:i') }}
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
                        El leaderboard esta en modo preparacion. La pagina ya no falla aunque falten tablas, pero todavia necesitas ejecutar las migraciones para guardar la conexion y las ventas.
                    </div>
                @endif

                @auth
                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                        <div class="flex flex-col gap-3 sm:flex-row">
                            @if ($salesforceConfigReady && $leaderboardTablesReady)
                                <a href="{{ route('salesforce.connect') }}"
                                    class="inline-flex items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:brightness-95">
                                    {{ $connection ? 'Reconectar Salesforce' : 'Conectar Salesforce' }}
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

                        @if (! $leaderboardTablesReady)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                Antes de conectar Salesforce, ejecuta las migraciones de Laravel para crear las tablas nuevas del leaderboard.
                            </div>
                        @elseif (! $salesforceConfigReady)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                La integracion esta en modo espera. La app no falla, pero no intentara conectar ni sincronizar hasta que completes
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_ID</code>,
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_CLIENT_SECRET</code>
                                y
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">SALESFORCE_REDIRECT_URI</code>.
                            </div>
                        @else
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                                En Salesforce Connected App debes autorizar exactamente la misma callback URL que uses aqui en
                                <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs">{{ config('services.salesforce.redirect_uri') }}</code>.
                                Hasta que eso ocurra, la web seguira funcionando y el leaderboard quedara simplemente pendiente de conexion.
                            </div>
                        @endif
                    @endif
                @endauth

                @if ($entries->isEmpty())
                    <div class="rounded-[2rem] border border-dashed border-brand-secondary/15 bg-slate-50 px-6 py-12 text-center text-brand-secondary/75">
                        <p class="text-lg font-semibold text-brand-secondary">Aun no hay datos de ventas</p>
                        <p class="mt-2 text-sm">
                            @if (! $leaderboardTablesReady)
                                Ejecuta primero las migraciones para activar el almacenamiento del leaderboard.
                            @elseif ($connection)
                                Ejecuta una sincronizacion para llenar el ranking.
                            @elseif ($salesforceConfigReady)
                                Completa la autorizacion OAuth en Salesforce y despues ejecuta la primera sincronizacion.
                            @else
                                Completa la configuracion de Salesforce y despues autoriza la conexion.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="grid gap-4 lg:grid-cols-3">
                        @foreach ($entries->take(3) as $entry)
                            <article
                                class="relative overflow-hidden rounded-[1.75rem] border border-brand-secondary/10 bg-white p-6 shadow-[0_18px_35px_rgba(15,23,42,0.07)]">
                                <div class="absolute right-4 top-4 rounded-full bg-brand-secondary px-3 py-1 text-xs font-semibold text-white">
                                    #{{ $entry->ranking_position }}
                                </div>
                                <div class="flex items-center gap-4">
                                    <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                        alt="Avatar de {{ $entry->seller_name }}"
                                        class="h-16 w-16 rounded-2xl object-cover ring-1 ring-brand-secondary/10">
                                    <div>
                                        <p class="text-xl font-semibold text-brand-secondary">{{ $entry->seller_name }}</p>
                                        <p class="text-sm text-brand-secondary/60">
                                            {{ $entry->user?->email ?? ($entry->salesforce_user_id ?: 'Sin vincular con usuario interno') }}
                                        </p>
                                    </div>
                                </div>
                                <p class="mt-6 text-sm uppercase tracking-[0.3em] text-brand-secondary/50">Ventas</p>
                                <p class="mt-2 text-3xl font-semibold text-brand-primary">
                                    {{ number_format((float) $entry->total_sales, 2, ',', '.') }} €
                                </p>
                            </article>
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-[1.75rem] border border-brand-secondary/10 bg-white">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Puesto</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Comercial</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">ID Salesforce</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.24em] text-brand-secondary/55">Ventas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($entries as $entry)
                                        <tr class="transition hover:bg-slate-50/80">
                                            <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">#{{ $entry->ranking_position }}</td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <img src="{{ $entry->user?->avatar_url ?? asset(\App\Models\User::DEFAULT_AVATAR_PATH) }}"
                                                        alt="Avatar de {{ $entry->seller_name }}"
                                                        class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                                    <div>
                                                        <p class="text-sm font-semibold text-brand-secondary">{{ $entry->seller_name }}</p>
                                                        <p class="text-xs text-brand-secondary/55">{{ $entry->user?->email ?? 'Sin usuario interno enlazado' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-brand-secondary/70">{{ $entry->salesforce_user_id ?: 'No informado' }}</td>
                                            <td class="px-6 py-4 text-right text-sm font-semibold text-brand-primary">
                                                {{ number_format((float) $entry->total_sales, 2, ',', '.') }} €
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <script>
        window.setTimeout(() => window.location.reload(), 600000);
    </script>
@endsection
