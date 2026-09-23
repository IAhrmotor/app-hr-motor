<x-filament-panels::page>
    <div class="mx-auto w-full max-w-3xl">
        <x-filament::section>
            <x-slot name="heading">Rankings</x-slot>

            <x-slot name="description">
                Actualiza los rankings de ventas, compras y coches de la aplicación con los datos de Salesforce.
            </x-slot>

            <div class="pt-2">
                <x-filament::button
                    wire:click="syncRankings"
                    wire:loading.attr="disabled"
                    wire:target="syncRankings"
                    icon="heroicon-o-arrow-path"
                >
                    <span wire:loading.remove wire:target="syncRankings">Actualizar rankings</span>
                    <span wire:loading wire:target="syncRankings">Actualizando rankings...</span>
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
