<div class="mr-2 flex items-center">
    <x-filament::icon-button
        color="gray"
        :icon="\Filament\Support\Icons\Heroicon::Sun"
        icon-size="lg"
        label="Cambiar a modo claro"
        tooltip="Cambiar a modo claro"
        x-cloak
        x-show="$store.theme === 'dark'"
        x-on:click="$dispatch('theme-changed', 'light')"
    />

    <x-filament::icon-button
        color="gray"
        :icon="\Filament\Support\Icons\Heroicon::Moon"
        icon-size="lg"
        label="Cambiar a modo oscuro"
        tooltip="Cambiar a modo oscuro"
        x-cloak
        x-show="$store.theme !== 'dark'"
        x-on:click="$dispatch('theme-changed', 'dark')"
    />
</div>
