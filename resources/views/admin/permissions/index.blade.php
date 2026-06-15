@extends('layouts.app')

@section('content')
    @php
        $selectedPermissionKeys = collect($selectedPermissionKeys ?? []);
        $baseQuery = request()->except(['target_type', 'target_user_id', 'target_role']);
        $targetUrl = function (array $params) use ($baseQuery): string {
            return route('admin.permissions.index', array_merge($baseQuery, $params));
        };
    @endphp

    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        @if ($missingTables ?? false)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                La base de datos todavía no tiene la estructura de permisos completa. Ejecuta las migraciones pendientes para poder asignar permisos.
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Administración</span>
                    <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Permisos</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Selecciona un usuario o grupo y marca qué herramientas de administración puede exactamente usar.
                    </p>
                </div>

                <div class="rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 px-5 py-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Permisos definidos</p>
                    <p class="mt-2 text-3xl font-bold text-brand-secondary">{{ $permissions->count() }}</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="space-y-6">
                    <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Grupos</p>
                                <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">Grupos por rol</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-brand-secondary/70">
                                    Cada grupo representa un rol real del sistema. Al elegir uno, podrás activar o quitar permisos para todo ese rol.
                                </p>
                            </div>
                            <span class="inline-flex flex-nowrap items-center gap-1 whitespace-nowrap rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                                {{ $groups->total() }} grupos
                            </span>
                        </div>

                        <div class="mt-5">
                            <label for="group-search" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">
                                Buscar grupo
                            </label>
                            <input
                                id="group-search"
                                type="search"
                                autocomplete="off"
                                placeholder="Escribe un rol o parte del nombre..."
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                data-permissions-filter="groups"
                            >
                        </div>

                        <div class="mt-5 overflow-hidden rounded-[1.5rem] border border-brand-secondary/10 bg-white shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.14em] text-brand-secondary/45">
                                        <tr>
                                            <th class="px-4 py-3">Grupo</th>
                                            <th class="px-4 py-3">Usuarios</th>
                                            <th class="px-4 py-3">Permisos</th>
                                            <th class="px-4 py-3 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100" data-permissions-filter-body="groups">
                                        @forelse ($groups as $group)
                                            @php
                                                $groupKey = (string) $group->role_key;
                                                $groupLabel = \App\Models\User::extraRoleLabels()[$groupKey] ?? $groupKey;
                                                $groupPermissionCount = (int) ($groupGrantCounts[$groupKey] ?? 0);
                                                $isSelected = data_get($selectedTarget, 'type') === 'group' && data_get($selectedTarget, 'role') === $groupKey;
                                            @endphp
                                            <tr class="{{ $isSelected ? 'bg-brand-primary/5' : 'hover:bg-slate-50' }}" data-permissions-filter-row="groups" data-filter-text="{{ strtolower($groupLabel . ' ' . $groupKey) }}">
                                                <td class="px-4 py-4">
                                                    <div class="font-semibold text-brand-secondary">{{ $groupLabel }}</div>
                                                    <div class="mt-1 text-xs text-brand-secondary/55">{{ $groupKey }}</div>
                                                </td>
                                                <td class="px-4 py-4 text-brand-secondary">{{ (int) $group->users_count }}</td>
                                                <td class="px-4 py-4 text-brand-secondary">{{ $groupPermissionCount }}</td>
                                                <td class="px-4 py-4 text-right">
                                                    <a href="{{ $targetUrl(['target_type' => 'group', 'target_role' => $groupKey]) }}"
                                                        class="inline-flex items-center rounded-full bg-brand-primary px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                        Seleccionar
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500" data-permissions-filter-empty="groups">
                                                    Todavía no hay usuarios con `extra_role` asignado.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($groups->hasPages())
                                <div class="border-t border-slate-200 px-4 py-3">
                                    {{ $groups->links() }}
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Usuarios</p>
                                <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">Usuarios concretos</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-brand-secondary/70">
                                    Selecciona una persona concreta para ajustar permisos que solo afecten a ese usuario.
                                </p>
                            </div>
                            <span class="inline-flex flex-nowrap items-center gap-1 whitespace-nowrap rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                                {{ $users->total() }} usuarios
                            </span>
                        </div>

                        <div class="mt-5">
                            <label for="user-search" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/45">
                                Buscar usuario
                            </label>
                            <input
                                id="user-search"
                                type="search"
                                autocomplete="off"
                                placeholder="Escribe un nombre, email o rol..."
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                data-permissions-filter="users"
                            >
                        </div>

                        <div class="mt-5 overflow-hidden rounded-[1.5rem] border border-brand-secondary/10 bg-white shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.14em] text-brand-secondary/45">
                                        <tr>
                                            <th class="px-4 py-3">Usuario</th>
                                            <th class="px-4 py-3">Rol</th>
                                            <th class="px-4 py-3">Permisos</th>
                                            <th class="px-4 py-3 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100" data-permissions-filter-body="users">
                                        @forelse ($users as $user)
                                            @php
                                                $userPermissionCount = (int) ($userGrantCounts[$user->id] ?? 0);
                                                $isSelected = data_get($selectedTarget, 'type') === 'user' && (int) data_get($selectedTarget, 'id') === $user->id;
                                                $userRoleLabel = $user->extra_role ? (\App\Models\User::extraRoleLabels()[$user->extra_role] ?? $user->extra_role) : 'Sin rol extra';
                                            @endphp
                                            <tr class="{{ $isSelected ? 'bg-brand-primary/5' : 'hover:bg-slate-50' }}" data-permissions-filter-row="users" data-filter-text="{{ strtolower($user->name . ' ' . $user->email . ' ' . $userRoleLabel) }}">
                                                <td class="px-4 py-4">
                                                    <div class="font-semibold text-brand-secondary">{{ $user->name }}</div>
                                                    <div class="mt-1 text-xs text-brand-secondary/55">{{ $user->email }}</div>
                                                </td>
                                                <td class="px-4 py-4 text-brand-secondary">{{ $userRoleLabel }}</td>
                                                <td class="px-4 py-4 text-brand-secondary">{{ $userPermissionCount }}</td>
                                                <td class="px-4 py-4 text-right">
                                                    <a href="{{ $targetUrl(['target_type' => 'user', 'target_user_id' => $user->id]) }}"
                                                        class="inline-flex items-center rounded-full bg-brand-primary px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                        Seleccionar
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500" data-permissions-filter-empty="users">
                                                    No hay usuarios para mostrar.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($users->hasPages())
                                <div class="border-t border-slate-200 px-4 py-3">
                                    {{ $users->links() }}
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <aside id="target-panel" class="lg:sticky lg:top-6">
                    @if ($selectedTarget)
                        <form method="POST" action="{{ route('admin.permissions.targets.sync') }}" class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="target_type" value="{{ $selectedTarget['type'] }}">
                            @if ($selectedTarget['type'] === 'user')
                                <input type="hidden" name="target_user_id" value="{{ $selectedTarget['id'] }}">
                            @else
                                <input type="hidden" name="target_role" value="{{ $selectedTarget['role'] }}">
                            @endif

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">
                                        {{ $selectedTarget['type'] === 'user' ? 'Usuario seleccionado' : 'Grupo seleccionado' }}
                                    </p>
                                    <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">{{ $selectedTarget['label'] }}</h2>
                                    <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                                        {{ $selectedTarget['description'] }}
                                    </p>
                                </div>

                                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                                    {{ $selectedTarget['meta'] }}
                                </span>
                            </div>

                            <div class="mt-5 rounded-[1.5rem] border border-brand-primary/10 bg-white p-4 shadow-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Acciones permitidas</p>
                                        <p class="mt-1 text-sm text-brand-secondary/65">
                                            Marca las herramientas de /admin que quieres conceder a este objetivo.
                                        </p>
                                    </div>
                                    <span class="inline-flex flex-nowrap items-center gap-1 whitespace-nowrap rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-primary">
                                        {{ $selectedPermissionKeys->count() }} activas
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $isChecked = $selectedPermissionKeys->contains($permission['key']);
                                        @endphp
                                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-brand-primary/20 hover:bg-brand-primary/5">
                                            <input type="checkbox" name="permission_keys[]" value="{{ $permission['key'] }}" @checked($isChecked)
                                                class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-semibold text-brand-secondary">{{ $permission['label'] }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-brand-secondary/60">{{ $permission['description'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs leading-5 text-brand-secondary/60">
                                    Guardarás una lista completa: lo marcado queda activo y lo desmarcado se quita.
                                </p>

                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-full bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500 shadow-sm">
                            <p class="font-semibold text-brand-secondary">Selecciona un usuario o grupo</p>
                            <p class="mt-2 leading-6">
                                El panel de la derecha te mostrará las acciones concretas que puedes activar o retirar para ese objetivo.
                            </p>
                        </div>
                    @endif
                </aside>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const normalize = (value) => (value || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();

            const bindFilter = (scope) => {
                const input = document.querySelector(`[data-permissions-filter="${scope}"]`);
                const rows = Array.from(document.querySelectorAll(`[data-permissions-filter-row="${scope}"]`));
                const empty = document.querySelector(`[data-permissions-filter-empty="${scope}"]`);

                if (!input || rows.length === 0) {
                    return;
                }

                const applyFilter = () => {
                    const query = normalize(input.value);
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const haystack = normalize(row.dataset.filterText || row.textContent);
                        const match = query === '' || haystack.includes(query);

                        row.classList.toggle('hidden', !match);
                        if (match) {
                            visibleCount += 1;
                        }
                    });

                    if (empty) {
                        empty.classList.toggle('hidden', visibleCount !== 0 || query === '');
                    }
                };

                input.addEventListener('input', applyFilter);
                applyFilter();
            };

            bindFilter('groups');
            bindFilter('users');
        })();
    </script>
@endsection
