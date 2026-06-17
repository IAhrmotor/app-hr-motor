@extends('layouts.app')

@section('content')
    @php
        $visibleRole = app_visible_role(auth()->user());
        $isManager = $visibleRole === \App\Models\User::ROLE_MANAGER;
        $baseRoleLabels = \App\Models\User::baseRoleLabels();
        $extraRoleLabels = \App\Models\User::extraRoleLabels();
        $selectedBaseRole = old('role', \App\Models\User::ROLE_USER);
        $selectedExtraRole = old('extra_role');
        $showSalesforceFields = $selectedBaseRole === \App\Models\User::ROLE_USER
            && in_array($selectedExtraRole, [\App\Models\User::ROLE_COMMERCIAL, \App\Models\User::ROLE_STORE_MANAGER], true);
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">Crear usuario</h1>
                <p class="mt-2 text-sm text-brand-secondary/70">
                    Da de alta un nuevo usuario en la plataforma. Recibirá un correo para establecer su contraseña y activar la cuenta.
                </p>
            </div>

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('users.store') }}" class="space-y-8">
                @csrf

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Nombre <span class="text-rose-600">*</span>
                        </label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="email" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Correo electrónico <span class="text-rose-600">*</span>
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="company_entry_date" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Día que entró en la empresa <span class="text-rose-600">*</span>
                        </label>
                        <input id="company_entry_date" name="company_entry_date" type="date" value="{{ old('company_entry_date') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="job_position" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Puesto <span class="text-rose-600">*</span>
                        </label>
                        <input id="job_position" name="job_position" type="text" value="{{ old('job_position') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="phone" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Teléfono</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="enreach_extension" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Extensión Enreach</label>
                        <input id="enreach_extension" name="enreach_extension" type="text" value="{{ old('enreach_extension') }}"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="role" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Rol base <span class="text-rose-600">*</span>
                        </label>

                        @if ($isManager)
                            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_USER }}">
                            <input type="text" value="Usuario" disabled
                                class="w-full cursor-not-allowed rounded-2xl border border-gray-300 bg-gray-100 px-4 py-3 text-sm text-brand-secondary/60">
                            <p class="mt-2 text-xs text-brand-secondary/60">Como gestor, el rol base queda fijado en usuario.</p>
                        @else
                            <div class="relative">
                                <select id="role" name="role" required data-role-select
                                    class="w-full appearance-none rounded-2xl border border-gray-300 px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                    @foreach ($availableBaseRoles as $role)
                                        <option value="{{ $role }}" @selected($selectedBaseRole === $role)>{{ $baseRoleLabels[$role] ?? $role }}</option>
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

                    <div>
                        <label for="extra_role" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Rol adicional</label>
                        <div class="relative">
                            <select id="extra_role" name="extra_role" data-extra-role-select
                                class="w-full appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                <option value="">Sin rol adicional</option>
                                @foreach ($availableExtraRoles as $role)
                                    <option value="{{ $role }}" @selected($selectedExtraRole === $role)>{{ $extraRoleLabels[$role] ?? $role }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div id="dealership-wrapper">
                        <label for="dealership" class="mb-2 block text-sm font-medium text-brand-secondary">Delegación</label>
                        <div class="relative">
                            <select id="dealership" name="dealership_id"
                                class="w-full appearance-none rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                <option value="">Sin delegación</option>
                                @foreach ($availableDealerships as $dealership)
                                    <option value="{{ $dealership->id }}" @selected((string) old('dealership_id') === (string) $dealership->id)>{{ $dealership->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-brand-secondary/60">La delegación se puede asignar a cualquier usuario.</p>
                    </div>

                    <div id="salesforce-user-id-wrapper" @class(['rounded-2xl border border-brand-primary/15 bg-brand-primary/5 px-4 py-4', 'hidden' => ! $showSalesforceFields])>
                        <label for="salesforce_user_id" class="mb-2 block text-sm font-medium text-brand-secondary">
                            ID de usuario en Salesforce <span class="text-rose-600">*</span>
                        </label>
                        <input id="salesforce_user_id" name="salesforce_user_id" type="text" value="{{ old('salesforce_user_id') }}" @required($showSalesforceFields)
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-primary/15 bg-brand-primary/5 px-4 py-4 text-sm text-brand-secondary/80">
                    El usuario no podrá iniciar sesión hasta que use el enlace recibido por correo y defina su propia contraseña.
                </div>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('users.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver</a>
                    <input type="submit" value="Guardar usuario" class="cursor-pointer rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90" />
                </div>
            </form>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.querySelector('[data-role-select]');
            const extraRoleSelect = document.querySelector('[data-extra-role-select]');
            const salesforceWrapper = document.getElementById('salesforce-user-id-wrapper');
            const salesforceInput = document.getElementById('salesforce_user_id');

            if (!salesforceWrapper || !salesforceInput || !extraRoleSelect) {
                return;
            }

            const toggleSalesforceFields = () => {
                const needsSalesforce = (roleSelect ? roleSelect.value === '{{ \App\Models\User::ROLE_USER }}' : true) && [
                    '{{ \App\Models\User::ROLE_COMMERCIAL }}',
                    '{{ \App\Models\User::ROLE_STORE_MANAGER }}',
                ].includes(extraRoleSelect.value);

                salesforceWrapper.classList.toggle('hidden', !needsSalesforce);
                salesforceInput.required = needsSalesforce;

                if (!needsSalesforce) {
                    salesforceInput.value = '';
                }
            };

            toggleSalesforceFields();
            roleSelect?.addEventListener('change', toggleSalesforceFields);
            extraRoleSelect.addEventListener('change', toggleSalesforceFields);
        });
    </script>
@endsection
