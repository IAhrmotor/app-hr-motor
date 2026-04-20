@extends('layouts.app')

@section('content')
    @php
        $roleLabels = \App\Models\User::roleLabels();
        $selectedRoles = old('roles', []);
    @endphp

    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8 max-w-3xl">
                <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-brand-primary">
                    Prioritaria
                </span>
                <h1 class="mt-4 text-2xl font-bold tracking-tight text-brand-secondary">Enviar notificación destacada</h1>
                <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                    Crea un aviso visible por encima de las notificaciones del foro para los usuarios activos de los roles seleccionados.
                </p>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

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

            <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-8" data-sync-loader-form>
                @csrf

                <div class="grid gap-6">
                    <div>
                        <label for="title" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Título</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                            placeholder="Ejemplo: Cambio importante en el portal">
                    </div>

                    <div>
                        <label for="description" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Descripción</label>
                        <textarea id="description" name="description" rows="6" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                            placeholder="Explica aquí el mensaje que quieres transmitir.">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="link_url" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">Enlace opcional</label>
                        <input id="link_url" name="link_url" type="url" value="{{ old('link_url') }}"
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                            placeholder="https://...">
                        <p class="mt-2 pl-2 text-xs text-brand-secondary/60">
                            Si lo rellenas, al pulsar la notificación el usuario irá a ese destino. Si lo dejas vacío, solo se marcará como leída.
                        </p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5">
                    <div class="max-w-2xl">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Destinatarios</span>
                        <h2 class="mt-2 text-xl font-semibold text-brand-secondary">Roles que recibirán el aviso</h2>
                        <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                            Solo se enviará a usuarios activos que tengan alguno de estos roles.
                        </p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($availableRoles as $role)
                            <label class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-white/70 bg-white px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <input type="checkbox" name="roles[]" value="{{ $role }}" @checked(in_array($role, $selectedRoles, true))
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-primary focus:ring-brand-primary">
                                <span class="flex-1">
                                    <span class="block text-sm font-semibold text-brand-secondary">
                                        {{ $roleLabels[$role] ?? $role }}
                                    </span>
                                    <span class="mt-1 block text-sm text-brand-secondary/65">
                                        @if ($role === \App\Models\User::ROLE_ADMIN)
                                            Usuarios con permisos totales del portal.
                                        @elseif ($role === \App\Models\User::ROLE_MANAGER)
                                            Gestores con acceso al área de administración.
                                        @elseif ($role === \App\Models\User::ROLE_STORE_MANAGER)
                                            Jefes de tienda.
                                        @else
                                            Comerciales activos.
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-primary/15 bg-brand-primary/5 px-4 py-4 text-sm text-brand-secondary/80">
                    La notificación se guardará en la bandeja de cada usuario destinatario y quedará marcada como prioritaria.
                </div>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('admin.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Volver
                    </a>

                    <button
                        type="submit"
                        data-sync-loader-button
                        data-sync-loader-default="Enviar notificación"
                        data-sync-loader-loading="Enviando notificación..."
                        class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        <span data-sync-loader-label>Enviar notificación</span>
                    </button>
                </div>
            </form>
        </section>
    </main>

    <div
        id="leaderboard-sync-loader"
        class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-6 py-8 opacity-0 backdrop-blur-sm transition-opacity duration-200"
    >
        <div class="w-full max-w-md rounded-[2rem] border border-white/60 bg-white/95 p-7 text-center shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.18),rgba(255,255,255,0.95))] ring-1 ring-brand-primary/10">
                <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-brand-primary/20 border-t-brand-primary"></div>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-brand-secondary">Enviando notificación</h2>
            <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                Estamos guardando el aviso y distribuyéndolo entre los usuarios seleccionados. Esta pantalla se cerrará sola al terminar.
            </p>
        </div>
    </div>

    <script>
        (() => {
            const overlay = document.getElementById('leaderboard-sync-loader');

            document.querySelectorAll('[data-sync-loader-form]').forEach((form) => {
                let submitted = false;

                form.addEventListener('submit', (event) => {
                    if (submitted) {
                        return;
                    }

                    submitted = true;
                    event.preventDefault();

                    const button = form.querySelector('[data-sync-loader-button]');
                    const label = form.querySelector('[data-sync-loader-label]');
                    const icon = form.querySelector('[data-sync-loader-icon]');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('opacity-90');
                    }

                    if (label && button?.dataset.syncLoaderLoading) {
                        label.textContent = button.dataset.syncLoaderLoading;
                    }

                    if (icon) {
                        icon.classList.add('animate-spin');
                    }

                    if (overlay) {
                        overlay.classList.remove('hidden');

                        requestAnimationFrame(() => {
                            overlay.classList.remove('pointer-events-none', 'opacity-0');
                            overlay.classList.add('flex', 'opacity-100');
                        });
                    }

                    window.setTimeout(() => form.submit(), 80);
                });
            });
        })();
    </script>
@endsection
