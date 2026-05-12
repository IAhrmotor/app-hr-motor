@php
    $cards = $cards ?? [];
    $showHeader = $showHeader ?? true;
    $showBackButton = $showBackButton ?? false;
    $backUrl = $backUrl ?? route('reviews.reports');
    $backLabel = $backLabel ?? 'Volver a reseñas';
@endphp

<div class="mx-auto max-w-7xl">
    @if ($showBackButton)
        <div class="mb-6 flex justify-end">
            <a href="{{ $backUrl }}"
                class="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-600 transition hover:border-gray-300 hover:text-gray-800">
                {{ $backLabel }}
            </a>
        </div>
    @endif

    @if ($showHeader)
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary">Marketing</p>
                <h1 class="mt-2 text-3xl font-bold text-brand-secondary">{{ $heading }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ $description }}</p>
            </div>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($cards as $card)
            @if (! empty($card['url']))
                <a href="{{ $card['url'] }}"
                    class="group rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-primary/20 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold text-brand-secondary">{{ $card['title'] }}</p>
                            @if (! empty($card['description']))
                                <p class="mt-1 text-sm text-gray-500">{{ $card['description'] }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold text-brand-primary">
                            {{ $card['cta'] ?? 'Abrir' }}
                        </span>
                    </div>
                </a>
            @else
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold text-brand-secondary">{{ $card['title'] }}</p>
                            @if (! empty($card['description']))
                                <p class="mt-1 text-sm text-gray-500">{{ $card['description'] }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500">
                            {{ $card['cta'] ?? 'Próximamente' }}
                        </span>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
