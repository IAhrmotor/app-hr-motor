@php
    $authUser = $authUser ?? auth()->user();
    $visibleRole = app_visible_role($authUser);
    $isAdminViewerMode = $authUser?->role === \App\Models\User::ROLE_ADMIN && app_role_viewer_active($authUser);
    $canManageUsers = $authUser && (
        $isAdminViewerMode
            ? app_role_has_admin_permission($visibleRole, 'users.manage')
            : app_user_has_admin_permission($authUser, 'users.manage')
    );
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
                    @foreach ([
                        'name' => 'Nombre',
                        'email' => 'Correo',
                        'role' => 'Rol',
                        'dealership' => 'Delegación',
                        'is_active' => 'Estado',
                    ] as $column => $label)
                        <th class="px-6 py-4 text-left">
                            <a href="{{ route('users.index', array_merge($persistedQuery, ['sort' => $column, 'direction' => $sortDirection($column, $sort, $direction)])) }}"
                                data-users-sort-link
                                class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70 transition hover:text-brand-secondary">
                                <span>{{ $label }}</span>
                            </a>
                        </th>
                    @endforeach
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.12em] text-brand-secondary/70">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                @forelse ($users as $user)
                    @php
                        $canManageUser = $visibleRole === \App\Models\User::ROLE_ADMIN
                            || ($visibleRole === \App\Models\User::ROLE_MANAGER
                                && $authUser->id !== $user->id
                                && $user->isCommercialLike());
                        $canManageUser = $canManageUser || (
                            $canManageUsers
                            && $user->isCommercialLike()
                        );
                        $isDisabled = $user->isDisabled();
                        $isInvitationExpired = $user->isInvitationExpired();
                        $isPendingActivation = $user->isPendingActivation();
                        $rowClasses = $isDisabled
                            ? 'bg-slate-50/80 transition hover:bg-slate-100/80'
                            : ($user->is_active
                                ? 'transition hover:bg-brand-secondary/5'
                                : ($isInvitationExpired
                                    ? 'bg-red-50/70 transition hover:bg-red-100/70'
                                    : 'bg-amber-50/70 transition hover:bg-amber-100/70'));
                    @endphp
                    <tr class="{{ $rowClasses }} {{ $isDisabled ? 'opacity-75' : '' }}">
                        <td class="px-6 py-4 text-sm font-semibold {{ $isDisabled ? 'text-slate-500' : 'text-brand-secondary' }}">
                            <a href="{{ route('users.show', $user) }}" class="flex items-center gap-3 transition hover:opacity-80">
                                <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10 {{ $isDisabled ? 'grayscale' : '' }}">
                                <span>{{ $user->name }}</span>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm {{ $isDisabled ? 'text-slate-500' : 'text-brand-secondary/80' }}">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm {{ $isDisabled ? 'text-slate-500' : 'text-brand-secondary/80' }}">
                            <span class="inline-flex min-w-[7.25rem] justify-center rounded-full bg-brand-primary/10 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-brand-primary">{{ $user->role_label }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm {{ $isDisabled ? 'text-slate-500' : 'text-brand-secondary/80' }}">{{ $user->resolved_dealership_name ?: 'No aplica' }}</td>
                        <td class="px-6 py-4 text-sm {{ $isDisabled ? 'text-slate-500' : 'text-brand-secondary/80' }}">
                            @if ($isDisabled)
                                <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-slate-200 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Desactivado</span>
                            @elseif ($user->is_active)
                                <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-green-100 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-green-700">Activo</span>
                            @elseif ($isInvitationExpired)
                                <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-red-100 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-red-700">Caducado</span>
                            @else
                                <span class="inline-flex min-w-[6.75rem] justify-center rounded-full bg-amber-100 px-3 py-1 text-center text-xs font-semibold uppercase tracking-wide text-amber-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($canManageUser)
                                <div class="flex justify-end gap-2">
                                    @if ($isDisabled)
                                        <form method="POST" action="{{ route('users.reactivate', $user) }}" data-user-reactivate-form>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 hover:text-emerald-800" title="Reactivar usuario" aria-label="Reactivar usuario">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8 8 0 0 0 4.582 9M4 9h5m11 11v-5h-.581m0 0a8.004 8.004 0 0 1-15.357-2M20 15h-5" />
                                                </svg>
                                            </button>
                                        </form>
                                    @elseif ($isPendingActivation || $isInvitationExpired || $user->must_change_password)
                                        <form method="POST" action="{{ route('users.resend-invitation', $user) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100 hover:text-amber-800" title="Reenviar correo de activacion" aria-label="Reenviar correo de activacion">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 7.5v9A2.25 2.25 0 0119.5 18.75h-15A2.25 2.25 0 012.25 16.5v-9m19.5 0A2.25 2.25 0 0019.5 5.25h-15A2.25 2.25 0 002.25 7.5m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0l-7.5-4.615A2.25 2.25 0 012.25 7.743V7.5" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if (! $isDisabled)
                                        <form method="POST" action="{{ route('users.disable', $user) }}" data-user-disable-form>
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="disabled_reason" value="" data-user-disable-reason-input>
                                            <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700" title="Desactivar usuario" aria-label="Desactivar usuario">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 1920 1920" fill="currentColor" aria-hidden="true">
                                                    <path d="M1285.316 1467.344c-119.106 82.796-341.251 202.437-636.017 188.299-238.533-11.247-423.512-105.182-542.19-188.192v-151.346c0-128.424 82.047-238.962 195.047-262.74 113.751-23.885 230.18-37.595 345.965-40.487 146.954-3.856 296.8 9.854 442.47 40.594 112.786 23.779 194.725 134.209 194.725 262.74v151.132ZM428.44 637.994v-6.962c45.95-12.425 75.298-39.31 98.327-60.625 26.777-24.635 42.95-39.523 80.546-39.523 36.846 0 52.805 14.781 79.261 39.31 31.17 28.597 73.799 67.8 153.167 67.8 57.946 0 95.756-21.315 124.247-43.915v43.915c0 147.704-120.07 267.774-267.774 267.774S428.44 785.698 428.44 637.994ZM696.213 263.11c123.605 0 226.858 84.616 257.599 198.688-13.282 9.747-24.742 20.244-35.453 30.098-26.35 24.314-42.095 38.988-78.619 38.988-37.595 0-53.769-14.888-80.546-39.524-30.848-28.49-73.263-67.586-151.882-67.586-79.368 0-121.998 39.31-153.167 67.908-8.676 8.033-16.28 14.567-23.778 20.35C440.22 373.327 555.042 263.11 696.213 263.11Zm416.443 685.609c-57.197-12.104-115.143-20.887-173.09-27.956 79.904-68.764 131.531-169.34 131.531-282.77v-107.11C1071.097 324.27 902.935 156 696.213 156S321.329 324.27 321.329 530.884v107.11c0 113.215 51.413 213.576 131.102 282.448-57.839 6.962-115.464 16.173-172.34 28.17C117.822 982.672 0 1137.232 0 1316.105v205.865l21.743 16.066c129.175 95.221 342.002 211.435 622.522 224.61 17.245.75 34.275 1.178 51.091 1.178 318.437 0 557.72-139.564 675.22-225.788l21.85-15.959v-205.865c0-178.873-117.713-333.432-279.77-367.493ZM1839.915 156l-200.269 200.269L1439.49 156l-80.198 80.085 200.27 200.269-200.27 200.269 80.198 80.085 200.156-200.27 200.269 200.27L1920 636.623l-200.269-200.269L1920 236.085 1839.915 156Z" fill-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('users.edit', $user) }}" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-brand-secondary/15 bg-white text-brand-secondary transition hover:bg-brand-secondary/5" title="Editar usuario" aria-label="Editar usuario">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.462 3 20l1.538-5.25L16.862 3.487z" />
                                        </svg>
                                    </a>

                                    @if ($visibleRole === \App\Models\User::ROLE_ADMIN)
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" data-user-delete-form>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-700" title="Eliminar definitivamente" aria-label="Eliminar definitivamente">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.1 1.02.17m-1.02-.17L18.16 19.67A2.25 2.25 0 0115.91 21.75H8.09a2.25 2.25 0 01-2.25-2.08L4.77 5.79m14.46 0A48.108 48.108 0 0012 5.25c-2.43 0-4.82.18-7.23.54m14.46 0a48.11 48.11 0 00-14.46 0m9.75-2.04v-.23A1.5 1.5 0 0013.02 2h-2.04a1.5 1.5 0 00-1.5 1.5v.23m5.04 0A49.5 49.5 0 009.48 3.75" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-brand-secondary/70">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($users->hasPages())
    <div class="mt-6" data-users-pagination>{{ $users->links() }}</div>
@endif
