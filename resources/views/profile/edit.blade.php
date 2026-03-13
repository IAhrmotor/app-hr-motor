@extends('layouts.app')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">
                        Perfil
                    </p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">
                        Modificar perfil
                    </h1>
                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70">
                        Desde aqui puedes subir tu foto de perfil y guardar el enlace a tu LinkedIn.
                    </p>
                </div>

                <div class="rounded-3xl border border-brand-secondary/10 bg-slate-50 p-5">
                    <div class="flex items-center gap-4">
                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar de {{ auth()->user()->name }}"
                            class="h-20 w-20 rounded-full object-cover ring-2 ring-brand-primary/10">
                        <div>
                            <p class="text-sm font-semibold text-brand-secondary">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-brand-secondary/60">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Revisa los campos marcados y vuelve a intentarlo.
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-8">
                @csrf
                @method('PATCH')

                <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6">
                        <h2 class="text-lg font-semibold text-brand-secondary">Foto de perfil</h2>
                        <p class="mt-2 text-sm text-brand-secondary/70">
                            Sube una imagen cuadrada o vertical en JPG, PNG o WEBP. Tamaño maximo: 2 MB.
                        </p>

                        <div class="mt-6 flex flex-col gap-5 sm:flex-row sm:items-center">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar actual de {{ auth()->user()->name }}"
                                class="h-28 w-28 rounded-full object-cover ring-2 ring-brand-primary/10">

                            <div class="flex-1">
                                <label for="avatar"
                                    class="mb-2 block text-sm font-semibold text-brand-secondary/80">Nueva foto</label>
                                <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    class="block w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary file:mr-4 file:rounded-xl file:border-0 file:bg-brand-primary file:px-4 file:py-2 file:font-semibold file:text-white hover:file:opacity-90">
                                @error('avatar')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                        <h2 class="text-lg font-semibold text-brand-secondary">LinkedIn</h2>
                        <p class="mt-2 text-sm text-brand-secondary/70">
                            Guarda aqui la URL de tu perfil publico de LinkedIn.
                        </p>

                        <div class="mt-6">
                            <label for="linkedin_url"
                                class="mb-2 block text-sm font-semibold text-brand-secondary/80">URL de LinkedIn</label>
                            <input id="linkedin_url" name="linkedin_url" type="url"
                                value="{{ old('linkedin_url', auth()->user()->linkedin_url) }}"
                                placeholder="https://www.linkedin.com/in/tu-perfil"
                                class="w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-brand-secondary outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                            @error('linkedin_url')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (auth()->user()->linkedin_url)
                            <a href="{{ auth()->user()->linkedin_url }}" target="_blank" rel="noopener noreferrer"
                                class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-primary transition hover:opacity-80">
                                Ver perfil actual
                            </a>
                        @endif
                    </section>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit"
                        class="inline-flex cursor-pointer items-center rounded-2xl bg-brand-primary px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </section>
    </main>
@endsection
