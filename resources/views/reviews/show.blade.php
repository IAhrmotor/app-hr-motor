@extends('layouts.app')

@section('title', $dealership->name . ' | Rese&ntilde;as')

@section('content')
    @php
        $historicalSeries = collect($historicalSeries ?? []);
        $historicalHasData = $historicalSeries->contains(fn (array $point): bool => (bool) ($point['has_data'] ?? false));
        $chartWidth = 760;
        $chartHeight = 300;
        $chartPadding = [
            'top' => 20,
            'right' => 18,
            'bottom' => 54,
            'left' => 48,
        ];
        $plotWidth = $chartWidth - $chartPadding['left'] - $chartPadding['right'];
        $plotHeight = $chartHeight - $chartPadding['top'] - $chartPadding['bottom'];
        $chartPoints = $historicalSeries->values()->map(function (array $point, int $index) use ($historicalSeries, $chartPadding, $plotWidth, $plotHeight): array {
            $count = max(1, $historicalSeries->count());
            $x = $chartPadding['left'] + ($count === 1 ? $plotWidth / 2 : ($plotWidth * $index) / ($count - 1));
            $average = (float) ($point['average'] ?? 0);
            $normalized = max(0, min(5, $average)) / 5;
            $y = $chartPadding['top'] + ($plotHeight - ($plotHeight * $normalized));
            $labelX = $x;
            $labelAnchor = 'middle';

            if ($index === 0) {
                $labelX = $x + 12;
                $labelAnchor = 'start';
            } elseif ($index === $count - 1) {
                $labelX = $x - 12;
                $labelAnchor = 'end';
            }

            return $point + [
                'x' => $x,
                'y' => $y,
                'label_x' => $labelX,
                'label_anchor' => $labelAnchor,
                'formatted_average' => number_format($average, 2),
            ];
        });
        $linePoints = $chartPoints->map(fn (array $point): string => number_format((float) $point['x'], 2, '.', '') . ',' . number_format((float) $point['y'], 2, '.', ''))->implode(' ');
        $gridValues = [5, 4, 3, 2, 1, 0];
        $starFillWidth = fn ($value) => max(0, min(100, ((float) $value / 5) * 100));
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-3xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-900 shadow-sm">
                <p class="font-semibold">Sincronizaci&oacute;n solicitada</p>
                <p class="mt-1 leading-6">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900 shadow-sm">
                <p class="font-semibold">No se ha podido iniciar la sincronizaci&oacute;n</p>
                <p class="mt-1 leading-6">{{ session('error') }}</p>
            </div>
        @endif
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('reviews.index') }}" class="text-sm font-semibold text-brand-primary">Volver a rese&ntilde;as</a>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">{{ $dealership->name }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $dealership->google_business_profile_location_title ?? 'Delegaci&oacute;n sin vincular' }}
                </p>
            </div>

            <form method="POST" action="{{ $dealership->id ? route('reviews.refresh', $dealership) : route('reviews.refresh') }}" data-review-sync-loader-form>
                @csrf
                <button
                    type="submit"
                    data-review-sync-loader-button
                    data-review-sync-loader-default="Sincronizar delegaci&oacute;n"
                    data-review-sync-loader-loading="Sincronizando..."
                    class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-4 py-2 text-sm font-semibold text-white sm:w-auto"
                >
                    <svg data-review-sync-loader-icon xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-1.636 10.26a9 9 0 11-2.867-9.668L21 9.348" />
                    </svg>
                    <span data-review-sync-loader-label>Sincronizar delegaci&oacute;n</span>
                </button>
                <p class="mt-2 max-w-sm text-xs leading-5 text-gray-500">
                    La sincronizaci&oacute;n se procesa en segundo plano. Si hay muchas rese&ntilde;as, puede tardar unos minutos en reflejarse.
                </p>
            </form>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Rese&ntilde;as totales</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['total_reviews']) }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Media actual</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['average_rating'], 2) }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <div class="relative inline-flex text-2xl leading-none" aria-label="ValoraciÃ³n media en Google Maps {{ number_format($stats['average_rating'], 2) }} de 5">
                        <div class="flex text-gray-200" aria-hidden="true">
                            <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                        </div>
                        <div class="absolute inset-0 overflow-hidden whitespace-nowrap text-amber-400" aria-hidden="true" style="width: {{ $starFillWidth($stats['average_rating'] ?? 0) }}%;">
                            <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-brand-secondary">{{ number_format($stats['average_rating'], 2) }}/5</span>
                </div>
            </div>
            <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Rese&ntilde;as este mes</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['monthly_reviews']) }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Sin responder</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['unanswered_reviews']) }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
            <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-brand-secondary">Evoluci&oacute;n hist&oacute;rica</h2>
                        <p class="text-sm text-gray-500">Media mensual de los &uacute;ltimos seis meses, incluyendo el actual.</p>
                    </div>
                </div>

                @if ($historicalHasData)
                    <div class="mt-6">
                        <div class="overflow-hidden rounded-[1.5rem] border border-gray-100 bg-gradient-to-b from-gray-50 to-white p-4 sm:p-5">
                            <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-auto w-full" role="img" aria-label="Evoluci&oacute;n hist&oacute;rica de la media mensual de rese&ntilde;as">
                                <title>Evoluci&oacute;n hist&oacute;rica de la media mensual de rese&ntilde;as</title>
                                <desc>Gr&aacute;fica de l&iacute;neas con la media mensual de las &uacute;ltimas seis mensualidades.</desc>

                                @foreach ($gridValues as $gridValue)
                                    @php
                                        $gridY = $chartPadding['top'] + ($plotHeight - ($plotHeight * ($gridValue / 5)));
                                    @endphp
                                    <line x1="{{ $chartPadding['left'] }}" y1="{{ $gridY }}" x2="{{ $chartWidth - $chartPadding['right'] }}" y2="{{ $gridY }}" stroke="#e5e7eb" stroke-dasharray="4 5" stroke-width="1" />
                                    <text x="16" y="{{ $gridY + 4 }}" fill="#6b7280" font-size="12" font-weight="600">{{ $gridValue }}</text>
                                @endforeach

                                <line x1="{{ $chartPadding['left'] }}" y1="{{ $chartPadding['top'] + $plotHeight }}" x2="{{ $chartWidth - $chartPadding['right'] }}" y2="{{ $chartPadding['top'] + $plotHeight }}" stroke="#d1d5db" stroke-width="1.25" />

                                <polyline
                                    fill="none"
                                    stroke="#c2410c"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="4"
                                    points="{{ $linePoints }}"
                                />

                                @foreach ($chartPoints as $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6" fill="#fff" stroke="#c2410c" stroke-width="3" />
                                    <text
                                        x="{{ $point['x'] }}"
                                        y="{{ max(16, $point['y'] - 14) }}"
                                        fill="#111827"
                                        font-size="12"
                                        font-weight="700"
                                        text-anchor="middle"
                                    >
                                        {{ $point['formatted_average'] }}
                                    </text>
                                    <text
                                        x="{{ $point['label_x'] }}"
                                        y="{{ $chartHeight - 18 }}"
                                        fill="#4b5563"
                                        font-size="12"
                                        font-weight="600"
                                        text-anchor="{{ $point['label_anchor'] }}"
                                    >
                                        {{ $point['label'] }}
                                    </text>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        A&uacute;n no hay datos suficientes para mostrar la evoluci&oacute;n hist&oacute;rica de esta delegaci&oacute;n.
                    </div>
                @endif
            </div>

            <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-brand-secondary">Resumen mensual</h2>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rese&ntilde;as totales</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['total_reviews']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Media actual</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['average_rating'], 2) }}</p>
                        <div class="mt-3 flex items-center gap-2">
                            <div class="relative inline-flex text-2xl leading-none" aria-label="ValoraciÃ³n media en Google Maps {{ number_format($stats['average_rating'], 2) }} de 5">
                                <div class="flex text-gray-200" aria-hidden="true">
                                    <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                                </div>
                                <div class="absolute inset-0 overflow-hidden whitespace-nowrap text-amber-400" aria-hidden="true" style="width: {{ $starFillWidth($stats['average_rating'] ?? 0) }}%;">
                                    <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-brand-secondary">{{ number_format($stats['average_rating'], 2) }}/5</span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rese&ntilde;as este mes</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['monthly_reviews']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sin responder</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['unanswered_reviews']) }}</p>
                    </div>
                </div>
            </div>
        </div>        <div class="mt-8 overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5 sm:px-7">
                <h2 class="text-lg font-semibold text-brand-secondary">Rese&ntilde;as y respuestas</h2>
                <p class="text-sm text-gray-500">Gestiona cada rese&ntilde;a desde aqu&iacute; y env&iacute;a la r&eacute;plica directamente a Google Business Profile.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <div id="review-{{ $review->id }}" class="px-6 py-6 sm:px-7 scroll-mt-6">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-primary/10 text-sm font-bold text-brand-primary ring-1 ring-brand-primary/10">
                                        {{ strtoupper(substr($review->reviewer_name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold text-brand-secondary">{{ $review->reviewer_name ?? 'An&oacute;nimo' }}</p>
                                        <p class="text-xs text-gray-500">{{ $review->review_created_at?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="ml-auto flex items-center gap-2">
                                        <div class="relative inline-flex text-xl leading-none sm:text-2xl">
                                            <div class="flex text-gray-200" aria-hidden="true">
                                                <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                                            </div>
                                            <div class="absolute inset-0 overflow-hidden whitespace-nowrap text-amber-400" aria-hidden="true" style="width: {{ $starFillWidth($review->rating ?? 0) }}%;">
                                                <span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                                    <p class="text-sm leading-7 text-gray-700">{{ $review->comment ?? 'Sin texto de rese&ntilde;a.' }}</p>
                                </div>

                                @if ($review->reply_comment)
                                    <div class="mt-4 rounded-2xl border border-brand-primary/10 bg-brand-primary/5 p-5">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary">Respuesta publicada</p>
                                            <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-brand-primary ring-1 ring-brand-primary/10">Visible en Google</span>
                                        </div>
                                        <p class="mt-3 text-sm leading-7 text-gray-700">{{ $review->reply_comment }}</p>
                                    </div>
                                @else
                                    <div class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4">
                                        <p class="text-sm text-gray-500">Esta rese&ntilde;a a&uacute;n no tiene respuesta.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="w-full xl:sticky xl:top-6 xl:w-[22rem] xl:self-start">
                                @if ($review->reply_comment)
                                    <div class="w-full rounded-[1.5rem] border border-emerald-200 bg-gradient-to-b from-emerald-50 to-white p-4 shadow-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                                Estado
                                            </div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-emerald-500"></div>
                                        </div>
                                        <h3 class="mt-4 text-base font-semibold text-emerald-900">Ya respondida</h3>
                                        <p class="mt-2 text-sm leading-6 text-emerald-900/75">
                                            Esta rese&ntilde;a ya tiene una respuesta publicada en Google Business Profile. No hace falta volver a escribirla aqu&iacute;.
                                        </p>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('reviews.reply', $review) }}" class="w-full rounded-[1.5rem] border border-gray-200 bg-gradient-to-b from-white to-gray-50 p-4 shadow-sm">
                                        @csrf
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-600">
                                                Responder
                                            </div>
                                            <div class="h-2.5 w-2.5 rounded-full bg-brand-primary"></div>
                                        </div>
                                        <h3 class="mt-4 text-base font-semibold text-brand-secondary">Publicar respuesta</h3>
                                        <p class="mt-2 text-sm leading-6 text-gray-500">
                                            Redacta aqu&iacute; una respuesta clara, breve y profesional para el cliente.
                                        </p>
                                        <textarea
                                            name="comment"
                                            rows="7"
                                            class="mt-4 w-full rounded-2xl border-gray-200 px-4 py-4 pt-6 text-sm placeholder:text-gray-400 focus:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary/15"
                                            placeholder="Escribe la respuesta para Google"
                                        ></textarea>
                                        <button type="submit" class="mt-3 inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-brand-primary/95">
                                            Publicar respuesta
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">
                        No hay rese&ntilde;as para esta delegaci&oacute;n.
                    </div>
                @endforelse
            </div>

            @if (method_exists($reviews, 'hasPages') && $reviews->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>

    <div
        id="review-sync-loader"
        class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-6 py-8 opacity-0 backdrop-blur-sm transition-opacity duration-200"
    >
        <div class="w-full max-w-md rounded-[2rem] border border-white/60 bg-white/95 p-7 text-center shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.18),rgba(255,255,255,0.95))] ring-1 ring-brand-primary/10">
                <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-brand-primary/20 border-t-brand-primary"></div>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-brand-secondary">Sincronizando rese&ntilde;as</h2>
            <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                Estamos actualizando la delegaci&oacute;n y su historial. Esta pantalla se cerrar&aacute; sola al terminar.
            </p>
        </div>
    </div>

    <script>
        (() => {
            const overlay = document.getElementById('review-sync-loader');

            document.querySelectorAll('[data-review-sync-loader-form]').forEach((form) => {
                let submitted = false;

                form.addEventListener('submit', (event) => {
                    if (submitted) {
                        return;
                    }

                    submitted = true;
                    event.preventDefault();

                    const button = form.querySelector('[data-review-sync-loader-button]');
                    const label = form.querySelector('[data-review-sync-loader-label]');
                    const icon = form.querySelector('[data-review-sync-loader-icon]');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('opacity-90');
                    }

                    if (label && button?.dataset.reviewSyncLoaderLoading) {
                        label.textContent = button.dataset.reviewSyncLoaderLoading;
                    }

                    if (icon) {
                        icon.classList.add('animate-spin');
                    }

                    if (overlay) {
                        overlay.classList.remove('hidden');

                        requestAnimationFrame(() => {
                            overlay.classList.remove('pointer-events-none', 'opacity-0');
                            overlay.classList.add('flex', 'opacity-100');
                        });
                    }

                    window.setTimeout(() => form.submit(), 80);
                });
            });
        })();
    </script>
@endsection
