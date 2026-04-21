@extends('layouts.app')

@section('content')
    @php
        $isManager = auth()->user()->role === \App\Models\User::ROLE_MANAGER;
        $selectedRole = old('role', $user->isCommercialLike() ? \App\Models\User::ROLE_COMMERCIAL : $user->role);
        $isStoreManager = old('is_store_manager', $user->role === \App\Models\User::ROLE_STORE_MANAGER ? '1' : '0') === '1';
        $showCommercialFields = $selectedRole === \App\Models\User::ROLE_COMMERCIAL;
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Editar usuario</h1>
                <p class="mt-2 text-sm text-brand-secondary/70">Modifica los datos del usuario seleccionado.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-8">
                @csrf
                @method('PUT')

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="email" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="phone" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Teléfono</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="enreach_extension" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Extensión Enreach</label>
                        <input id="enreach_extension" name="enreach_extension" type="text" value="{{ old('enreach_extension', $user->enreach_extension) }}"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Si existe, también tiene que ser única.</p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="password" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Nueva contraseña</label>
                        <input id="password" name="password" type="password"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Déjala en blanco si no quieres cambiar la contraseña.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Confirmar nueva contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="role" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Rol</label>

                        @if ($isManager)
                            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_COMMERCIAL }}">
                            <input type="text" value="Comercial" disabled
                                class="w-full cursor-not-allowed rounded-2xl border border-gray-300 bg-gray-100 px-4 py-3 text-sm text-brand-secondary/60">
                            <p class="mt-2 text-xs text-brand-secondary/60">Como gestor, no puedes modificar el rol base.</p>
                        @else
                            <div class="relative">
                                <select id="role" name="role" required data-role-select
                                    class="w-full appearance-none rounded-2xl border border-gray-300 px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                    @foreach ($availableRoles as $role)
                                        <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role === \App\Models\User::ROLE_MANAGER ? 'Gestor' : ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div id="dealership-wrapper" @class(['hidden' => ! $showCommercialFields])>
                        <label for="dealership" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Delegación</label>
                        <div class="relative">
                            <select id="dealership" name="dealership_id" @required($showCommercialFields)
                                class="w-full appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                <option value="">Selecciona una delegación</option>
                                @foreach ($availableDealerships as $dealership)
                                    <option value="{{ $dealership->id }}" @selected((string) old('dealership_id', $user->dealership_id) === (string) $dealership->id)>{{ $dealership->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Solo administradores y gestores pueden modificar esta delegación.</p>
                    </div>

                    <div id="salesforce-user-id-wrapper" @class(['hidden' => ! $showCommercialFields])>
                        <label for="salesforce_user_id" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">ID de usuario en Salesforce</label>
                        <input id="salesforce_user_id" name="salesforce_user_id" type="text" value="{{ old('salesforce_user_id', $user->salesforce_user_id) }}" @required($showCommercialFields)
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">Si el usuario deja de ser comercial o jefe de tienda, este campo se limpiará automáticamente.</p>
                    </div>

                    <div id="store-manager-wrapper" @class(['rounded-2xl border border-brand-primary/15 bg-white px-4 py-4 shadow-sm', 'hidden' => ! $showCommercialFields])>
                        <label for="is_store_manager" class="group flex cursor-pointer items-start gap-4">
                            <input id="is_store_manager" name="is_store_manager" type="checkbox" value="1" @checked($isStoreManager)
                                class="peer sr-only">
                            <span class="mt-0.5 inline-flex h-7 w-12 shrink-0 items-center rounded-full bg-slate-300 p-1 transition duration-300 ease-out peer-checked:bg-brand-primary peer-checked:[&>span]:translate-x-5 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-primary/20">
                                <span class="block h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300 ease-out"></span>
                            </span>
                            <span class="flex-1">
                                <span class="block text-sm font-semibold text-brand-secondary">¿Es jefe de tienda?</span>
                                <span class="mt-1 block text-sm text-brand-secondary/65">
                                    Actívalo para guardar a este usuario como jefe de tienda manteniendo el mismo acceso que un comercial por ahora.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('users.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver</a>
                    <input type="submit" value="Guardar cambios" class="cursor-pointer rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90" />
                </div>
            </form>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.querySelector('[data-role-select]');
            const storeManagerWrapper = document.getElementById('store-manager-wrapper');
            const dealershipWrapper = document.getElementById('dealership-wrapper');
            const dealershipSelect = document.getElementById('dealership');
            const salesforceWrapper = document.getElementById('salesforce-user-id-wrapper');
            const salesforceInput = document.getElementById('salesforce_user_id');

            if (!salesforceWrapper || !salesforceInput || !dealershipWrapper || !dealershipSelect || !storeManagerWrapper) {
                return;
            }

            const toggleCommercialFields = () => {
                const isCommercial = !roleSelect || roleSelect.value === '{{ \App\Models\User::ROLE_COMMERCIAL }}';

                storeManagerWrapper.classList.toggle('hidden', !isCommercial);
                dealershipWrapper.classList.toggle('hidden', !isCommercial);
                dealershipSelect.required = isCommercial;
                salesforceWrapper.classList.toggle('hidden', !isCommercial);
                salesforceInput.required = isCommercial;

                if (!isCommercial) {
                    dealershipSelect.value = '';
                    salesforceInput.value = '';
                }
            };

            toggleCommercialFields();
            roleSelect?.addEventListener('change', toggleCommercialFields);
        });
    </script>
@endsection
