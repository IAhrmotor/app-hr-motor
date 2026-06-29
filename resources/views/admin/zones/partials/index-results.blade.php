@php
    $persistedQuery = request()->except(['ajax', 'page']);
    $sortDirection = function ($column, $sort, $direction) {
        if ($sort !== $column) {
            return 'asc';
        }

        return $direction === 'asc' ? 'desc' : 'asc';
    };
@endphp

<div class="overflow-hidden rounded-2xl border border-brand-secondary/10">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-brand-secondary/10">
            <thead class="bg-brand-secondary/5">
                <tr>
                    @foreach ([
                        'name' => 'Nombre',
                        'dealerships_count' => 'Delegaciones',
                        'created_at' => 'Creada',
                    ] as $column => $label)
                        <th class="px-6 py-4 text-left">
                            <a href="{{ route('admin.zones.index', array_merge($persistedQuery, ['sort' => $column, 'direction' => $sortDirection($column, $sort, $direction)])) }}"
                                class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary"
                                data-zone-sort-link>
                                <span>{{ $label }}</span>
                            </a>
                        </th>
                    @endforeach
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                @forelse ($zones as $zone)
                    <tr class="align-top transition hover:bg-brand-secondary/5">
                        <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                            {{ $zone->name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                            <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                {{ $zone->dealerships_count ?? $zone->dealerships->count() }} delegaciones
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-brand-secondary/80">
                            {{ $zone->created_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.zones.edit', $zone) }}" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Editar zona" aria-label="Editar zona">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.462 3 20l1.538-5.25L16.862 3.487z" />
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('admin.zones.destroy', $zone) }}" onsubmit="return confirm('Seguro que quieres eliminar esta zona?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-700" title="Eliminar zona" aria-label="Eliminar zona">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.1 1.02.17m-1.02-.17L18.16 19.67A2.25 2.25 0 0115.91 21.75H8.09a2.25 2.25 0 01-2.25-2.08L4.77 5.79m14.46 0A48.108 48.108 0 0012 5.25c-2.43 0-4.82.18-7.23.54m14.46 0a48.11 48.11 0 00-14.46 0m9.75-2.04v-.23A1.5 1.5 0 0013.02 2h-2.04a1.5 1.5 0 00-1.5 1.5v.23m5.04 0A49.5 49.5 0 009.48 3.75" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No hay zonas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($zones->hasPages())
    <div class="mt-6" data-zone-pagination>
        {{ $zones->links() }}
    </div>
@endif
