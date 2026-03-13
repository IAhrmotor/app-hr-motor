@extends('layouts.app')

@section('content')
    @php
        $isOwnProfile = auth()->id() === $user->id;
    @endphp

    <main class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-5">
                    <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}"
                        class="h-24 w-24 rounded-full object-cover ring-2 ring-brand-primary/10">

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">
                            Perfil de usuario
                        </p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">
                            {{ $user->name }}
                        </h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">
                            {{ $user->email }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($user->linkedin_url)
                        <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0A66C2] text-white transition hover:opacity-90"
                            title="Ver LinkedIn" aria-label="Ver LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24"
                                fill="currentColor" aria-hidden="true">
                                <path
                                    d="M6.94 8.5H3.56V19h3.38V8.5ZM5.25 3C4.17 3 3.3 3.88 3.3 4.96c0 1.07.87 1.94 1.95 1.94 1.08 0 1.95-.87 1.95-1.94C7.2 3.88 6.33 3 5.25 3Zm14.45 9.47c0-3.17-1.69-4.64-3.95-4.64-1.82 0-2.64 1-3.09 1.7V8.5H9.28c.04.68 0 10.5 0 10.5h3.38v-5.86c0-.31.02-.62.11-.84.25-.62.82-1.27 1.79-1.27 1.27 0 1.78.96 1.78 2.37V19h3.38v-6.53Z" />
                            </svg>
                        </a>
                    @endif

                    @if ($isOwnProfile)
                        <a href="{{ route('profile.edit') }}"
                            class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Editar perfil
                        </a>
                    @endif

                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                        <a href="{{ route('users.index') }}"
                            class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">
                            Volver a usuarios
                        </a>
                    @endif
                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Informacion general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-3">
                    <div>
                        <dt class="text-brand-secondary/60">Nombre</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-brand-secondary/60">Correo</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-brand-secondary/60">Rol</dt>
                        <dd class="mt-1">
                            <span
                                class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">
                                {{ ucfirst($user->role) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </section>
        </section>
    </main>
@endsection
