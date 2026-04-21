@php
    $persistedQuery = request()->except(['ajax']);
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
                    <th class="px-6 py-4 text-left">
                        <a href="{{ route('dealerships.index', array_merge($persistedQuery, ['sort' => 'name', 'direction' => $sortDirection('name', $sort, $direction)])) }}"
                            data-dealership-sort-link
                            class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary">
                            <span>Delegación</span>
                        </a>
                    </th>
                    <th class="px-6 py-4 text-left">
                        <a href="{{ route('dealerships.index', array_merge($persistedQuery, ['sort' => 'salesforce_id', 'direction' => $sortDirection('salesforce_id', $sort, $direction)])) }}"
                            data-dealership-sort-link
                            class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary">
                            <span>ID Salesforce</span>
                        </a>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Equipo</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                @forelse ($dealerships as $dealership)
                    <tr class="transition hover:bg-brand-secondary/5">
                        <td class="px-6 py-4 text-sm font-semibold text-brand-secondary">
                            <a href="{{ route('dealerships.show', $dealership) }}" class="flex items-center gap-3 transition hover:opacity-80">
                                @if ($dealership->image_url)
                                    <img src="{{ $dealership->image_url }}" alt="Imagen de {{ $dealership->name }}"
                                        class="h-11 w-11 rounded-xl object-cover ring-1 ring-brand-secondary/10">
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-secondary text-sm font-semibold text-white">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($dealership->name, 0, 2)) }}
                                    </div>
                                @endif
                                <span>{{ $dealership->name }}</span>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $dealership->salesforce_id ?: 'Sin configurar' }}</td>
                        <td class="px-6 py-4 text-sm text-brand-secondary/80">{{ $dealership->users_count }} {{ $dealership->users_count === 1 ? 'usuario' : 'usuarios' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('dealerships.edit', $dealership) }}" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Editar delegación" aria-label="Editar delegación">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.462 3 20l1.538-5.25L16.862 3.487z" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('dealerships.destroy', $dealership) }}" onsubmit="return confirm('¿Seguro que quieres eliminar esta delegación?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-700" title="Eliminar delegación" aria-label="Eliminar delegación">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.1 1.02.17m-1.02-.17L18.16 19.67A2.25 2.25 0 0115.91 21.75H8.09a2.25 2.25 0 01-2.25-2.08L4.77 5.79m14.46 0A48.108 48.108 0 0012 5.25c-2.43 0-4.82.18-7.23.54m14.46 0a48.11 48.11 0 00-14.46 0m9.75-2.04v-.23A1.5 1.5 0 0013.02 2h-2.04a1.5 1.5 0 00-1.5 1.5v.23m5.04 0A49.5 49.5 0 009.48 3.75" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No hay delegaciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($dealerships->hasPages())
    <div class="mt-6" data-dealership-pagination>{{ $dealerships->links() }}</div>
@endif
