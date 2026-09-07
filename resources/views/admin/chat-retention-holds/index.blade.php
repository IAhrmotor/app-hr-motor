@php
    $backoffice = $backoffice ?? false;
    $retentionRoutes = $retentionRoutes ?? [
        'index' => 'admin.chat-retention-holds.index',
        'conversation.store' => 'admin.chat-retention-holds.store',
        'conversation.update' => 'admin.chat-retention-holds.update',
        'conversation.destroy' => 'admin.chat-retention-holds.destroy',
        'user.store' => 'admin.chat-retention-holds.users.store',
        'user.update' => 'admin.chat-retention-holds.users.update',
        'user.destroy' => 'admin.chat-retention-holds.users.destroy',
    ];
@endphp

    <main class="{{ $backoffice ? 'retention-backoffice w-full px-0 py-0' : 'mx-auto max-w-7xl px-6 py-10 lg:px-8' }}">
        <section class="{{ $backoffice ? 'border-0 bg-transparent p-0 shadow-none' : 'rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8' }}" x-data="chatRetentionHoldsPage()"
            @open-retention-conversation-edit.window="openConversationEdit($event.detail)"
            @open-retention-conversation-deactivate.window="openConversationDeactivate($event.detail)"
            @open-retention-user-edit.window="openUserEdit($event.detail)"
            @open-retention-user-deactivate.window="openUserDeactivate($event.detail)">
            @if ($missingTable ?? false)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                    La tabla de conservación excepcional todavía no existe en esta base de datos. Ejecuta la migración para empezar a gestionar los bloqueos.
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col gap-6">
                @if (! $backoffice)
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Historial</span>
                    <h1 class="mt-3 text-3xl font-semibold text-brand-secondary md:text-4xl">Conservación excepcional</h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        Bloquea una conversación concreta o todas las conversaciones de un usuario para que no entren en la purga automática de mensajes de 6 meses.
                    </p>
                </div>
                @endif

                <div class="grid gap-6 xl:grid-cols-2">
                    <form
                        method="POST"
                        action="{{ route($retentionRoutes['conversation.store']) }}"
                        class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60"
                        x-data="conversationHoldForm(@js($availableConversations->map(fn ($conversation) => [
                            'id' => $conversation->id,
                            'userOneId' => $conversation->user_one_id,
                            'userTwoId' => $conversation->user_two_id,
                            'label' => $conversation->retention_hold_target_label,
                        ])->values()))"
                    @user-selected.window="if ($event.detail.name === 'user_one_id') { userOneId = $event.detail.id; syncConversationId(); } if ($event.detail.name === 'user_two_id') { userTwoId = $event.detail.id; syncConversationId(); }"
                    >
                        @csrf
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Bloqueo por conversación</p>
                                <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">Retener conversación</h2>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4 rounded-[1.5rem] border border-brand-secondary/10 bg-white p-4">
                            <input type="hidden" name="conversation_id" x-model="conversationId" required>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="conversation_user_one_id" class="text-sm font-semibold text-brand-secondary">Usuario 1 <span class="text-red-500">*</span></label>
                                    @if ($backoffice)
                                        <div x-data="retentionUserSelect(@js($availableUsers->map(fn ($user) => ['id' => $user->id, 'label' => $user->name, 'isActive' => $user->is_active])->values()), 'user_one_id')" @user-selected.window="syncExcluded($event)" class="relative mt-2">
                                            <input type="hidden" name="user_one_id" x-model="value" required>
                                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-brand-secondary focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                                                <span x-text="selectedLabel || 'Selecciona el primer usuario'" class="truncate"></span>
                                                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }">
                                                    <path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                            <div x-show="open" x-cloak @click.outside="open = false" class="retention-user-options absolute z-[80] mt-2 w-full overflow-hidden rounded-xl border border-slate-700 bg-gray-900 p-2 shadow-xl">
                                                <input type="search" x-model="query" placeholder="Buscar usuario..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-primary">
                                                <div class="mt-2 max-h-56 overflow-x-hidden overflow-y-auto">
                                                    <template x-for="option in filteredOptions" :key="option.id">
                                                        <button type="button" @click="select(option)" class="block w-full break-words rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-800" :class="option.isActive ? 'text-gray-100' : 'text-gray-500'" x-text="option.label"></button>
                                                    </template>
                                                    <p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-slate-500">No se han encontrado usuarios.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                    <select required
                                        id="conversation_user_one_id"
                                        name="user_one_id"
                                        x-model="userOneId"
                                        @change="syncConversationId()"
                                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                    >
                                        <option value="">Selecciona el primer usuario</option>
                                        @foreach ($availableUsers as $user)
                                            <option value="{{ $user->id }}" @selected(old('user_one_id') == $user->id)>
                                                {{ $user->name }} · {{ $user->email }}{{ $user->is_active ? '' : ' · Desactivado' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @endif
                                </div>

                                <div>
                                    <label for="conversation_user_two_id" class="text-sm font-semibold text-brand-secondary">Usuario 2 <span class="text-red-500">*</span></label>
                                    @if ($backoffice)
                                        <div x-data="retentionUserSelect(@js($availableUsers->map(fn ($user) => ['id' => $user->id, 'label' => $user->name, 'isActive' => $user->is_active])->values()), 'user_two_id')" @user-selected.window="syncExcluded($event)" class="relative mt-2">
                                            <input type="hidden" name="user_two_id" x-model="value" required>
                                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-brand-secondary focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                                                <span x-text="selectedLabel || 'Selecciona el segundo usuario'" class="truncate"></span>
                                                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }">
                                                    <path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                            <div x-show="open" x-cloak @click.outside="open = false" class="retention-user-options absolute z-[80] mt-2 w-full overflow-hidden rounded-xl border border-slate-700 bg-gray-900 p-2 shadow-xl">
                                                <input type="search" x-model="query" placeholder="Buscar usuario..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-primary">
                                                <div class="mt-2 max-h-56 overflow-x-hidden overflow-y-auto">
                                                    <template x-for="option in filteredOptions" :key="option.id">
                                                        <button type="button" @click="select(option)" class="block w-full break-words rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-800" :class="option.isActive ? 'text-gray-100' : 'text-gray-500'" x-text="option.label"></button>
                                                    </template>
                                                    <p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-slate-500">No se han encontrado usuarios.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                    <select required
                                        id="conversation_user_two_id"
                                        name="user_two_id"
                                        x-model="userTwoId"
                                        @change="syncConversationId()"
                                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                    >
                                        <option value="">Selecciona el segundo usuario</option>
                                        @foreach ($availableUsers as $user)
                                            <option value="{{ $user->id }}" @selected(old('user_two_id') == $user->id)>
                                                {{ $user->name }} · {{ $user->email }}{{ $user->is_active ? '' : ' · Desactivado' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @endif
                                </div>
                            </div>

                            @if (! $backoffice)
                                <div class="rounded-2xl border border-brand-primary/10 bg-brand-primary/5 px-4 py-3 text-xs leading-5 text-brand-secondary/70">
                                    Se seleccionan los dos participantes y el sistema localiza automáticamente la conversación existente entre ambos.
                                </div>
                            @endif

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="conversation_expires_at" class="text-sm font-semibold text-brand-secondary">Caducidad opcional</label>
                                    <input
                                        id="conversation_expires_at"
                                        type="date"
                                        name="expires_at"
                                        value="{{ old('expires_at') }}"
                                        class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                    >
                                </div>

                                @if (! $backoffice)
                                    <div class="rounded-2xl border border-brand-primary/10 bg-brand-primary/5 px-4 py-3 text-xs leading-5 text-brand-secondary/70">
                                        Esta opción bloquea sólo la conversación concreta entre los dos usuarios seleccionados.
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label for="conversation_reason" class="text-sm font-semibold text-brand-secondary">Motivo <span class="text-red-500">*</span></label>
                                <textarea
                                    id="conversation_reason"
                                    name="reason"
                                    rows="4"
                                    required
                                    placeholder="Explica por qué esta conversación requiere conservación excepcional."
                                    class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                >{{ old('reason') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end">
                            <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                Retener conversación
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route($retentionRoutes['user.store']) }}" class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-inner shadow-white/60">
                        @csrf
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Bloqueo por usuario</p>
                                <h2 class="mt-2 text-2xl font-semibold text-brand-secondary">Retener conversaciones de usuario</h2>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4 rounded-[1.5rem] border border-brand-secondary/10 bg-white p-4">
                            <div>
                                <label for="user_id" class="text-sm font-semibold text-brand-secondary">Usuario <span class="text-red-500">*</span></label>
                                @if ($backoffice)
                                <div x-data="retentionUserSelect(@js($availableUsers->map(fn ($user) => ['id' => $user->id, 'label' => $user->name, 'isActive' => $user->is_active])->values()), 'user_id')" class="relative mt-2">
                                    <input type="hidden" name="user_id" x-model="value" required>
                                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-brand-secondary focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                                        <span x-text="selectedLabel || 'Selecciona un usuario'" class="truncate"></span>
                                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }">
                                            <path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <div x-show="open" x-cloak @click.outside="open = false" class="retention-user-options absolute z-[80] mt-2 w-full overflow-hidden rounded-xl border border-slate-700 bg-gray-900 p-2 shadow-xl">
                                        <input type="search" x-model="query" placeholder="Buscar usuario..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-primary">
                                        <div class="mt-2 max-h-56 overflow-x-hidden overflow-y-auto">
                                            <template x-for="option in filteredOptions" :key="option.id"><button type="button" @click="select(option)" class="block w-full break-words rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-800" :class="option.isActive ? 'text-gray-100' : 'text-gray-500'" x-text="option.label"></button></template>
                                            <p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-slate-500">No se han encontrado usuarios.</p>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <select required id="user_id" name="user_id" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                                    <option value="">Selecciona un usuario</option>
                                    @foreach ($availableUsers as $user)
                                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                            {{ $user->name }} · {{ $user->email }}{{ $user->is_active ? '' : ' · Desactivado' }}
                                        </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="user_expires_at" class="text-sm font-semibold text-brand-secondary">Caducidad opcional</label>
                                    <input
                                        id="user_expires_at"
                                        type="date"
                                        name="expires_at"
                                        value="{{ old('expires_at') }}"
                                        class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                    >
                                </div>

                                @if (! $backoffice)
                                    <div class="rounded-2xl border border-brand-primary/10 bg-brand-primary/5 px-4 py-3 text-xs leading-5 text-brand-secondary/70">
                                        Esta opción protege todas las conversaciones en las que participe el usuario seleccionado.
                                    </div>
                                @endif
                            </div>

                            <div>
                                    <label for="user_reason" class="text-sm font-semibold text-brand-secondary">Motivo <span class="text-red-500">*</span></label>
                                <textarea
                                    id="user_reason"
                                    name="reason"
                                    rows="4"
                                    required
                                    placeholder="Explica por qué las conversaciones de este usuario requieren conservación excepcional."
                                    class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition placeholder:text-slate-400 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"
                                >{{ old('reason') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end">
                            <button type="submit" class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                Retener usuario
                            </button>
                        </div>
                    </form>
                </div>

                <div class="legacy-retention-table overflow-hidden rounded-[1.75rem] border border-brand-secondary/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-brand-secondary/10">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Conversación</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Usuarios afectados</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Activada</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Administrador</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Motivo</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Caducidad</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Estado</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                                @forelse ($activeHolds as $conversation)
                                    <tr class="align-top">
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-semibold">#{{ $conversation->id }}</p>
                                            <p class="mt-1 text-brand-secondary/65">{{ $conversation->retention_hold_target_label ?: 'Conversación sin participantes' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <div class="space-y-2">
                                                @if ($conversation->userOne)
                                                    <div class="rounded-2xl border border-brand-secondary/10 bg-white px-3 py-2">
                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-secondary/45">Participante 1</p>
                                                        <p class="mt-1 font-medium text-brand-secondary">{{ $conversation->userOne->name }}</p>
                                                    </div>
                                                @endif

                                                @if ($conversation->userTwo)
                                                    <div class="rounded-2xl border border-brand-secondary/10 bg-white px-3 py-2">
                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-secondary/45">Participante 2</p>
                                                        <p class="mt-1 font-medium text-brand-secondary">{{ $conversation->userTwo->name }}</p>
                                                    </div>
                                                @endif

                                                @if (! $conversation->userOne && ! $conversation->userTwo)
                                                    <p class="font-medium">Sin datos de participantes</p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-semibold">{{ $conversation->retention_hold_created_at?->format('d/m/Y') ?? 'N/D' }}</p>
                                            <p class="mt-1 text-brand-secondary/65">{{ $conversation->retention_hold_created_at?->format('H:i:s') ?? 'N/D' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-semibold">{{ $conversation->retentionHoldCreatedByUser?->name ?? 'Sistema' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="max-w-md whitespace-pre-line text-brand-secondary/90">{{ $conversation->retention_hold_reason }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-medium">{{ $conversation->retention_hold_expires_at?->format('d/m/Y H:i') ?? 'Sin caducidad' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $conversation->retention_hold_status_label === 'Activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $conversation->retention_hold_status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-right text-sm">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button type="button" class="cursor-pointer rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 font-semibold text-brand-secondary transition hover:bg-brand-secondary/5" @click="openConversationEdit({{ \Illuminate\Support\Js::from(['id' => $conversation->id, 'reason' => $conversation->retention_hold_reason, 'expiresAt' => optional($conversation->retention_hold_expires_at)->format('Y-m-d'), 'target' => $conversation->retention_hold_target_label ?: ('#' . $conversation->id)]) }})">Editar</button>
                                                <button type="button" class="cursor-pointer rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 font-semibold text-rose-700 transition hover:bg-rose-100" @click="openConversationDeactivate({{ \Illuminate\Support\Js::from(['id' => $conversation->id, 'target' => $conversation->retention_hold_target_label ?: ('#' . $conversation->id)]) }})">Desactivar</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-brand-secondary/65">
                                            Todavía no hay conversaciones protegidas por conservación excepcional.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="legacy-retention-table overflow-hidden rounded-[1.75rem] border border-brand-secondary/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-brand-secondary/10">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Usuario</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Alcance</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Activada</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Administrador</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Motivo</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Caducidad</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Estado</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-secondary/10 bg-white">
                                @forelse ($activeUserHolds as $userHold)
                                    <tr class="align-top">
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-semibold">{{ $userHold->user?->name ?? 'Usuario eliminado' }}</p>
                                            <p class="mt-1 text-brand-secondary/65">{{ $userHold->user?->email ?? 'N/D' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-medium">Todas sus conversaciones</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-semibold">{{ $userHold->retention_hold_created_at?->format('d/m/Y') ?? 'N/D' }}</p>
                                            <p class="mt-1 text-brand-secondary/65">{{ $userHold->retention_hold_created_at?->format('H:i:s') ?? 'N/D' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-semibold">{{ $userHold->createdBy?->name ?? 'Sistema' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="max-w-md whitespace-pre-line text-brand-secondary/90">{{ $userHold->retention_hold_reason }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <p class="font-medium">{{ $userHold->retention_hold_expires_at?->format('d/m/Y H:i') ?? 'Sin caducidad' }}</p>
                                        </td>
                                        <td class="px-6 py-5 text-sm text-brand-secondary">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] {{ $userHold->retention_hold_status_label === 'Activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $userHold->retention_hold_status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-right text-sm">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button type="button" class="cursor-pointer rounded-2xl border border-brand-secondary/10 bg-white px-4 py-2 font-semibold text-brand-secondary transition hover:bg-brand-secondary/5" @click="openUserEdit({{ \Illuminate\Support\Js::from(['id' => $userHold->id, 'reason' => $userHold->retention_hold_reason, 'expiresAt' => optional($userHold->retention_hold_expires_at)->format('Y-m-d'), 'target' => $userHold->user?->name ?? ('#' . $userHold->user_id)]) }})">Editar</button>
                                                <button type="button" class="cursor-pointer rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 font-semibold text-rose-700 transition hover:bg-rose-100" @click="openUserDeactivate({{ \Illuminate\Support\Js::from(['id' => $userHold->id, 'target' => $userHold->user?->name ?? ('#' . $userHold->user_id)]) }})">Desactivar</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-brand-secondary/65">
                                            Todavía no hay bloqueos por usuario activos.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if (is_object($activeHolds) && method_exists($activeHolds, 'hasPages') && $activeHolds->hasPages())
                <div class="mt-6">
                    {{ $activeHolds->links() }}
                </div>
            @endif

            <div x-show="conversationEditOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 px-4 py-8" @keydown.escape.window="closeConversationEdit()">
                <div class="absolute inset-0" @click="closeConversationEdit()"></div>
                <form method="POST" :action="conversationEditAction" class="retention-modal relative z-10 w-full max-w-2xl rounded-[2rem] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-2xl font-semibold text-brand-secondary">Editar conservación excepcional</h2>
                    <p class="mt-2 text-sm leading-6 text-brand-secondary/70">Ajusta el motivo o la fecha de caducidad de la conversación seleccionada.</p>
                    <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary">
                        <span class="font-semibold">Conversación:</span> <span x-text="selectedTarget || 'N/D'"></span>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-brand-secondary" for="conversation-edit-reason">Motivo <span class="text-red-500">*</span></label>
                            <textarea id="conversation-edit-reason" x-model="conversationEditReason" name="reason" rows="4" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"></textarea>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-brand-secondary" for="conversation-edit-expires-at">Caducidad opcional</label>
                            <input id="conversation-edit-expires-at" x-model="conversationEditExpiresAt" type="date" name="expires_at" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                        </div>
                    </div>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="cursor-pointer rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" @click="closeConversationEdit()">Cancelar</button>
                        <button type="submit" class="cursor-pointer rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-primary/95">Guardar cambios</button>
                    </div>
                </form>
            </div>

            <div x-show="conversationDeactivateOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 px-4 py-8" @keydown.escape.window="closeConversationDeactivate()">
                <div class="absolute inset-0" @click="closeConversationDeactivate()"></div>
                <form method="POST" :action="conversationDeactivateAction" class="retention-modal relative z-10 w-full max-w-2xl rounded-[2rem] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-2xl font-semibold text-brand-secondary">Desactivar conservación excepcional</h2>
                    <p class="mt-2 text-sm leading-6 text-brand-secondary/70">Indica el motivo para dejar de retener esta conversación.</p>
                    <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary">
                        <span class="font-semibold">Conversación:</span> <span x-text="selectedTarget || 'N/D'"></span>
                    </div>
                    <div class="mt-5">
                        <label class="text-sm font-semibold text-brand-secondary" for="conversation-deactivate-reason">Motivo de desactivación <span class="text-red-500">*</span></label>
                        <textarea id="conversation-deactivate-reason" x-model="conversationDeactivateReason" name="reason" rows="4" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"></textarea>
                    </div>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="cursor-pointer rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" @click="closeConversationDeactivate()">Cancelar</button>
                        <button type="submit" class="cursor-pointer rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">Desactivar bloqueo</button>
                    </div>
                </form>
            </div>

            <div x-show="userEditOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 px-4 py-8" @keydown.escape.window="closeUserEdit()">
                <div class="absolute inset-0" @click="closeUserEdit()"></div>
                <form method="POST" :action="userEditAction" class="retention-modal relative z-10 w-full max-w-2xl rounded-[2rem] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-2xl font-semibold text-brand-secondary">Editar conservación excepcional de usuario</h2>
                    <p class="mt-2 text-sm leading-6 text-brand-secondary/70">Ajusta el motivo o la fecha de caducidad del bloqueo de usuario.</p>
                    <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary">
                        <span class="font-semibold">Usuario:</span> <span x-text="selectedTarget || 'N/D'"></span>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-brand-secondary" for="user-edit-reason">Motivo <span class="text-red-500">*</span></label>
                            <textarea id="user-edit-reason" x-model="userEditReason" name="reason" rows="4" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"></textarea>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-brand-secondary" for="user-edit-expires-at">Caducidad opcional</label>
                            <input id="user-edit-expires-at" x-model="userEditExpiresAt" type="date" name="expires_at" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10">
                        </div>
                    </div>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="cursor-pointer rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" @click="closeUserEdit()">Cancelar</button>
                        <button type="submit" class="cursor-pointer rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-primary/95">Guardar cambios</button>
                    </div>
                </form>
            </div>

            <div x-show="userDeactivateOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 px-4 py-8" @keydown.escape.window="closeUserDeactivate()">
                <div class="absolute inset-0" @click="closeUserDeactivate()"></div>
                <form method="POST" :action="userDeactivateAction" class="retention-modal relative z-10 w-full max-w-2xl rounded-[2rem] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-2xl font-semibold text-brand-secondary">Desactivar conservación excepcional de usuario</h2>
                    <p class="mt-2 text-sm leading-6 text-brand-secondary/70">Indica el motivo para dejar de retener todas las conversaciones de ese usuario.</p>
                    <div class="mt-5 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-4 py-3 text-sm text-brand-secondary">
                        <span class="font-semibold">Usuario:</span> <span x-text="selectedTarget || 'N/D'"></span>
                    </div>
                    <div class="mt-5">
                        <label class="text-sm font-semibold text-brand-secondary" for="user-deactivate-reason">Motivo de desactivación <span class="text-red-500">*</span></label>
                        <textarea id="user-deactivate-reason" x-model="userDeactivateReason" name="reason" rows="4" required class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10"></textarea>
                    </div>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="cursor-pointer rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" @click="closeUserDeactivate()">Cancelar</button>
                        <button type="submit" class="cursor-pointer rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">Desactivar bloqueo</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        function chatRetentionHoldsPage() {
            return {
                selectedTarget: '',
                conversationEditOpen: false,
                conversationDeactivateOpen: false,
                userEditOpen: false,
                userDeactivateOpen: false,
                conversationEditAction: '',
                conversationDeactivateAction: '',
                userEditAction: '',
                userDeactivateAction: '',
                conversationEditReason: '',
                conversationEditExpiresAt: '',
                conversationDeactivateReason: '',
                userEditReason: '',
                userEditExpiresAt: '',
                userDeactivateReason: '',
                openConversationEdit(payload) {
                    this.selectedTarget = payload.target || '';
                    this.conversationEditReason = payload.reason || '';
                    this.conversationEditExpiresAt = payload.expiresAt || '';
                    this.conversationEditAction = `{{ route($retentionRoutes['conversation.update'], ['conversation' => '__id__']) }}`.replace('__id__', payload.id);
                    this.conversationEditOpen = true;
                },
                closeConversationEdit() {
                    this.conversationEditOpen = false;
                },
                openConversationDeactivate(payload) {
                    this.selectedTarget = payload.target || '';
                    this.conversationDeactivateReason = '';
                    this.conversationDeactivateAction = `{{ route($retentionRoutes['conversation.destroy'], ['conversation' => '__id__']) }}`.replace('__id__', payload.id);
                    this.conversationDeactivateOpen = true;
                },
                closeConversationDeactivate() {
                    this.conversationDeactivateOpen = false;
                },
                openUserEdit(payload) {
                    this.selectedTarget = payload.target || '';
                    this.userEditReason = payload.reason || '';
                    this.userEditExpiresAt = payload.expiresAt || '';
                    this.userEditAction = `{{ route($retentionRoutes['user.update'], ['userHold' => '__id__']) }}`.replace('__id__', payload.id);
                    this.userEditOpen = true;
                },
                closeUserEdit() {
                    this.userEditOpen = false;
                },
                openUserDeactivate(payload) {
                    this.selectedTarget = payload.target || '';
                    this.userDeactivateReason = '';
                    this.userDeactivateAction = `{{ route($retentionRoutes['user.destroy'], ['userHold' => '__id__']) }}`.replace('__id__', payload.id);
                    this.userDeactivateOpen = true;
                },
                closeUserDeactivate() {
                    this.userDeactivateOpen = false;
                },
            };
        }

        function retentionUserSelect(options, name) {
            return {
                options,
                name,
                open: false,
                query: '',
                value: '',
                excludedId: '',
                selectedLabel: '',
                get filteredOptions() {
                    const search = this.query.trim().toLowerCase();

                    const filtered = search === ''
                        ? this.options
                        : this.options.filter((option) => option.label.toLowerCase().includes(search));

                    return filtered
                        .filter((option) => String(option.id) !== String(this.excludedId))
                        .slice(0, search === '' ? 50 : 100);
                },
                syncExcluded(event) {
                    if (event.detail.name === this.name) {
                        return;
                    }

                    this.excludedId = String(event.detail.id || '');

                    if (this.value && this.value === this.excludedId) {
                        this.value = '';
                        this.selectedLabel = '';
                    }
                },
                select(option) {
                    this.value = String(option.id);
                    this.selectedLabel = option.label;
                    this.query = '';
                    this.open = false;
                    this.$dispatch('user-selected', { name: this.name, id: this.value });
                },
            };
        }

        function conversationHoldForm(conversations) {
            return {
                conversations,
                userOneId: '',
                userTwoId: '',
                conversationId: '',
                syncConversationId() {
                    const first = String(this.userOneId || '');
                    const second = String(this.userTwoId || '');

                    if (!first || !second || first === second) {
                        this.conversationId = '';
                        return;
                    }

                    const match = this.conversations.find((conversation) => (
                        (String(conversation.userOneId) === first && String(conversation.userTwoId) === second)
                        || (String(conversation.userOneId) === second && String(conversation.userTwoId) === first)
                    ));

                    this.conversationId = match ? String(match.id) : '';
                },
            };
        }
    </script>
