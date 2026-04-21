@extends('layouts.app')

@section('content')
    @php
        $backUrl = in_array(auth()->user()->role, ['admin', 'gestor']) ? route('admin.contacts.index') : route('agenda.index');
    @endphp

    <main class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-5">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-brand-primary/10 text-3xl font-bold text-brand-primary ring-2 ring-brand-primary/10">
                        {{ mb_strtoupper(mb_substr($contact->name, 0, 1)) }}
                    </div>

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Ficha de contacto</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $contact->name }}</h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">{{ $contact->email ?: 'Sin correo registrado' }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                        <a href="{{ route('admin.contacts.edit', $contact) }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Editar contacto</a>
                    @endif

                    <a href="{{ $backUrl }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Volver</a>
                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Información general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-brand-secondary/60">Nombre</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $contact->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Correo</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $contact->email ?: 'No disponible' }}</dd>
                    </div>
                    <div>
                        <dt class="text-brand-secondary/60">Teléfono</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $contact->phone ?: 'No disponible' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-brand-secondary/60">Extensión Enreach</dt>
                        <dd class="mt-1 font-semibold text-brand-secondary">{{ $contact->enreach_extension ?: 'No disponible' }}</dd>
                    </div>
                </dl>
            </section>
        </section>
    </main>
@endsection
