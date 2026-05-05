@extends('layouts.app')

@section('title', $dealership->name . ' | Reseñas')

@section('content')
    @php
        $maxMonthReviews = max(1, (int) $snapshots->max('monthly_reviews'));
        $maxAverage = max(1, (float) $snapshots->max('monthly_average_rating'));
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('reviews.index') }}" class="text-sm font-semibold text-brand-primary">Volver a reseñas</a>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">{{ $dealership->name }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $dealership->google_business_profile_location_title ?? 'Delegación sin vincular' }}
                </p>
            </div>

            <form method="POST" action="{{ $dealership->id ? route('reviews.refresh', $dealership) : route('reviews.refresh') }}" data-review-sync-loader-form>
                @csrf
                <button
                    type="submit"
                    data-review-sync-loader-button
                    data-review-sync-loader-default="Sincronizar delegación"
                    data-review-sync-loader-loading="Sincronizando..."
                    class="inline-flex w-full cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-4 py-2 text-sm font-semibold text-white sm:w-auto">
                    <svg data-review-sync-loader-icon xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-1.636 10.26a9 9 0 11-2.867-9.668L21 9.348" />
                    </svg>
                    <span data-review-sync-loader-label>Sincronizar delegación</span>
                </button>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Reseñas totales</p>
                <p class="mt-3 text-3xl font-bold text-brand-secondary">{{ number_format($stats['total_reviews']) }}</p>
            </div>
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Media actual</p>
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

        <div class="mt-8 grid gap-6 xl:grid-cols-3">
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-brand-secondary">Evolución histórica</h2>
                        <p class="text-sm text-gray-500">Comparativa mensual desde el primer registro guardado.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($snapshots as $snapshot)
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-600">{{ $snapshot->snapshot_month?->format('m/Y') }}</span>
                                <span class="text-gray-500">{{ $snapshot->monthly_reviews }} reseñas, media {{ number_format((float) $snapshot->monthly_average_rating, 2) }}</span>
                            </div>
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-8 rounded-full bg-gray-100">
                                    <div class="h-3 rounded-full bg-brand-primary" style="width: {{ $maxMonthReviews ? min(100, ($snapshot->monthly_reviews / $maxMonthReviews) * 100) : 0 }}%"></div>
                                </div>
                                <div class="col-span-4 rounded-full bg-gray-100">
                                    <div class="h-3 rounded-full bg-amber-400" style="width: {{ $maxAverage ? min(100, ((float) $snapshot->monthly_average_rating / $maxAverage) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                            Aún no hay snapshots mensuales para esta delegación.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-brand-secondary">Resumen mensual</h2>
                <div class="mt-4 space-y-4">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Media general</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['average_rating'], 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Media este mes</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['monthly_average_rating'], 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reseñas sin responder</p>
                        <p class="mt-1 text-2xl font-bold text-brand-secondary">{{ number_format($stats['unanswered_reviews']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-brand-secondary">Reseñas y respuestas</h2>
                <p class="text-sm text-gray-500">Responde desde aquí y la réplica se enviará a Google Business Profile.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <div class="p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-4xl">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-primary/10 text-sm font-bold text-brand-primary">
                                        {{ strtoupper(substr($review->reviewer_name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-secondary">{{ $review->reviewer_name ?? 'Anónimo' }}</p>
                                        <p class="text-xs text-gray-500">{{ $review->review_created_at?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                        {{ $review->rating ?? 0 }} / 5
                                    </span>
                                </div>

                                <p class="mt-4 text-sm leading-6 text-gray-700">{{ $review->comment ?? 'Sin texto de reseña.' }}</p>

                                @if ($review->reply_comment)
                                    <div class="mt-4 rounded-2xl border border-brand-primary/10 bg-brand-primary/5 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-primary">Respuesta publicada</p>
                                        <p class="mt-2 text-sm leading-6 text-gray-700">{{ $review->reply_comment }}</p>
                                    </div>
                                @else
                                    <div class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4">
                                        <p class="text-sm text-gray-500">Esta reseña aún no tiene respuesta.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="w-full lg:max-w-md">
                                @if ($review->reply_comment)
                                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                        <p class="text-sm font-semibold text-emerald-700">Ya respondida</p>
                                        <p class="mt-2 text-sm leading-6 text-emerald-900/80">
                                            Esta reseña ya tiene respuesta publicada en Google Business Profile.
                                        </p>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('reviews.reply', $review) }}" class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                        @csrf
                                        <label class="mb-2 block text-sm font-semibold text-brand-secondary">Responder</label>
                                        <textarea name="comment" rows="4" class="w-full rounded-xl border-gray-200 text-sm" placeholder="Escribe la respuesta para Google"></textarea>
                                        <button type="submit" class="mt-3 w-full cursor-pointer rounded-xl bg-brand-primary px-4 py-2 text-sm font-semibold text-white">
                                            Publicar respuesta
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">
                        No hay reseñas para esta delegación.
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
            <h2 class="mt-5 text-xl font-semibold text-brand-secondary">Sincronizando reseñas</h2>
            <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                Estamos actualizando la delegación y su historial. Esta pantalla se cerrará sola al terminar.
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
