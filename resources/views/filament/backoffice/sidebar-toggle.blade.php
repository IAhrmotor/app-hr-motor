<div class="mr-2 flex items-center">
    <x-filament::icon-button
        color="gray"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedChevronLeft"
        icon-size="lg"
        label="Contraer menú lateral"
        tooltip="Contraer menú lateral"
        x-cloak
        x-show="$store.sidebar.isOpen"
        x-on:click="$store.sidebar.close()"
    />

    <x-filament::icon-button
        color="gray"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedChevronRight"
        icon-size="lg"
        label="Abrir menú lateral"
        tooltip="Abrir menú lateral"
        x-cloak
        x-show="! $store.sidebar.isOpen"
        x-on:click="$store.sidebar.open()"
    />
</div>
