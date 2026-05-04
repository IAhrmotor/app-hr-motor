@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    $navTitle = 'Reseñas';
    $starValue = fn ($value) => max(0, min(5, (int) round((float) $value)));
@endphp

@section('title', 'Reseñas')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">Reseñas</h1>
                <p class="mt-2 max-w-3xl text-sm text-gray-600">
                    Visor centralizado de reseñas de Google Business Profile para todas las delegaciones, con métricas
                    mensuales, historial y respuestas desde la app.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if (auth()->user()?->role === \App\Models\User::ROLE_ADMIN || auth()->user()?->role === \App\Models\User::ROLE_MANAGER)
                    <a href="{{ route('google-business-profile.connect') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-brand-primary/20 bg-brand-primary/5 px-4 py-2 text-sm font-semibold text-brand-primary transition hover:bg-brand-primary/10">
                        Conectar Google
                    </a>
                @endif
                <form method="POST" action="{{ route('reviews.refresh') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Sincronizar ahora
                    </button>
                </form>
                <a href="{{ route('reviews.reports') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Informes mensuales
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if (! $connection)
            <div class="mb-8 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Aun no hay conexion activa con Google Business Profile. Cuando se autorice la cuenta,
                la pagina empezara a guardar el historial y a sincronizar las reseñas automaticamente.
            </div>
        @endif

        <div x-data="{ open: false }" class="mb-8 rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-brand-secondary">Campana de reseñas</p>
                    <p class="text-xs text-gray-500">Las ultimas reseñas sin responder se muestran aqui para actuar rapido.</p>
                </div>
                <button type="button" @click="open = !open"
                    class="inline-flex cursor-pointer items-center gap-2 self-start rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    {{ $latestUnanswered->count() }} sin responder
                </button>
            </div>

            <div x-show="open" x-cloak class="grid gap-3 px-5 py-4 md:grid-cols-2 xl:grid-cols-4">
                @forelse ($latestUnanswered as $review)
                    @if ($review->dealership_id)
                        <a href="{{ route('reviews.show', $review->dealership_id) }}"
                            class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition hover:border-brand-primary/20 hover:bg-brand-primary/5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-brand-secondary">
                                        {{ $review->dealership?->name ?? $review->location_title ?? 'Delegacion sin asignar' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $review->reviewer_name ?? 'Cliente anónimo' }}</p>
                                </div>
                                <span class="rounded-full bg-red-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-red-700">
                                    {{ $review->rating ?? 0 }}★
                                </span>
                            </div>
                            <p class="mt-3 line-clamp-3 text-sm text-gray-600">{{ $review->comment ?? 'Sin texto de reseña.' }}</p>
                        </a>
                    @else
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-brand-secondary">
                                        {{ $review->dealership?->name ?? $review->location_title ?? 'Delegacion sin asignar' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $review->reviewer_name ?? 'Cliente anónimo' }}</p>
                                </div>
                                <span class="rounded-full bg-red-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-red-700">
                                    {{ $review->rating ?? 0 }}★
                                </span>
                            </div>
                            <p class="mt-3 line-clamp-3 text-sm text-gray-600">{{ $review->comment ?? 'Sin texto de reseña.' }}</p>
                        </div>
                    @endif
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-4">
                        No hay reseñas pendientes de respuesta.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Reseñas totales</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['total_reviews']) }}</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Media general</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['average_rating'], 2) }}</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Reseñas este mes</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['monthly_reviews']) }}</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Sin responder</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['unanswered_reviews']) }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-4 xl:grid-cols-3">
            @forelse ($dealershipSummaries as $summary)
                @php
                    $dealership = $summary['dealership'];
                    $avg = max(0, min(5, (float) $summary['average_rating']));
                    $monthlyAvg = max(0, min(5, (float) $summary['monthly_average_rating']));
                @endphp
                <a href="{{ route('reviews.show', $dealership) }}"
                    class="group rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold text-brand-secondary">{{ $dealership->name }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ $dealership->google_business_profile_location_title ?? 'Sin vincular' }}</p>
                        </div>
                        <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                            {{ $summary['total_reviews'] }} total
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Media actual</span>
                            <span class="font-semibold text-brand-secondary">{{ number_format($avg, 2) }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $starValue($avg) ? 'text-amber-400' : 'text-gray-200' }}">★</span>
                            @endfor
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Media este mes</span>
                            <span class="font-semibold text-brand-secondary">{{ number_format($monthlyAvg, 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Sin responder</span>
                            <span class="font-semibold text-brand-secondary">{{ $summary['unanswered_reviews'] }}</span>
                        </div>
                    </div>
                </a>
            @empty
                @if ($locationSummaries->isEmpty())
                    <div class="rounded-3xl border border-dashed border-gray-200 bg-white p-8 text-sm text-gray-500 xl:col-span-3">
                        Aun no hay delegaciones vinculadas con reseñas.
                    </div>
                @endif
            @endforelse
        </div>

        @if ($dealershipSummaries->isEmpty() && $locationSummaries->isNotEmpty())
            <div class="mt-10">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-brand-secondary">Ubicaciones de Google</h2>
                    <p class="text-sm text-gray-500">Mostramos las ubicaciones reales de Google Business Profile mientras no haya delegaciones locales enlazadas.</p>
                </div>

                <div class="grid gap-4 xl:grid-cols-3">
                    @foreach ($locationSummaries as $summary)
                        @php
                            $avg = max(0, min(5, (float) $summary['average_rating']));
                            $monthlyAvg = max(0, min(5, (float) $summary['monthly_average_rating']));
                        @endphp
                        <a href="{{ route('reviews.location', $summary['key']) }}"
                            class="group rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:shadow-md">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-lg font-semibold text-brand-secondary">{{ $summary['location_title'] }}</p>
                                    <p class="mt-1 text-sm text-gray-500">Google location</p>
                                </div>
                                <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                                    {{ $summary['total_reviews'] }} total
                                </span>
                            </div>

                            <div class="mt-5 space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Media actual</span>
                                    <span class="font-semibold text-brand-secondary">{{ number_format($avg, 2) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $starValue($avg) ? 'text-amber-400' : 'text-gray-200' }}">★</span>
                                    @endfor
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Media este mes</span>
                                    <span class="font-semibold text-brand-secondary">{{ number_format($monthlyAvg, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Sin responder</span>
                                    <span class="font-semibold text-brand-secondary">{{ $summary['unanswered_reviews'] }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10 rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-brand-secondary">Filtros y actividad reciente</h2>
                    <p class="text-sm text-gray-500">Busca por texto, delegacion, estado o puntuacion.</p>
                </div>

                <form method="GET" class="grid gap-3 md:grid-cols-4">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar..."
                        class="rounded-xl border-gray-200 text-sm">
                    <select name="dealership_id" class="rounded-xl border-gray-200 text-sm">
                        <option value="">Todas las delegaciones</option>
                        @foreach ($dealerships as $dealership)
                            <option value="{{ $dealership->id }}" @selected((string) ($filters['dealership_id'] ?? '') === (string) $dealership->id)>{{ $dealership->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-xl border-gray-200 text-sm">
                        <option value="">Todas</option>
                        <option value="answered" @selected(($filters['status'] ?? '') === 'answered')>Respondidas</option>
                        <option value="unanswered" @selected(($filters['status'] ?? '') === 'unanswered')>Sin responder</option>
                    </select>
                    <select name="sort" class="rounded-xl border-gray-200 text-sm">
                        <option value="">Mas recientes</option>
                        <option value="rating_desc" @selected(($filters['sort'] ?? '') === 'rating_desc')>Mejor valoradas</option>
                        <option value="rating_asc" @selected(($filters['sort'] ?? '') === 'rating_asc')>Peor valoradas</option>
                    </select>
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" class="cursor-pointer rounded-xl bg-brand-primary px-4 py-2 text-sm font-semibold text-white">
                            Aplicar filtros
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Delegacion</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Puntuacion</th>
                            <th class="px-5 py-3">Reseña</th>
                            <th class="px-5 py-3">Respuesta</th>
                            <th class="px-5 py-3">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($latestReviews as $review)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-brand-secondary">
                                    {{ $review->dealership?->name ?? $review->location_title ?? 'Sin asignar' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ $review->reviewer_name ?? 'Anónimo' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    <span class="font-semibold text-brand-secondary">{{ $review->rating ?? 0 }}</span>/5
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    <p class="line-clamp-2 max-w-xl">{{ $review->comment ?? 'Sin texto' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    @if ($review->reply_comment)
                                        <p class="line-clamp-2 max-w-xl">{{ $review->reply_comment }}</p>
                                    @else
                                        <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Sin responder</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-500">
                                    {{ $review->review_created_at?->format('d/m/Y H:i') ?? $review->created_at?->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">
                                    No hay reseñas con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 60000);
    </script>
@endsection
