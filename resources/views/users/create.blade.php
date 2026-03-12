@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-brand-secondary">
                    Crear usuario
                </h1>

                <p class="mt-2 text-sm text-brand-secondary/70">
                    Da de alta un nuevo usuario en la plataforma.
                </p>
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

            <form method="POST" action="{{ route('users.store') }}" class="space-y-8">
                @csrf

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Nombre
                        </label>

                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="email" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Correo electrónico
                        </label>

                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="password" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Contraseña
                        </label>

                        <input id="password" name="password" type="password" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Confirmar contraseña
                        </label>

                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="role" class="mb-2 block pl-2 text-sm font-medium text-brand-secondary">
                            Rol
                        </label>

                        @if (auth()->user()->role === 'gestor')
                            <input type="hidden" name="role" value="comercial">

                            <input type="text" value="Comercial" disabled
                                class="w-full cursor-not-allowed rounded-2xl border border-gray-300 bg-gray-100 px-4 py-3 text-sm text-brand-secondary/60">

                            <p class="mt-2 text-xs text-brand-secondary/60">
                                Como gestor, solo puedes crear usuarios con rol comercial.
                            </p>
                        @else
                            <div class="relative">
                                <select id="role" name="role" required
                                    class="w-full appearance-none bg-none rounded-2xl border border-gray-300 px-4 py-3 pr-12 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                    @foreach ($availableRoles as $role)
                                        <option value="{{ $role }}" @selected(old('role') === $role)>
                                            {{ ucfirst($role) }}
                                        </option>
                                    @endforeach
                                </select>

                                <div
                                    class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-brand-secondary/70">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div></div>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('users.index') }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                        Volver
                    </a>

                    <input type="submit" value="Guardar usuario"
                        class="cursor-pointer rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90" />
                </div>
            </form>
        </section>
    </main>
@endsection
