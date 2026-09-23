@php
    $pollingInterval = $this->getPollingInterval();
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Filament\Support\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
                'admin-overview-widget',
            ])
    "
>
    <style>
        .admin-overview-widget .fi-wi-stats-overview-stat-value {
            font-size: 2.5rem;
            font-weight: 700;
        }
    </style>

    {{ $this->content }}
</x-filament-widgets::widget>
